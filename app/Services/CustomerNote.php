<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * A note typed by hand and sent to one customer — a doctor's advice after a consultation, a
 * word about an order, anything the platform did not generate itself.
 *
 * WhatsApp allows free text only for 24 hours after the customer last messaged the business.
 * Outside that window nothing free-form is delivered — Meta drops it without an error the sender
 * would notice — so the same note has to travel as an approved template carrying the text as a
 * variable. This picks whichever of the two will actually arrive, which also decides the price:
 * a reply inside the window costs nothing, a template is billed like any other message.
 *
 * Deliberately not tied to a module. It takes a store, a name, a phone and a string, so the
 * hospital's patient screen and every other module's customer screen share one implementation.
 */
class CustomerNote
{
    /** The template a note falls back to when the free-form window is shut. */
    const TEMPLATE = 'advice_note';

    /** How long after a customer's own message free text still reaches them. */
    const WINDOW_HOURS = 24;

    /**
     * Template parameters cannot carry newlines, tabs or long runs of spaces — Meta rejects the
     * whole message — and the body has to leave room for the wording around it.
     */
    const MAX_LENGTH = 700;

    /**
     * Has this person messaged the store recently enough that plain text still reaches them?
     *
     * Read from the inbound log rather than tracked separately: the webhook already writes every
     * received message, and the last one is the only thing that opens the window.
     */
    public static function windowOpen(int $storeId, string $phone): bool
    {
        if (!Schema::hasTable('whatsapp_messages')) {
            return false;
        }

        $normalized = WhatsAppService::make($storeId)->normalizePhone($phone);

        try {
            return DB::table('whatsapp_messages')
                ->where('store_id', $storeId)
                ->where('direction', 'in')
                ->where('recipient', $normalized)
                ->where('sent_at', '>=', now()->subHours(self::WINDOW_HOURS))
                ->exists();
        } catch (\Throwable $e) {
            Log::warning('Note window lookup failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * What sending this note would cost, and why — so the sender is told before they commit
     * rather than discovering it on their bill.
     */
    public static function quote(int $storeId, string $phone): array
    {
        if (self::windowOpen($storeId, $phone)) {
            return [
                'free'   => true,
                'cost'   => 0.0,
                'reason' => 'Free — this customer messaged you in the last 24 hours, so your note goes as a normal reply.',
            ];
        }

        $ready = WhatsAppService::templateApproved($storeId, self::TEMPLATE);

        return [
            'free'   => false,
            'cost'   => WhatsAppBilling::messageCost('own'),
            'ready'  => $ready,
            'reason' => $ready
                ? 'This customer has not messaged recently, so the note is sent on your approved template and is charged as one message.'
                : 'This customer has not messaged recently, so the note needs your "' . self::TEMPLATE . '" template — it is not approved yet.',
        ];
    }

    /**
     * Send the note. Returns ['success' => bool, 'message' => string, 'free' => bool].
     */
    public static function send(int $storeId, ?string $customerName, ?string $phone, ?string $note): array
    {
        $phone = trim((string) $phone);
        $note  = self::tidy($note);

        if ($note === '') {
            return self::fail('Write the note before sending it.');
        }
        if (strlen(preg_replace('/[^0-9]/', '', $phone) ?? '') < 10) {
            return self::fail('This customer has no usable phone number on file.');
        }

        $wa = WhatsAppService::make($storeId);
        if ($wa->source() !== 'vendor') {
            return self::fail('Connect your own WhatsApp number under WhatsApp → Connection before sending notes.');
        }
        if (!WhatsAppBilling::isActive($storeId)) {
            return self::fail('Your WhatsApp subscription isn\'t active. Activate it under WhatsApp → Plan & Billing.');
        }

        // Inside the window a plain reply arrives, reads as a normal message and costs nothing.
        if (self::windowOpen($storeId, $phone)) {
            $res = $wa->sendText($phone, $note, false, 'advice note');
            return empty($res['success'])
                ? self::fail($res['error'] ?? 'WhatsApp would not accept the note.')
                : ['success' => true, 'free' => true, 'message' => 'Note sent.'];
        }

        if (!WhatsAppService::templateApproved($storeId, self::TEMPLATE)) {
            return self::fail(
                'This customer has not messaged you in the last 24 hours, so the note has to go on a template — '
                . 'and your "' . self::TEMPLATE . '" template is not approved yet. Submit it under WhatsApp → Templates.'
            );
        }

        $storeName = DB::table('stores')->where('id', $storeId)->value('name') ?: 'our store';
        $language  = WhatsAppService::templateLanguage($storeId, self::TEMPLATE);

        $components = [[
            'type' => 'body',
            'parameters' => array_map(
                fn($v) => ['type' => 'text', 'text' => $v],
                [trim((string) $customerName) ?: 'there', $storeName, $note]
            ),
        ]];

        $res = $wa->sendTemplate($phone, self::TEMPLATE, $language, $components, 'advice note');

        return empty($res['success'])
            ? self::fail($res['error'] ?? 'WhatsApp would not accept the note.')
            : ['success' => true, 'free' => false, 'message' => 'Note sent.'];
    }

    /**
     * Flatten a typed note into something a template parameter will carry.
     *
     * Meta refuses a parameter containing a newline, a tab or four or more consecutive spaces,
     * and the whole body is capped — so the paragraphs a doctor types become one line here. The
     * same text is sent as-is when it goes free-form, where none of that applies.
     */
    public static function tidy(?string $note): string
    {
        $note = trim(preg_replace('/\s+/u', ' ', (string) $note) ?? '');

        return mb_strlen($note) > self::MAX_LENGTH
            ? rtrim(mb_substr($note, 0, self::MAX_LENGTH - 1)) . '…'
            : $note;
    }

    protected static function fail(string $message): array
    {
        return ['success' => false, 'free' => false, 'message' => $message];
    }
}
