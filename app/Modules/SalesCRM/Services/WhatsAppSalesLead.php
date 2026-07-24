<?php

namespace App\Modules\SalesCRM\Services;

use App\Modules\SalesCRM\Models\QueryActivity;
use App\Modules\SalesCRM\Models\SalesQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Auto-create a Sales Query (Sales & Marketing CRM) when someone messages the MyChitti
 * platform WhatsApp number showing interest in a MyChitti service/plan/onboarding.
 *
 * The platform auto-reply AI decides intent and emits a SALES_LEAD marker; this service
 * parses it and creates the sales_queries row (source=whatsapp) exactly like the admin
 * "Add Sales Query" form, so it appears in the same list with an SQ- ref. Deduped: an open
 * query for the same phone gets a note activity instead of a duplicate.
 */
class WhatsAppSalesLead
{
    const MARKER = 'SALES_LEAD';

    /** Kept in sync with SendAutoReply::ESCALATE_MARKER for the prompt wording. */
    const ESCALATE_HINT = '[[NEEDS_VENDOR]]';

    /** Prompt section appended for the platform auto-reply, teaching the marker protocol. */
    public static function promptSection(): string
    {
        return "\n\nSALES LEAD CAPTURE (this takes priority over the 'pass to the team' rule):\n"
            . "- If the sender shows ANY interest in MyChitti itself — wants to register/list their "
            . "business, become a vendor/partner, asks about plans/pricing/demo, or says they are "
            . "interested in a MyChitti service or product — treat it as a SALES LEAD.\n"
            . "- Reply helpfully and warmly (say our team will reach out to help them get started), "
            . "then append this exact marker at the very end (the sender never sees it):\n"
            . '[[' . self::MARKER . ': {"name":"<their name or empty>","interest":"<what they want, short>","priority":"low|medium|high"}]]' . "\n"
            . "- For a sales lead use the " . self::MARKER . " marker ONLY. Do NOT also use the "
            . self::ESCALATE_HINT . " marker — the sales lead already alerts the team.\n"
            . "- Use priority high for strong buying intent (ready to sign up / urgent), medium for "
            . "general interest, low for vague curiosity.\n"
            . "- Do NOT emit this marker for plain greetings, support questions already answered, or complaints.";
    }

    /**
     * Detect the SALES_LEAD marker in the model's reply. When present, create/append the
     * sales query and return the customer-facing reply with the marker stripped. Returns
     * null when there is no marker (normal reply flow continues untouched).
     */
    public static function extractAndCreate(string $reply, string $phoneKey, string $fromPhone, string $body): ?string
    {
        if (!preg_match('/\[\[' . self::MARKER . ':\s*(\{.*?\})\s*\]\]/s', $reply, $m)) {
            return null;
        }
        $clean = trim(preg_replace('/\[\[' . self::MARKER . ':\s*\{.*?\}\s*\]\]/s', '', $reply) ?? $reply);

        try {
            if (!Schema::hasTable('sales_queries')) {
                return $clean;
            }
            $data = json_decode($m[1], true) ?: [];
            static::createOrAppend($phoneKey, $fromPhone, $data, $body);
        } catch (\Throwable $e) {
            Log::warning('WA sales-lead create failed: ' . $e->getMessage());
        }

        return $clean;
    }

    protected static function createOrAppend(string $phoneKey, string $fromPhone, array $data, string $body): void
    {
        $phone    = '+' . ltrim($fromPhone, '+');
        $interest = trim((string) ($data['interest'] ?? '')) ?: 'Interested (via WhatsApp)';
        $priority = in_array(($data['priority'] ?? ''), SalesQuery::PRIORITIES, true) ? $data['priority'] : 'medium';

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $user = DB::table('users')
                ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), 10) = ?", [$phoneKey])
                ->first(['f_name', 'l_name', 'zone_id']);
            $name = $user ? trim(($user->f_name ?? '') . ' ' . ($user->l_name ?? '')) : '';
        }
        $name = $name ?: 'WhatsApp Lead';

        // Zone from the platform user record, if we know them.
        $zoneId = DB::table('users')
            ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), 10) = ?", [$phoneKey])
            ->value('zone_id');

        // Dedup: reuse an OPEN query for this number rather than piling up duplicates.
        $openStatuses = ['new', 'in_progress', 'proposal_sent', 'on_hold'];
        $existing = SalesQuery::where('source', 'whatsapp')
            ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), 10) = ?", [$phoneKey])
            ->whereIn('status', $openStatuses)
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            static::activity($existing->id, 'note', 'New WhatsApp message: "' . mb_substr($body, 0, 200) . '"');
            return;
        }

        $query = SalesQuery::create([
            'ref_no'       => SalesQuery::generateRef(),
            'contact_name' => $name,
            'phone'        => $phone,
            'zone_id'      => $zoneId ?: null,
            'source'       => 'whatsapp',
            'status'       => 'new',
            'priority'     => $priority,
            'description'  => $interest . "\n\nFirst message: \"" . mb_substr($body, 0, 300) . '"',
        ]);

        static::activity($query->id, 'note', 'Lead auto-created from WhatsApp.');

        // Alert the sales team in the admin panel.
        _inAppNotification(
            'New WhatsApp sales lead: ' . $query->ref_no,
            $name . ' (' . $phone . ') — ' . $interest,
            null,
            null,
            route('admin.sales-crm.query.show', $query->id),
            'admin'
        );

        Log::info('WA sales lead created', ['ref' => $query->ref_no, 'phone' => $phone]);
    }

    /** Activity row without depending on admin auth (this runs in the webhook/job context). */
    protected static function activity(int $queryId, string $type, string $description): void
    {
        try {
            QueryActivity::create([
                'query_id'    => $queryId,
                'admin_id'    => null,
                'type'        => $type,
                'description' => $description,
            ]);
        } catch (\Throwable $e) {
            // activity is non-critical — never fail lead creation on it
        }
    }
}
