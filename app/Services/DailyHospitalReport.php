<?php

namespace App\Services;

use App\CentralLogics\Helpers;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The numbers a hospital owner wants at the end of the day, in one message.
 *
 * Every figure is read from the same table the matching screen reads, so the report and the
 * screen can never disagree — a summary that quietly uses its own definition of "income" is
 * worse than no summary, because it gets believed.
 */
class DailyHospitalReport
{
    /**
     * What can appear in the report, in the order it is shown.
     *
     * Keys are stored in the store's config, so renaming one silently drops it from every
     * hospital that had ticked it — add, never rename.
     */
    public const METRICS = [
        'leads'      => 'New enquiries',
        'patients'   => 'New patients',
        'opd'        => 'OPD visits',
        'lab'        => 'Lab income',
        'radiology'  => 'Radiology income',
        'pharmacy'   => 'Pharmacy income',
        'income'     => 'Total income',
        'whatsapp'   => 'WhatsApp chats',
    ];

    /** Ticked for a hospital that has never opened the settings block. */
    public const DEFAULT_METRICS = ['leads', 'patients', 'lab', 'radiology', 'income', 'whatsapp'];

    public function __construct(
        private int $storeId,
        private ?Carbon $date = null
    ) {
        $this->date = $date ?: now();
    }

    public static function for(int $storeId, ?Carbon $date = null): self
    {
        return new self($storeId, $date);
    }

    /**
     * @param  array<string>|null  $metrics  Which figures to compute; null = the defaults.
     * @return array<string, array{label: string, value: string, raw: float}>
     */
    public function build(?array $metrics = null): array
    {
        $metrics = $metrics ?: self::DEFAULT_METRICS;
        $out     = [];

        foreach (self::METRICS as $key => $label) {
            if (!in_array($key, $metrics, true)) {
                continue;
            }

            $raw   = $this->value($key);
            $money = in_array($key, ['lab', 'radiology', 'pharmacy', 'income'], true);

            $out[$key] = [
                'label' => $label,
                'raw'   => $raw,
                'value' => $money ? Helpers::format_currency($raw) : number_format($raw),
            ];
        }

        return $out;
    }

    /** Every figure in the report is zero — nothing happened worth waking someone for. */
    public function isQuiet(array $built): bool
    {
        foreach ($built as $row) {
            if ((float) $row['raw'] > 0) {
                return false;
            }
        }

        return true;
    }

    private function value(string $key): float
    {
        try {
            return match ($key) {
                'leads'     => $this->leads(),
                'patients'  => $this->newPatients(),
                'opd'       => $this->opdVisits(),
                'lab'       => $this->labIncome(),
                'radiology' => $this->radiologyIncome(),
                'pharmacy'  => $this->pharmacyIncome(),
                'income'    => $this->labIncome() + $this->radiologyIncome() + $this->pharmacyIncome()
                                + $this->consultationIncome() + $this->hospitalBillIncome(),
                'whatsapp'  => $this->whatsappChats(),
                default     => 0.0,
            };
        } catch (\Throwable $e) {
            // A module this hospital does not use may have no table at all. A missing figure is
            // reported as zero rather than failing the whole report.
            report($e);
            return 0.0;
        }
    }

    private function day(): string
    {
        return $this->date->toDateString();
    }

    /** Enquiries sent to this store today — the same FIND_IN_SET the leads screen uses. */
    private function leads(): float
    {
        if (!Schema::hasTable('service_requests')) {
            return 0.0;
        }

        return (float) DB::table('service_requests')
            ->whereRaw('FIND_IN_SET(?, sent_to)', [$this->storeId])
            ->whereDate('created_at', $this->day())
            ->count();
    }

    private function newPatients(): float
    {
        if (!Schema::hasTable('patients')) {
            return 0.0;
        }

        return (float) DB::table('patients')
            ->where('store_id', $this->storeId)
            ->whereDate('created_at', $this->day())
            ->count();
    }

    private function opdVisits(): float
    {
        if (!Schema::hasTable('opd_visits')) {
            return 0.0;
        }

        return (float) DB::table('opd_visits')
            ->where('store_id', $this->storeId)
            ->whereDate('visit_date', $this->day())
            ->where(fn($q) => $q->whereNull('status')->orWhere('status', '!=', 'cancelled'))
            ->count();
    }

    /** What the patient actually pays: payable, not what was billed before insurance. */
    private function labIncome(): float
    {
        if (!Schema::hasTable('lab_invoices')) {
            return 0.0;
        }

        return (float) DB::table('lab_invoices')
            ->where('store_id', $this->storeId)
            ->whereDate('created_at', $this->day())
            ->sum('payable');
    }

    private function radiologyIncome(): float
    {
        if (!Schema::hasTable('radiology_invoices')) {
            return 0.0;
        }

        return (float) DB::table('radiology_invoices')
            ->where('store_id', $this->storeId)
            ->whereDate('created_at', $this->day())
            ->sum('payable');
    }

    /**
     * Over-the-counter pharmacy sales. A walk-in sale is a manual invoice billed to 'walkin'
     * (see BasicPharmacyController::walkinStore), so it never double-counts with the patient
     * bills read by hospitalBillIncome().
     */
    private function pharmacyIncome(): float
    {
        if (!Schema::hasTable('manual_invoices')) {
            return 0.0;
        }

        return (float) DB::table('manual_invoices')
            ->where('vendor_id', $this->storeId)
            ->where('bill_to_type', 'walkin')
            ->whereDate('invoice_date', $this->day())
            ->sum('total_amount');
    }

    private function consultationIncome(): float
    {
        if (!Schema::hasTable('opd_consultation_receipts')) {
            return 0.0;
        }

        return (float) DB::table('opd_consultation_receipts')
            ->where('store_id', $this->storeId)
            ->whereDate('receipt_date', $this->day())
            ->sum('paid');
    }

    /**
     * Hospital bills raised today. manual_invoices.vendor_id holds the STORE id, and only bills
     * made out to a patient belong to the hospital's own takings.
     */
    private function hospitalBillIncome(): float
    {
        if (!Schema::hasTable('manual_invoices')) {
            return 0.0;
        }

        return (float) DB::table('manual_invoices')
            ->where('vendor_id', $this->storeId)
            ->where('bill_to_type', 'patient')
            ->whereDate('invoice_date', $this->day())
            ->sum('total_amount');
    }

    /** People the hospital exchanged WhatsApp messages with today, not message count. */
    private function whatsappChats(): float
    {
        if (!Schema::hasTable('whatsapp_messages')) {
            return 0.0;
        }

        $column = Schema::hasColumn('whatsapp_messages', 'contact_phone') ? 'contact_phone'
            : (Schema::hasColumn('whatsapp_messages', 'wa_id') ? 'wa_id' : null);

        if (!$column) {
            return 0.0;
        }

        return (float) DB::table('whatsapp_messages')
            ->where('store_id', $this->storeId)
            ->whereDate('created_at', $this->day())
            ->distinct()
            ->count($column);
    }
}
