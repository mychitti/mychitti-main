<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * The bill itself, sent to the customer on WhatsApp the moment it is raised.
 *
 * Distinct from InvoicePayments, which sends a receipt after money has changed hands. One is the
 * ask and the other the acknowledgement, and a customer who gets both should be able to tell which
 * is which — hence a separate template rather than a re-worded receipt.
 *
 * A media template, because the bill IS the attachment: a WhatsApp message stating an amount, with
 * nothing the customer can save, open or show at a counter, is not a bill. The PDF _createBillPdf()
 * already wrote to the public disk is what gets attached — Meta fetches the link itself with no
 * session, which is the same exposure the vendor panel's own download link already carries.
 *
 * Best-effort throughout and never throws: a WhatsApp problem must never look like the bill failed
 * to save, so every refusal comes back as a message and the invoice stands regardless.
 */
class InvoiceShare
{
    /** Approved template the bill rides on. Its header is a DOCUMENT — the bill PDF. */
    const TEMPLATE = 'invoice_ready';

    /** Send Notifications toggle governing this. Default OFF — see NotificationPrefs. */
    const PREF = 'invoice_ready';

    /** Where _createBillPdf() puts a vendor bill on the public disk. */
    const PDF_DIR = 'invoice';

    /**
     * One row per invoice whose bill has been sent. The unique key IS the lock: a double-submitted
     * form, a retried request, or a screen that later learns to re-send all try to insert the same
     * row, and only the first of them messages the customer.
     */
    public static function ensureTable(): void
    {
        if (Schema::hasTable('wa_invoice_sends')) {
            return;
        }

        // invoice_type + invoice_id, not a plain FK: manual_invoices and service_invoices are two
        // tables whose ids both count from 1, so the type is half of the identity.
        DB::statement("CREATE TABLE `wa_invoice_sends` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `store_id` BIGINT NOT NULL,
            `invoice_type` VARCHAR(10) NOT NULL DEFAULT 'manual',
            `invoice_id` BIGINT NOT NULL,
            `invoice_number` VARCHAR(100) NULL,
            `sent_to` VARCHAR(32) NULL,
            `status` VARCHAR(20) NULL,
            `error` VARCHAR(255) NULL,
            `created_at` TIMESTAMP NULL,
            `updated_at` TIMESTAMP NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `wais_once` (`store_id`, `invoice_type`, `invoice_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * Send this bill, if the store asked for it and has not already had it sent.
     *
     * $pdfUrl is what _createBillPdf() just returned; when it is omitted the invoice's stored
     * filename is used instead, so a caller that only has the model still works.
     *
     * Returns ['status' => 'sent'|'skipped'|'failed', 'message' => ?string]. A null message means
     * a standing condition the vendor already knows about — WhatsApp not connected, the toggle
     * deliberately off — which is not worth interrupting them about on every bill they raise.
     */
    public static function auto($invoice, string $type = 'manual', ?string $pdfUrl = null): array
    {
        $quiet = ['status' => 'skipped', 'message' => null];

        try {
            $type    = InvoicePayments::normalizeType($type);
            $storeId = (int) $invoice->vendor_id;
            if (!$storeId || !$invoice->id) {
                return $quiet;
            }

            // Each of these used to return in silence. The bill still stands either way — but the
            // vendor now has somewhere to read why their customer never got it.
            $note = fn(string $reason) => MessageLog::skipped($storeId, $reason, [
                'key'         => self::PREF,
                'label'       => 'Bill on WhatsApp',
                'template'    => self::TEMPLATE,
                'record_type' => 'invoice',
                'record_id'   => (int) $invoice->id,
            ]);

            if (!NotificationPrefs::enabled($storeId, 'whatsapp_send', self::PREF)) {
                $note('"Bill on WhatsApp" is turned off under Send Notifications.');
                return $quiet;
            }

            $wa = WhatsAppService::make($storeId);
            if ($wa->source() !== 'vendor') {
                $note('Your own WhatsApp number is not connected.');
                return $quiet;
            }
            if (!WhatsAppBilling::isActive($storeId)) {
                $note('Your WhatsApp subscription is not active.');
                return $quiet;
            }

            // Nothing can send on a template Meta has not approved. Attempting it anyway would
            // fail, be billed regardless (charges are taken at dispatch), and spend the claim row
            // below — so this bill could never be sent again once approval came through.
            if (!WhatsAppService::templateApproved($storeId, self::TEMPLATE)) {
                Log::info('Bill WhatsApp skipped — template not approved', [
                    'store' => $storeId, 'template' => self::TEMPLATE,
                ]);
                $note('The "' . self::TEMPLATE . '" template is not approved on your WhatsApp account yet.');
                return $quiet;
            }

            $customer = InvoicePayments::billTo($invoice);
            $phone    = trim((string) ($customer['phone'] ?? ''));
            if (strlen(preg_replace('/[^0-9]/', '', $phone) ?? '') < 10) {
                $note('This customer has no phone number on file.');
                return ['status' => 'skipped', 'message' => 'Bill not sent on WhatsApp — this customer has no phone number on file.'];
            }

            $pdfUrl = $pdfUrl ?: self::pdfUrl($invoice->pdf ?? null);
            if (!$pdfUrl) {
                $note('The bill PDF could not be generated.');
                return ['status' => 'skipped', 'message' => 'Bill not sent on WhatsApp — the bill PDF could not be generated.'];
            }

            self::ensureTable();

            // Claim it before sending. A duplicate key means this bill has already gone out.
            try {
                DB::table('wa_invoice_sends')->insert([
                    'store_id'       => $storeId,
                    'invoice_type'   => $type,
                    'invoice_id'     => (int) $invoice->id,
                    'invoice_number' => mb_substr((string) $invoice->invoice_id, 0, 100),
                    'sent_to'        => mb_substr($phone, 0, 32),
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            } catch (\Throwable $e) {
                return $quiet;
            }

            $result = self::send($wa, $invoice, $type, $storeId, $customer, $phone, $pdfUrl);

            DB::table('wa_invoice_sends')
                ->where('store_id', $storeId)->where('invoice_type', $type)->where('invoice_id', (int) $invoice->id)
                ->update([
                    'status'     => $result['status'],
                    'error'      => $result['error'] ? mb_substr($result['error'], 0, 255) : null,
                    'updated_at' => now(),
                ]);

            MessageLog::record($storeId, $result['status'] === 'sent' ? MessageLog::SENT : MessageLog::FAILED, [
                'key'         => self::PREF,
                'label'       => 'Bill on WhatsApp',
                'template'    => self::TEMPLATE,
                'recipient'   => $customer['name'] ?? null,
                'to'          => $phone,
                'record_type' => 'invoice',
                'record_id'   => (int) $invoice->id,
                'reason'      => $result['error'] ?: null,
            ]);

            return ['status' => $result['status'], 'message' => $result['message']];
        } catch (\Throwable $e) {
            Log::error('Bill WhatsApp send failed: ' . $e->getMessage());
            return ['status' => 'failed', 'message' => 'The bill could not be sent on WhatsApp.'];
        }
    }

    /** Build and dispatch the template message. */
    protected static function send($wa, $invoice, string $type, int $storeId, array $customer, string $phone, string $pdfUrl): array
    {
        $storeName = DB::table('stores')->where('id', $storeId)->value('name') ?: 'our store';
        $balance   = InvoicePayments::balanceOf($invoice, $type);

        $components = [
            [
                'type' => 'header',
                'parameters' => [[
                    'type' => 'document',
                    'document' => [
                        'link'     => $pdfUrl,
                        'filename' => (preg_replace('/[^A-Za-z0-9\-_]/', '', (string) $invoice->invoice_id) ?: 'Invoice') . '.pdf',
                    ],
                ]],
            ],
            [
                'type' => 'body',
                'parameters' => array_map(fn($v) => ['type' => 'text', 'text' => self::sanitize((string) $v)], [
                    $customer['name'] ?: 'there',
                    $storeName,
                    (string) $invoice->invoice_id,
                    _price($invoice->total_amount),
                    // A whole sentence rather than a bare number: "0" on a settled bill reads as a
                    // demand, and an amount with no wording around it reads as a threat.
                    $balance > InvoicePayments::EPSILON
                        ? 'Amount still due: ' . _price($balance) . '.'
                        : 'Payment received in full, thank you.',
                ]),
            ],
        ];

        $tpl = WhatsAppService::roleTemplate($storeId, 'invoice', self::TEMPLATE);
        $res = $wa->sendTemplate($phone, $tpl['name'], $tpl['language'], $components, 'invoice');

        if (empty($res['success'])) {
            $error = (string) ($res['error'] ?? 'WhatsApp refused the message.');
            if (stripos($error, 'template') !== false) {
                $error .= ' Create the "' . $tpl['name'] . '" template under WhatsApp → Message Templates '
                    . '(it is in the suggested list) and wait for Meta to approve it, or point this '
                    . 'message at one of your own under WhatsApp → Automation.';
            }
            return ['status' => 'failed', 'message' => 'Bill not sent on WhatsApp — ' . $error, 'error' => $error];
        }

        return [
            'status'  => 'sent',
            'message' => 'Bill sent on WhatsApp to ' . self::maskPhone($phone) . '.',
            'error'   => null,
        ];
    }

    /** Public URL of a stored bill PDF. */
    public static function pdfUrl(?string $pdf): ?string
    {
        return $pdf ? asset('storage/app/public/' . self::PDF_DIR) . '/' . $pdf : null;
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
