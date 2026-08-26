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
        // Money
        'lab'            => 'Lab income',
        'radiology'      => 'Radiology income',
        'pharmacy'       => 'Pharmacy income',
        'income'         => 'Total income',
        'cash'           => 'Cash collected',
        'online'         => 'Online collected',
        'dues'           => 'Money still owed',
        // Counts
        'leads'          => 'New enquiries',
        'patients'       => 'New patients',
        'opd'            => 'OPD visits',
        'noshow'         => 'No-shows',
        'tomorrow'       => 'Booked for tomorrow',
        'ipd_admissions' => 'Admitted today',
        'ipd_discharges' => 'Discharged today',
        'beds_free'      => 'Beds free now',
        'low_stock'      => 'Medicines to reorder',
        'whatsapp'       => 'WhatsApp chats',
    ];

    /**
     * The figures shown as money — which also decides which ones carry a comparison against
     * yesterday, and which paragraph of the message they land in.
     */
    public const MONEY_METRICS = ['lab', 'radiology', 'pharmacy', 'income', 'cash', 'online', 'dues'];

    /** Ticked for a hospital that has never opened the settings block. */
    public const DEFAULT_METRICS = ['lab', 'radiology', 'income', 'dues', 'leads', 'patients', 'tomorrow', 'whatsapp'];

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
     * @param  bool  $compare  Add a "vs yesterday" movement to the money figures.
     * @return array<string, array{label: string, value: string, raw: float, money: bool, delta: string}>
     */
    public function build(?array $metrics = null, bool $compare = true): array
    {
        $metrics = $metrics ?: self::DEFAULT_METRICS;
        $out     = [];

        // A number on its own is history; a number next to yesterday's is information. Computed
        // once for the whole report rather than per figure.
        $yesterday = null;
        if ($compare) {
            $yesterday = new self($this->storeId, $this->date->copy()->subDay());
        }

        foreach (self::METRICS as $key => $label) {
            if (!in_array($key, $metrics, true)) {
                continue;
            }

            $raw   = $this->value($key);
            $money = in_array($key, self::MONEY_METRICS, true);

            $delta = '';
            // Outstanding dues are a running balance, not a day's takings, so "up on yesterday"
            // would compare two snapshots and read as if today caused it.
            if ($yesterday && $money && $key !== 'dues') {
                $delta = $this->movement($raw, $yesterday->value($key));
            }

            $out[$key] = [
                'label' => $label,
                'raw'   => $raw,
                'money' => $money,
                'delta' => $delta,
                'value' => $money ? Helpers::format_currency($raw) : number_format($raw),
            ];
        }

        return $out;
    }

    /** " (up 3,100)" / " (down 900)" / "" when it did not move or there is nothing to compare. */
    private function movement(float $today, float $before): string
    {
        $diff = round($today - $before, 2);
        if (abs($diff) < 0.01) {
            return '';
        }

        return ' (' . ($diff > 0 ? 'up ' : 'down ') . Helpers::format_currency(abs($diff)) . ' on yesterday)';
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
                'cash'      => $this->collected('cash_amount'),
                'online'    => $this->collected('online_amount'),
                'dues'      => $this->outstanding(),
                'noshow'    => $this->appointments(['no_show'], $this->day()),
                'tomorrow'  => $this->appointments(['scheduled'], $this->date->copy()->addDay()->toDateString()),
                'ipd_admissions' => $this->ipd('admission_date'),
                'ipd_discharges' => $this->ipd('discharge_date'),
                'beds_free'      => $this->bedsFree(),
                'low_stock'      => $this->lowStock(),
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

    /**
     * What actually came in today by that route — cash in the drawer, or online.
     *
     * Read off the invoices themselves rather than a payments ledger, so it matches what the
     * billing screens show for the same day.
     */
    private function collected(string $column): float
    {
        if (!Schema::hasTable('manual_invoices') || !Schema::hasColumn('manual_invoices', $column)) {
            return 0.0;
        }

        return (float) DB::table('manual_invoices')
            ->where('vendor_id', $this->storeId)
            ->whereDate('invoice_date', $this->day())
            ->sum($column);
    }

    /**
     * Everything still unpaid, not just today's — a running balance is the only figure in the
     * report the owner can act on tonight.
     */
    private function outstanding(): float
    {
        if (!Schema::hasTable('manual_invoices')) {
            return 0.0;
        }

        return (float) DB::table('manual_invoices')
            ->where('vendor_id', $this->storeId)
            ->where(fn($q) => $q->whereNull('payment_status')->orWhere('payment_status', '!=', 'Paid'))
            ->sum('total_amount');
    }

    /** @param array<string> $statuses */
    private function appointments(array $statuses, string $date): float
    {
        if (!Schema::hasTable('appointments')) {
            return 0.0;
        }

        return (float) DB::table('appointments')
            ->where('store_id', $this->storeId)
            ->whereDate('appointment_date', $date)
            ->whereIn('status', $statuses)
            ->count();
    }

    private function ipd(string $column): float
    {
        if (!Schema::hasTable('ipd_admissions')) {
            return 0.0;
        }

        return (float) DB::table('ipd_admissions')
            ->where('store_id', $this->storeId)
            ->whereDate($column, $this->day())
            ->count();
    }

    /** A snapshot, not a day's total — how many beds are free at the moment it is sent. */
    private function bedsFree(): float
    {
        if (!Schema::hasTable('beds')) {
            return 0.0;
        }

        return (float) DB::table('beds')
            ->where('store_id', $this->storeId)
            ->where('status', 'available')
            ->count();
    }

    /** Medicines at or below their reorder level, plus anything already out. */
    private function lowStock(): float
    {
        if (!Schema::hasTable('inventory_items') || !Schema::hasColumn('inventory_items', 'reorder_level')) {
            return 0.0;
        }

        return (float) DB::table('inventory_items')
            ->where('store_id', $this->storeId)
            ->where('item_type', 'product')
            ->where(fn($q) => $q
                ->where('stock', '<=', 0)
                ->orWhere(fn($w) => $w->where('reorder_level', '>', 0)->whereColumn('stock', '<=', 'reorder_level')))
            ->count();
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
