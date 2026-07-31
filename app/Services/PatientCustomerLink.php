<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\PatientMedicalHistory;
use App\Models\StoreCustomer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A hospital keeps one person record, not two. Everything outside HMIS — billing, ledgers,
 * receipts, WhatsApp campaigns, CRM history — reads store_customers, while HMIS reads
 * patients, so a patient who was never mirrored into store_customers is invisible to half
 * the platform (and the same human ends up entered twice).
 *
 * patients.store_customer_id is the single link, kept in step in both directions:
 * every patient is a client, and in a hospital store every client is a patient.
 */
class PatientCustomerLink
{
    /** Each direction writes the other side; without this the two model hooks ping-pong. */
    private static bool $syncing = false;

    private static ?bool $linkColumn = null;

    private static array $hospitalStores = [];

    /**
     * patients is created by the HMIS installer, not a migration, so the link column is
     * added on first use the same way the rest of the module extends its tables.
     */
    public static function ensureSchema(): bool
    {
        if (self::$linkColumn !== null) {
            return self::$linkColumn;
        }

        try {
            if (!Schema::hasTable('patients') || !Schema::hasTable('store_customers')) {
                return self::$linkColumn = false;
            }

            if (!Schema::hasColumn('patients', 'store_customer_id')) {
                // MySQL commits the open transaction as soon as it sees DDL, which would cut a
                // half-written registration in two. Sit it out and add the column on the next
                // call that runs outside one — nothing is cached, so it retries.
                if (DB::transactionLevel() > 0) {
                    return false;
                }
                DB::statement('ALTER TABLE `patients` ADD COLUMN `store_customer_id` BIGINT UNSIGNED NULL');
                DB::statement('ALTER TABLE `patients` ADD INDEX `patients_store_customer_id_idx` (`store_customer_id`)');
            }
        } catch (\Throwable $e) {
            return self::$linkColumn = false;
        }

        return self::$linkColumn = true;
    }

    /**
     * Store-scoped, because the model hooks fire for stores that are not the one in session
     * (queued jobs, API bookings, the backfill command) — _isHospital() would answer for the
     * wrong store there.
     */
    public static function isHospitalStore($storeId): bool
    {
        $storeId = (int) $storeId;
        if (!$storeId) {
            return false;
        }

        if (!array_key_exists($storeId, self::$hospitalStores)) {
            $type = DB::table('stores')->where('id', $storeId)->value('business_type');
            self::$hospitalStores[$storeId] = strtolower((string) $type) === 'hospital';
        }

        return self::$hospitalStores[$storeId];
    }

    /**
     * Mirror a patient onto the store's client list. Matches an existing client by phone
     * first, so a walk-in already known to billing is linked rather than duplicated.
     */
    public static function fromPatient(Patient $patient): ?StoreCustomer
    {
        if (self::$syncing || !$patient->store_id || !$patient->exists || !self::ensureSchema()) {
            return null;
        }

        self::$syncing = true;
        try {
            $phone = $patient->phone ? _cleanPhoneNumber($patient->phone) : null;

            $customer = $patient->store_customer_id
                ? StoreCustomer::where('store_id', $patient->store_id)->find($patient->store_customer_id)
                : null;

            if (!$customer && $phone) {
                // One number covers a whole family here, so a client already claimed by another
                // patient is off limits — the second person gets their own record rather than
                // being merged into their relative's ledger.
                $candidates = StoreCustomer::where('store_id', $patient->store_id)
                    ->where('user_type', 'customer')
                    ->where('phone', $phone)
                    ->pluck('id');

                if ($candidates->isNotEmpty()) {
                    $claimed = Patient::where('store_id', $patient->store_id)
                        ->whereIn('store_customer_id', $candidates)
                        ->where('id', '!=', $patient->id)
                        ->pluck('store_customer_id')
                        ->all();

                    $free = $candidates->first(fn($cid) => !in_array($cid, $claimed));
                    $customer = $free ? StoreCustomer::find($free) : null;
                }
            }

            if (!$customer) {
                $customer = new StoreCustomer();
                $customer->store_id  = $patient->store_id;
                $customer->user_type = 'customer';
            }

            $customer->f_name = $patient->name;
            // Only fill what the patient actually carries — a half-filled OPD registration
            // must not blank out details the client record already holds.
            if ($phone)            $customer->phone    = $phone;
            if ($patient->email)   $customer->email    = $patient->email;
            if ($patient->address) $customer->address  = $patient->address;
            if ($patient->pincode) $customer->pin_code = $patient->pincode;
            $customer->save();

            if ((int) $patient->store_customer_id !== (int) $customer->id) {
                // Straight to the column: $patient->save() would re-enter the patient hook.
                Patient::where('id', $patient->id)->update(['store_customer_id' => $customer->id]);
                $patient->store_customer_id = $customer->id;
                $patient->syncOriginalAttribute('store_customer_id');
            }

            return $customer;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('PatientCustomerLink::fromPatient failed: ' . $e->getMessage());
            return null;
        } finally {
            self::$syncing = false;
        }
    }

