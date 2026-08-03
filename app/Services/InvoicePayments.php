<?php

namespace App\Services;

use App\CentralLogics\Helpers;
use App\Models\ManualInvoice;
use App\Models\ServiceInvoice;
use App\Models\Store;
use App\Models\StoreLedgerEntry;
use App\Models\StoreVoucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Mpdf\Mpdf;

/**
 * Money received against a bill, one payment at a time.
 *
 * A bill used to be a switch — Unpaid or Paid, nothing in between — so a customer who handed over
 * half of it had to be recorded as either lying about the rest or having paid it. Every payment is
 * now a row here, the invoice's status is derived from the sum of those rows, and each one produces
 * a numbered receipt the customer gets on WhatsApp.
 *
 * The payments table is the only source of truth for how much has been collected. cash_amount /
 * online_amount / payment_status on the invoice are recomputed from it after every payment, never
 * incremented in place — an invoice re-saved twice must not end up showing twice the money.
 */
class InvoicePayments
{
    /** Approved template the receipt rides on. Its header is a DOCUMENT — the receipt PDF. */
    const TEMPLATE = 'payment_receipt';

    /** Where receipt PDFs live on the public disk (a DO Spaces mount). */
    const PDF_DIR = 'receipt';

    /** Rounding slack. Anything inside this is the same number as far as a bill is concerned. */
    const EPSILON = 0.009;

