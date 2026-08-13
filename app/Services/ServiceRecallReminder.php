<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * "It has been six months since we serviced your washing machine — due again?"
 *
 * The service-request twin of AppointmentRebookReminder and RepeatPurchaseReminder. A customer
 * whose job was completed and who never came back is invited to book again, a number of days
 * after completion that the VENDOR chooses — one setting per store, because unlike a doctor's
 * recall there is no clinical judgement to make per service, and a store that services fans and
 * washing machines wants one answer to "how often do we chase".
 *
 * A store that never sets it is never swept, which is the right default: this messages past
 * customers unprompted and every message is billed.
 *
 * The three guards that keep the other two sweeps from nagging apply here too, and share their
 * machinery rather than copying it:
 *
 *   1. A customer with a newer job — booked or completed — is skipped. They have already come
 *      back; inviting them to is how a business's number gets reported.
 *   2. The fortnight cooldown is the same table and the same phone key the retail and hospital
 *      sweeps use, so one person who is a patient, a retail customer AND a service customer hears
 *      from all of it once a fortnight rather than three times.
 *   3. A completed job is chased once. Coming back re-arms it; silence does not earn a second.
 */
class ServiceRecallReminder
{
    /** Template the invitation is sent on. MARKETING — it is a nudge to buy again. */
    const TEMPLATE = 'service_recall';

    /** Jobs completed longer ago than this are not worth chasing — the trail is cold. */
    const STALE_DAYS = 540;

    /**
     * The statuses that END a request. Anything else — including the empty string a freshly
     * dispatched request carries, and "Confirmation Request Sent" — is still in flight.
     *
     * Listed as the closed set rather than the open one on purpose: current_status is free text
     * written by several screens, and a new value added later would silently read as "not coming
     * back" if this named the open ones instead.
     */
    const CLOSED_STATUSES = ['Completed', 'Cancelled', 'Rejected'];

    public static function ensureColumn(): void
    {
        if (Schema::hasTable('stores') && !Schema::hasColumn('stores', 'wa_service_recall_days')) {
            DB::statement("ALTER TABLE `stores` ADD COLUMN `wa_service_recall_days` INT NULL");
        }
    }

    /** Stores that have chosen a recall gap. NULL or 0 means "never chase", and is the default. */
    public static function configuredStoreIds(): array
    {
        self::ensureColumn();

        return DB::table('stores')->where('wa_service_recall_days', '>', 0)->pluck('id')->all();
    }

    /**
     * Completed jobs whose recall has come due, newest completion per customer+service.
     *
     * Grouped by customer and service rather than listed per job: someone who had the same
     * appliance serviced three times is one invitation about that appliance, dated from the last
     * time, not three.
     */
    public static function dueFor(int $storeId): array
    {
        self::ensureColumn();

        $days = (int) DB::table('stores')->where('id', $storeId)->value('wa_service_recall_days');
        if ($days <= 0) {
            return [];
        }

        $cutoff = now()->subDays($days);
        $stale  = now()->subDays(self::STALE_DAYS);

        $rows = DB::table('accepted_service_requests as a')
            ->join('service_requests as s', 's.id', '=', 'a.service_request_id')
            ->leftJoin('items as i', 'i.id', '=', 's.item_id')
            ->leftJoin('users as u', 'u.id', '=', 's.user_id')
            ->where('a.vendor_id', $storeId)
            ->where('a.current_status', 'Completed')
            ->whereNotNull('a.completed_at')
            ->where('a.completed_at', '<=', $cutoff)
            ->where('a.completed_at', '>=', $stale)
            ->whereNotNull('s.user_id')
            ->select(
                's.user_id',
                's.item_id',
                DB::raw('MAX(a.completed_at) as last_done'),
                DB::raw('MAX(i.name) as service_name'),
                DB::raw('MAX(u.phone) as user_phone'),
                DB::raw('MAX(u.f_name) as f_name'),
                DB::raw('MAX(s.patient_phone) as fallback_phone')
            )
            ->groupBy('s.user_id', 's.item_id')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        // Anyone who has come back — a newer job of any status that is not cancelled — is not
        // someone to invite back. Read once for the whole store rather than per row.
        $returned = DB::table('accepted_service_requests as a')
            ->join('service_requests as s', 's.id', '=', 'a.service_request_id')
            ->where('a.vendor_id', $storeId)
            ->where(function ($q) use ($cutoff) {
                $q->where('a.completed_at', '>', $cutoff)
                  ->orWhereNotIn('a.current_status', self::CLOSED_STATUSES);
            })
            ->pluck('s.user_id')
            ->flip();

        $due = [];
        foreach ($rows as $r) {
            if (isset($returned[$r->user_id])) {
                continue;
            }
            $phone = trim((string) ($r->user_phone ?: $r->fallback_phone));
            if ($phone === '') {
                continue;
            }

            $due[] = (object) [
                'user_id'   => (int) $r->user_id,
                'ref_key'   => 'item:' . (int) $r->item_id,
                'label'     => trim((string) $r->service_name) ?: 'service',
                'name'      => trim((string) $r->f_name),
                'phone'     => $phone,
                'last_done' => Carbon::parse($r->last_done),
            ];
        }

        return self::withoutAlreadyReminded($storeId, $due);
    }