    /**
     * The other direction: in a hospital store a client added from CRM, billing or the AI
     * agent is the same human who walks into OPD, so give them a patient record too.
     * Suppliers (user_type vendor) are not people the hospital treats — skipped.
     */
    public static function fromCustomer(StoreCustomer $customer): ?Patient
    {
        if (self::$syncing || !$customer->store_id || !$customer->exists) {
            return null;
        }
        if (($customer->user_type ?: 'customer') !== 'customer') {
            return null;
        }
        if (!self::isHospitalStore($customer->store_id) || !self::ensureSchema()) {
            return null;
        }

        self::$syncing = true;
        try {
            $phone = $customer->phone ? _cleanPhoneNumber($customer->phone) : null;

            $patient = Patient::where('store_id', $customer->store_id)
                ->where('store_customer_id', $customer->id)
                ->first();

            if (!$patient && $phone) {
                // Patient phones are typed at a reception desk and may carry a country code,
                // so match on the last ten digits rather than the whole string.
                $patient = Patient::where('store_id', $customer->store_id)
                    ->where('phone', 'like', '%' . $phone)
                    ->whereNull('store_customer_id')
                    ->first();
            }

            $isNew = !$patient;
            if ($isNew) {
                $patient = new Patient();
                $patient->store_id    = $customer->store_id;
                $patient->patient_uid = Patient::generateUid((int) $customer->store_id);
                $patient->status      = 1;
            }

            $patient->store_customer_id = $customer->id;
            $patient->name = $customer->f_name;
            // Rewrite the number only when it is genuinely a different one — otherwise a
            // patient recorded as +91XXXXXXXXXX would be stripped on every client edit.
            if ($phone && _cleanPhoneNumber($patient->phone) !== $phone) {
                $patient->phone = $phone;
            }
            if ($customer->email)    $patient->email   = $customer->email;
            if ($customer->address)  $patient->address = $customer->address;
            if ($customer->pin_code) $patient->pincode = $customer->pin_code;
            $patient->save();

            if ($isNew) {
                PatientMedicalHistory::firstOrCreate(['patient_id' => $patient->id]);
            }

            return $patient;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('PatientCustomerLink::fromCustomer failed: ' . $e->getMessage());
            return null;
        } finally {
            self::$syncing = false;
        }
    }

    /** Read-only lookup for the views — never creates, never alters anything. */
    public static function patientFor($customer): ?Patient
    {
        if (!$customer || !self::ensureSchema()) {
            return null;
        }

        return Patient::where('store_id', $customer->store_id)
            ->where('store_customer_id', $customer->id)
            ->first();
    }

    /** Attributes that, when edited on either side, are worth pushing across the link. */
    public static function patientFieldsChanged(Patient $patient): bool
    {
        return self::touched($patient, ['name', 'phone', 'email', 'address', 'pincode']);
    }

    public static function customerFieldsChanged(StoreCustomer $customer): bool
    {
        return self::touched($customer, ['f_name', 'phone', 'email', 'address', 'pin_code']);
    }

    /**
     * getChanges() is what an `updated` listener should read; getDirty() covers a caller that
     * asks before saving. Checking both keeps the helper usable from either side.
     */
    private static function touched($model, array $fields): bool
    {
        $changed = array_merge(array_keys($model->getChanges()), array_keys($model->getDirty()));

        return (bool) array_intersect($changed, $fields);
    }
}