    public static function ensureTable(): void
    {
        if (Schema::hasTable('invoice_payments')) {
            return;
        }

        // invoice_type + invoice_id, not a plain FK: manual_invoices and service_invoices are two
        // tables whose ids both count from 1, so the type is half of the identity.
        //
        // balance_after is stored rather than derived because it is printed on the receipt the
        // customer keeps — what the bill said at that moment must not change when a later payment
        // lands, or an edit to the invoice total rewrites history the customer is holding.
        DB::statement("CREATE TABLE `invoice_payments` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `store_id` BIGINT NOT NULL,
            `invoice_type` VARCHAR(10) NOT NULL DEFAULT 'manual',
            `invoice_id` BIGINT NOT NULL,
            `invoice_number` VARCHAR(100) NULL,
            `receipt_no` VARCHAR(100) NOT NULL,
            `amount` DECIMAL(14,2) NOT NULL DEFAULT 0,
            `cash_amount` DECIMAL(14,2) NOT NULL DEFAULT 0,
            `online_amount` DECIMAL(14,2) NOT NULL DEFAULT 0,
            `payment_mode` VARCHAR(30) NOT NULL DEFAULT 'Cash',
            `payment_date` DATE NULL,
            `reference` VARCHAR(190) NULL,
            `note` VARCHAR(255) NULL,
            `balance_after` DECIMAL(14,2) NOT NULL DEFAULT 0,
            `pdf` VARCHAR(190) NULL,
            `wa_status` VARCHAR(20) NULL,
            `wa_error` VARCHAR(255) NULL,
            `created_by` BIGINT NULL,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `ip_receipt` (`store_id`, `receipt_no`),
            KEY `ip_invoice` (`invoice_type`, `invoice_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /** 'manual' | 'service' — anything else is a manual invoice, which is what most callers mean. */
    public static function normalizeType(?string $type): string
    {
        return $type === 'service' ? 'service' : 'manual';
    }

    public static function find(string $type, $id)
    {
        return self::normalizeType($type) === 'service'
            ? ServiceInvoice::find($id)
            : ManualInvoice::find($id);
    }

    /** How much has actually been collected against one invoice. */
    public static function paidFor(string $type, int $invoiceId): float
    {
        self::ensureTable();

        return round((float) DB::table('invoice_payments')
            ->where('invoice_type', self::normalizeType($type))
            ->where('invoice_id', $invoiceId)
            ->sum('amount'), 2);
    }

    /**
     * Collected totals for a list of invoices, keyed by "type:id".
     *
     * One query for a whole invoice list — the alternative is a sum per row, and the billing list
     * routinely runs to hundreds of rows.
     */
    public static function paidForMany($invoices): array
    {
        self::ensureTable();

        $ids = collect($invoices)->pluck('id')->filter()->unique()->values()->all();
        if (empty($ids)) {
            return [];
        }

        return DB::table('invoice_payments')
            ->whereIn('invoice_id', $ids)
            ->groupBy('invoice_type', 'invoice_id')
            ->selectRaw('invoice_type, invoice_id, SUM(amount) as paid')
            ->get()
            ->mapWithKeys(fn($r) => [$r->invoice_type . ':' . $r->invoice_id => round((float) $r->paid, 2)])
            ->all();
    }

    /** What is still owed on this invoice, never below zero. */
    public static function balanceOf($invoice, string $type): float
    {
        $total = round((float) $invoice->total_amount, 2);

        if (strtolower((string) $invoice->payment_status) === 'paid') {
            return 0.0;
        }

        return max(0, round($total - self::paidFor($type, (int) $invoice->id), 2));
    }
 
    /** Every receipt raised against an invoice, oldest first. */
    public static function receiptsFor(string $type, int $invoiceId)
    {
        try {
            self::ensureTable();

            $type = self::normalizeType($type);

            // Lazy retry: if any earlier receipt failed its PDF build, try once more now that the
            // invoice is being viewed/rendered again — the transient cause may have cleared.
            self::regenerateMissingPdfs($type, $invoiceId);

            return DB::table('invoice_payments')
                ->where('invoice_type', $type)
                ->where('invoice_id', $invoiceId)
                ->orderBy('id')
                ->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * Record one payment against an invoice: settle the books, raise a numbered receipt, WhatsApp
     * it to the customer.
     *
     * Returns ['success' => bool, 'message' => string, 'payment' => object|null, 'wa' => string|null].
     * A refusal (nothing owed, more than the balance, no amount) comes back as success = false with
     * nothing written; everything after the payment row is written is best-effort and never undoes it.
     */
    public static function record($invoice, string $type, array $input): array
    {
        $type    = self::normalizeType($type);
        $storeId = (int) $invoice->vendor_id;
        $total   = round((float) $invoice->total_amount, 2);

        self::ensureTable();

        $alreadyPaid = self::paidFor($type, (int) $invoice->id);
        $balance     = round($total - $alreadyPaid, 2);

        if (strtolower((string) $invoice->payment_status) === 'paid' || $balance <= self::EPSILON) {
            return ['success' => false, 'message' => 'This invoice is already fully paid.', 'payment' => null, 'wa' => null];
        }

        $mode = in_array($input['payment_mode'] ?? '', ['Cash', 'Online', 'Cash and Online'], true)
            ? $input['payment_mode']
            : 'Cash';

        // A split payment is defined by its two legs; a single-mode payment by the one amount box.
        // Deriving the total from whichever the vendor actually filled in is what stops "Both" plus
        // an untouched amount field from recording zero.
        if ($mode === 'Cash and Online') {
            $cash   = round(max(0, (float) ($input['cash_amount'] ?? 0)), 2);
            $online = round(max(0, (float) ($input['online_amount'] ?? 0)), 2);
            $amount = round($cash + $online, 2);
        } else {
            $amount = round(max(0, (float) ($input['amount'] ?? 0)), 2);
            $cash   = $mode === 'Cash' ? $amount : 0.0;
            $online = $mode === 'Online' ? $amount : 0.0;
        }

        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Enter how much was received.', 'payment' => null, 'wa' => null];
        }

        if ($amount - $balance > self::EPSILON) {
            return [
                'success' => false,
                'message' => 'That is more than the ' . _price($balance) . ' still due on this invoice.',
                'payment' => null,
                'wa'      => null,
            ];
        }

        $balanceAfter = max(0, round($balance - $amount, 2));
        $settled      = $balanceAfter <= self::EPSILON;

        $paymentId = self::insertPayment($storeId, $invoice, $type, [
            'amount'        => $amount,
            'cash'          => $cash,
            'online'        => $online,
            'mode'          => $mode,
            'date'          => $input['payment_date'] ?? date('Y-m-d'),
            'reference'     => $input['reference'] ?? null,
            'note'          => $input['note'] ?? null,
            'balance_after' => $balanceAfter,
        ]);

        self::applyToInvoice($invoice, $type, $settled);

        $payment = DB::table('invoice_payments')->find($paymentId);

        // The bill itself now carries a payment history, so it has to be re-rendered — otherwise
        // the PDF the customer downloads still claims the whole amount is outstanding.
        try {
            $data = _createBillPdf($invoice, 'vendor');
            $invoice->update(['pdf' => $data['pdf']]);
        } catch (\Throwable $e) {
            Log::warning('Bill PDF rebuild after payment failed: ' . $e->getMessage());
        }

        $pdf = self::buildReceiptPdf($payment, $invoice, $type);
        if ($pdf) {
            DB::table('invoice_payments')->where('id', $paymentId)
                ->update(['pdf' => $pdf['name'], 'updated_at' => now()]);
            $payment->pdf = $pdf['name'];
        }

        $wa = self::sendReceipt($payment, $invoice, $type, $pdf['url'] ?? null);

        DB::table('invoice_payments')->where('id', $paymentId)->update([
            'wa_status' => $wa['status'],
            'wa_error'  => $wa['error'] ? mb_substr($wa['error'], 0, 255) : null,
            'updated_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => $settled
                ? 'Payment of ' . _price($amount) . ' recorded — this invoice is fully paid. Receipt ' . $payment->receipt_no . ' raised.'
                : 'Payment of ' . _price($amount) . ' recorded. ' . _price($balanceAfter) . ' still due. Receipt ' . $payment->receipt_no . ' raised.',
            'payment' => $payment,
            'wa'      => $wa['message'],
        ];
    }

    /**
     * Write the payment row, retrying on a clashing receipt number.
     *
     * Two staff taking money at the same counter can generate the same next number; the unique key
     * is what catches it, and a retry is cheaper than serialising every payment in the business.
     */
    protected static function insertPayment(int $storeId, $invoice, string $type, array $p): int
    {
        $userId = auth('vendor_employee')->id() ?? auth('vendor')->id();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return (int) DB::table('invoice_payments')->insertGetId([
                    'store_id'       => $storeId,
                    'invoice_type'   => $type,
                    'invoice_id'     => (int) $invoice->id,
                    'invoice_number' => mb_substr((string) $invoice->invoice_id, 0, 100),
                    'receipt_no'     => self::nextReceiptNo($storeId),
                    'amount'         => $p['amount'],
                    'cash_amount'    => $p['cash'],
                    'online_amount'  => $p['online'],
                    'payment_mode'   => $p['mode'],
                    'payment_date'   => $p['date'],
                    'reference'      => $p['reference'] ? mb_substr((string) $p['reference'], 0, 190) : null,
                    'note'           => $p['note'] ? mb_substr((string) $p['note'], 0, 255) : null,
                    'balance_after'  => $p['balance_after'],
                    'created_by'     => $userId,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            } catch (\Throwable $e) {
                if ($attempt === 4) {
                    throw $e;
                }
                usleep(50000);
            }
        }

        throw new \RuntimeException('Could not record the payment.');
    }

    /**
     * Recompute the invoice's payment fields from the payments table, and settle the books when
     * the last rupee lands.
     *
     * payment_status stays 'Unpaid' while anything is outstanding, deliberately: every reminder,
     * report and filter in the system reads that column as a two-state flag, and inventing a third
     * value here would quietly drop partly-paid bills out of all of them. What has been collected
     * is on the payments table for anything that wants it.
     */
    protected static function applyToInvoice($invoice, string $type, bool $settled): void
    {
        $sums = DB::table('invoice_payments')
            ->where('invoice_type', $type)
            ->where('invoice_id', (int) $invoice->id)
            ->selectRaw('SUM(cash_amount) as cash, SUM(online_amount) as online')
            ->first();

        $cash   = round((float) ($sums->cash ?? 0), 2);
        $online = round((float) ($sums->online ?? 0), 2);

        $invoice->cash_amount   = $cash;
        $invoice->online_amount = $online;
        $invoice->payment_method = ($cash > 0 && $online > 0)
            ? 'Cash and Online'
            : ($online > 0 ? 'Online' : 'Cash');

        if ($settled) {
            $invoice->payment_status = 'Paid';
            $invoice->payment_date   = date('Y-m-d');
        }

        $invoice->save();

        if ($settled) {
            self::approveVoucher($invoice, $type);
        }
    }

    /**
     * Approve the accounting voucher behind the invoice, once it is actually paid in full.
     *
     * A part payment deliberately leaves the voucher pending — the books should not show the whole
     * receivable cleared because a customer paid a third of it.
     */
    protected static function approveVoucher($invoice, string $type): void
    {
        try {
            $voucher = StoreVoucher::where('invoice_id', $type . '-' . $invoice->id)->first();
            if (!$voucher) {
                return;
            }

            $voucher->status = 'approved';
            $voucher->completed_at = now();
            $voucher->save();

            foreach (StoreLedgerEntry::where('voucher_id', $voucher->id)->get() as $entry) {
                $entry->status = 'approved';
                $entry->completed_at = now();
                $entry->save();
            }
        } catch (\Throwable $e) {
            Log::warning('Voucher approval on payment failed: ' . $e->getMessage());
        }
    }

    /** {PREFIX}_RCPT_{FY}_{n} — the same shape as an invoice number, so the two read as a pair. */
    protected static function nextReceiptNo(int $storeId): string
    {
        $store  = Store::find($storeId);
        $prefix = $store ? substr(strtoupper(preg_replace('/[^A-Za-z]/', '', $store->name)), 0, 3) : 'RCP';
        $prefix = $prefix !== '' ? $prefix : 'RCP';
        $fy     = _currentFinancialYear();

        $serial = (int) DB::table('invoice_payments')
            ->where('store_id', $storeId)
            ->where('receipt_no', 'like', $prefix . '_RCPT_' . $fy . '_%')
            ->count() + 1;

        do {
            $receiptNo = $prefix . '_RCPT_' . $fy . '_' . $serial;
            $exists = DB::table('invoice_payments')
                ->where('store_id', $storeId)->where('receipt_no', $receiptNo)->exists();
            $serial++;
        } while ($exists);

        return $receiptNo;
    }

    /**
     * Render the receipt to a PDF on the public disk.
     *
     * The filename is a random token because Meta fetches the file itself when the message goes out
     * — there is no session to put in front of it, so the name is the whole of its privacy.
     */
    protected static function buildReceiptPdf($payment, $invoice, string $type): ?array
    {
        try {
            $store    = DB::table('stores')->where('id', $payment->store_id)->first();
            $customer = self::billTo($invoice);
            $receipts = self::receiptsFor($type, (int) $invoice->id);

            $html = view('invoice_template.payment_receipt', [
                'payment'  => $payment,
                'invoice'  => $invoice,
                'store'    => $store,
                'customer' => $customer,
                'receipts' => $receipts,
                'paid'     => round((float) $receipts->sum('amount'), 2),
            ])->render();

            $temp = storage_path('app/mpdf_temp');
            if (!is_dir($temp)) {
                mkdir($temp, 0775, true);
            }

            $mpdf = new Mpdf([
                'mode'          => 'utf-8',
                'format'        => 'A5-L',
                'tempDir'       => $temp,
                'margin_left'   => 10,
                'margin_right'  => 10,
                'margin_top'    => 10,
                'margin_bottom' => 10,
            ]);
            $mpdf->WriteHTML($html);

            $name = 'receipt_' . bin2hex(random_bytes(16)) . '.pdf';
            Helpers::savePdfToPublic($mpdf, self::PDF_DIR, $name);

            return [
                'name' => $name,
                'url'  => asset('storage/app/public/' . self::PDF_DIR) . '/' . $name,
            ];
        } catch (\Throwable $e) {
            Log::error('Receipt PDF build failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Retry PDF generation for any receipts on this invoice whose pdf column is still NULL.
     *
     * Called lazily from receiptsFor() so the fix happens the next time somebody views the bill —
     * no separate cron or manual action required. Each receipt gets exactly one retry per page
     * render; if it fails again the row stays as-is and will try once more next time.
     */
    protected static function regenerateMissingPdfs(string $type, int $invoiceId): void
    {
        try {
            $missing = DB::table('invoice_payments')
                ->where('invoice_type', $type)
                ->where('invoice_id', $invoiceId)
                ->whereNull('pdf')
                ->get();

            if ($missing->isEmpty()) {
                return;
            }

            $invoice = self::find($type, $invoiceId);
            if (!$invoice) {
                return;
            }

            foreach ($missing as $payment) {
                try {
                    $pdf = self::buildReceiptPdf($payment, $invoice, $type);
                    if ($pdf) {
                        DB::table('invoice_payments')->where('id', $payment->id)
                            ->update(['pdf' => $pdf['name'], 'updated_at' => now()]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Receipt PDF retry failed for payment #' . $payment->id . ': ' . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            // Never let a retry break the page that triggered it.
            Log::warning('regenerateMissingPdfs failed: ' . $e->getMessage());
        }
    }

    /** Public URL of a stored receipt PDF. */
    public static function receiptUrl(?string $pdf): ?string
    {
        return $pdf ? asset('storage/app/public/' . self::PDF_DIR) . '/' . $pdf : null;
    }

    /**
     * Send the receipt to whoever the bill was raised against.
     *
     * Best-effort throughout: a WhatsApp problem must never look like the payment failed, so every
     * refusal comes back as a message the vendor can read and the payment stands regardless.
     */
    protected static function sendReceipt($payment, $invoice, string $type, ?string $pdfUrl): array
    {
        // A standing condition — WhatsApp not connected, the toggle deliberately off — is not news
        // to anybody, and telling the vendor about it on every single payment is nagging. Only
        // things they can fix on this bill get a message.
        $quiet = fn() => ['status' => 'skipped', 'message' => null, 'error' => null];
        $skip = fn(string $why) => ['status' => 'skipped', 'message' => $why, 'error' => null];

        try {
            $storeId = (int) $payment->store_id;

            if (!NotificationPrefs::enabled($storeId, 'whatsapp_send', 'payment_receipt')) {
                return $quiet();
            }

            $wa = WhatsAppService::make($storeId);
            if ($wa->source() !== 'vendor' || !WhatsAppBilling::isActive($storeId)) {
                return $quiet();
            }

            $customer = self::billTo($invoice);
            $phone = trim((string) ($customer['phone'] ?? ''));
            if (strlen(preg_replace('/[^0-9]/', '', $phone) ?? '') < 10) {
                return $skip('Receipt not sent — this invoice has no customer phone number on it.');
            }

            if (!$pdfUrl) {
                return $skip('Receipt not sent — the receipt PDF could not be generated.');
            }

            $balance = round((float) $payment->balance_after, 2);
            $storeName = DB::table('stores')->where('id', $storeId)->value('name') ?: 'our store';

            $components = [
                [
                    'type' => 'header',
                    'parameters' => [[
                        'type' => 'document',
                        'document' => [
                            'link'     => $pdfUrl,
                            'filename' => preg_replace('/[^A-Za-z0-9\-_]/', '', $payment->receipt_no) . '.pdf',
                        ],
                    ]],
                ],
                [
                    'type' => 'body',
                    'parameters' => array_map(fn($v) => ['type' => 'text', 'text' => self::sanitize((string) $v)], [
                        $customer['name'] ?: 'there',
                        _price($payment->amount),
                        (string) $invoice->invoice_id,
                        $storeName,
                        $balance > self::EPSILON
                            ? 'Balance still due: ' . _price($balance) . '.'
                            : 'This bill is now fully paid.',
                    ]),
                ],
            ];

            $res = $wa->sendTemplate($phone, self::TEMPLATE, 'en_US', $components, 'payment receipt');

            if (empty($res['success'])) {
                $error = (string) ($res['error'] ?? 'WhatsApp refused the message.');
                if (stripos($error, 'template') !== false) {
                    $error .= ' Create the "' . self::TEMPLATE . '" template under WhatsApp → Message Templates '
                        . '(it is in the suggested list) and wait for Meta to approve it.';
                }
                return ['status' => 'failed', 'message' => 'Receipt not sent — ' . $error, 'error' => $error];
            }

            return [
                'status'  => 'sent',
                'message' => 'Receipt sent on WhatsApp to ' . self::maskPhone($phone) . '.',
                'error'   => null,
            ];
        } catch (\Throwable $e) {
            Log::error('Receipt WhatsApp send failed: ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'Receipt could not be sent on WhatsApp.', 'error' => $e->getMessage()];
        }
    }

    /** Name, phone and address of whoever the invoice was billed to. */
    public static function billTo($invoice): array
    {
        try {
            $party = $invoice->user;
            if (!$party) {
                return ['name' => '', 'phone' => '', 'address' => ''];
            }

            // A patient carries a single `name`; every other party is split into f_name / l_name.
            $name = trim((string) ($party->name ?? '')) !== ''
                ? trim((string) $party->name)
                : trim(($party->f_name ?? '') . ' ' . ($party->l_name ?? ''));

            return [
                'name'    => $name,
                'phone'   => trim((string) ($party->phone ?? '')),
                'address' => trim((string) ($party->address ?? '')),
            ];
        } catch (\Throwable $e) {
            return ['name' => '', 'phone' => '', 'address' => ''];
        }
    }

    /** Meta rejects newlines and long runs of spaces inside a template parameter. */
    protected static function sanitize(string $value): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
        return $value === '' ? '-' : mb_substr($value, 0, 500);
    }

    protected static function maskPhone(?string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $phone) ?? '';
        return strlen($digits) < 4 ? '••••' : '••••••' . substr($digits, -4);
    }
}