    /** Drop anything already chased since it was last completed. */
    protected static function withoutAlreadyReminded(int $storeId, array $due): array
    {
        if (empty($due)) {
            return [];
        }

        RepeatPurchaseReminder::ensureTables();

        $chased = DB::table('wa_repeat_reminders')
            ->where('store_id', $storeId)
            ->where('customer_type', 'service')
            ->whereIn('customer_id', array_map(fn($d) => $d->user_id, $due))
            ->get(['customer_id', 'ref_key', 'reminded_at'])
            ->keyBy(fn($r) => $r->customer_id . '|' . $r->ref_key);

        return array_values(array_filter($due, function ($d) use ($chased) {
            $seen = $chased[$d->user_id . '|' . $d->ref_key] ?? null;
            if (!$seen || !$seen->reminded_at) {
                return true;
            }
            // Chased before — only worth chasing again if they came back and lapsed since.
            return $d->last_done->gt(Carbon::parse($seen->reminded_at));
        }));
    }

    /** Send this store's due recalls. Returns how many customers were messaged. */
    public static function runStore(int $storeId): int
    {
        if (!NotificationPrefs::enabled($storeId, 'whatsapp_send', 'service_recall')) {
            return 0;
        }

        $wa = WhatsAppService::make($storeId);
        if ($wa->source() !== 'vendor' || !WhatsAppBilling::isActive($storeId)) {
            return 0;
        }
        if (!WhatsAppService::templateApproved($storeId, self::TEMPLATE)) {
            return 0;
        }

        $due = self::dueFor($storeId);
        if (empty($due)) {
            return 0;
        }

        $storeName  = DB::table('stores')->where('id', $storeId)->value('name') ?: 'our team';
        $optedOut   = array_flip(array_map(
            fn($p) => RepeatPurchaseReminder::phoneKey((string) $p),
            WhatsAppService::optedOutPhones($storeId)
        ));
        $onCooldown = RepeatPurchaseReminder::recentlyMessagedPhones($storeId);

        // One message per person even when two different services are both due — they are being
        // asked to come back to this store, and two texts about it is the thing to avoid. The
        // service named is whichever came due first.
        $byPhone = [];
        foreach ($due as $d) {
            $key = RepeatPurchaseReminder::phoneKey($d->phone);
            if ($key === '' || isset($optedOut[$key]) || isset($onCooldown[$key])) {
                continue;
            }
            if (!isset($byPhone[$key])) {
                $byPhone[$key] = ['phone' => $d->phone, 'name' => $d->name, 'lines' => []];
            }
            $byPhone[$key]['lines'][] = $d;
        }

        $sent = 0;
        foreach ($byPhone as $key => $row) {
            $first = $row['lines'][0];

            $components = [[
                'type' => 'body',
                'parameters' => array_map(
                    fn($v) => ['type' => 'text', 'text' => $v],
                    [$row['name'] ?: 'there', $storeName, $first->label]
                ),
            ]];

            try {
                $res = $wa->sendTemplate(
                    $row['phone'],
                    self::TEMPLATE,
                    WhatsAppService::templateLanguage($storeId, self::TEMPLATE),
                    $components,
                    'service recall'
                );
            } catch (\Throwable $e) {
                Log::warning('Service recall send failed: ' . $e->getMessage());
                continue;
            }

            if (empty($res['success'])) {
                continue;
            }

            self::markReminded($storeId, $key, $row['lines']);
            $sent++;
        }

        return $sent;
    }

    /** Record the chase, on the same table the retail and hospital sweeps share. */
    protected static function markReminded(int $storeId, string $phoneKey, array $lines): void
    {
        foreach ($lines as $line) {
            DB::table('wa_repeat_reminders')->updateOrInsert(
                [
                    'store_id'      => $storeId,
                    'customer_type' => 'service',
                    'customer_id'   => $line->user_id,
                    'ref_key'       => $line->ref_key,
                ],
                [
                    'phone_key'      => $phoneKey,
                    'label'          => mb_substr($line->label, 0, 120),
                    'last_bought_at' => $line->last_done,
                    'reminded_at'    => now(),
                    'updated_at'     => now(),
                    'created_at'     => now(),
                ]
            );
        }
    }
}
