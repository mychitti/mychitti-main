<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\SystemPrompt;
use App\Models\VendorSubscription;
use App\Modules\SalesCRM\Models\SalesFollowUp;
use App\Modules\SalesCRM\Models\SalesQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The three assistants an admin can talk to, and the live figures each one is allowed to see.
 *
 * They are one AI with three briefs rather than three stacks: same chat endpoint, same agent
 * record, same model. What differs is the system prompt — the persona's instructions plus a block
 * of figures read at the moment the message is sent.
 *
 * Each persona also gets its OWN guard, which is what the AI service keys memory and history by.
 * Sharing one guard would have put all three in a single thread, so the CEO assistant would read
 * the support assistant's conversation and answer from it.
 *
 * Figures are read from the same queries the admin dashboard uses, so a number quoted in chat and
 * the same number on the dashboard cannot disagree.
 */
class AdminAiPersona
{
    const CEO     = 'ceo';
    const SALES   = 'sales';
    const SUPPORT = 'support';

    const PERSONAS = [
        self::CEO => [
            'label' => 'CEO AI',
            'icon'  => 'tio-chart-bar-4',
            'blurb' => 'Platform-wide health — stores, vendors, subscriptions and growth.',
        ],
        self::SALES => [
            'label' => 'Sales AI',
            'icon'  => 'tio-shopping-cart',
            'blurb' => 'Enquiries, follow-ups and plan conversions.',
        ],
        self::SUPPORT => [
            'label' => 'Customer Support AI',
            'icon'  => 'tio-support',
            'blurb' => 'Tickets and issues raised by vendors and users.',
        ],
    ];

    /** A known persona key, or null. */
    public static function resolve(?string $key): ?string
    {
        return isset(self::PERSONAS[$key]) ? $key : null;
    }

    /**
     * Always 'admin'. The AI service validates the guard against a fixed list
     * (user, admin, vendor, agent_test, guest) and derives the memory column from it as
     * "{guard}_id", so a persona guard would be rejected outright and, if it were not, would
     * point at a column that does not exist.
     */
    public static function guard(string $key): string
    {
        return 'admin';
    }

    /**
     * The id the persona's thread is stored under.
     *
     * Threads are keyed by (user_id, guard). Since the guard is fixed, the separation has to come
     * from the id — one high, per-persona band added to the admin's own id, which keeps each
     * assistant's history and memory to itself while staying stable for that admin across
     * sessions. Same device SendAutoReply uses for its 900000000-based scope ids; the bands below
     * start above that one so the two can never land on the same row.
     */
    public static function memoryUserId(string $key, int $adminId): int
    {
        $band = match ($key) {
            self::CEO     => 910000000,
            self::SALES   => 920000000,
            self::SUPPORT => 930000000,
        };

        return $band + $adminId;
    }

    /**
     * The brief each assistant opens with, as first written.
     *
     * This is the half an admin may rewrite: it lives in system_prompts alongside Sam and Sara and
     * is edited on the AI Agents screen. What is here is the seed and the fallback — if the row is
     * deleted or emptied, the assistant keeps its original brief rather than losing its identity.
     */
    const DEFAULT_INTRO = [
        self::CEO => "You are the CEO assistant for MyChitti, a multi-vendor business platform in India. "
            . "You brief the platform's leadership on how the business as a whole is doing.",
        self::SALES => "You are the sales assistant for MyChitti. You help the sales team work their "
            . "enquiries, follow-ups and plan conversions.",
        self::SUPPORT => "You are the customer support assistant for MyChitti. You help the support team "
            . "stay on top of issues raised by vendors and by users.",
    ];

    /**
     * Persona brief + the figures it may see, as one system prompt.
     *
     * Two halves with two owners. The brief comes from the database and belongs to whoever is
     * running the platform. Everything below it — the live figures, and the rules governing how
     * they may be used — is assembled here and cannot be edited from the admin panel: those rules
     * are what stop the assistant inventing a number, and an assistant that can be told to ignore
     * them is worse than no assistant. Editing them is a code change, on purpose.
     */
    public static function systemPrompt(string $key): string
    {
        return static::intro($key) . "\n\n" . static::developerPrompt($key);
    }

    /**
     * The half that belongs to the developer: the live figures and the rules governing them.
     *
     * Split out so the admin screen can SHOW it read-only beside the editable intro. An admin who
     * cannot see this half is writing an intro blind — they have no way of knowing the assistant is
     * already told to quote figures exactly, or which figures it has.
     */
    public static function developerPrompt(string $key): string
    {
        return static::figures($key) . "\n"
            . "RULES:\n"
            . "- Quote the figures above exactly. Never estimate, project or invent a number.\n"
            . "- If something is not in the figures above, say it is not available here and point them at the "
            . "relevant admin screen. Do not guess.\n"
            . "- The figures are a snapshot taken when this message was sent — say so if asked how current they are.\n"
            . "- Be direct and brief. Plain text, no markdown headings.\n";
    }

    /** Write the backing rows for all three assistants, if they are not there yet. */
    public static function ensureRows(): void
    {
        foreach (array_keys(self::PERSONAS) as $key) {
            static::row($key);
        }
    }

    /** The admin-editable brief for one persona, or its original wording if none is saved. */
    public static function intro(string $key): string
    {
        $saved = trim((string) (static::row($key)->prompt ?? ''));

        return $saved !== '' ? $saved : (self::DEFAULT_INTRO[$key] ?? '');
    }

    /**
     * The system_prompts row backing one persona, created on first use.
     *
     * They are seeded rather than expected: these three assistants existed in code before they had
     * rows, so every install needs the row written once before it can be edited. Keyed on
     * (user_type, skill_type) rather than the name, which an admin is free to change.
     */
    public static function row(string $key)
    {
        static $cache = [];
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        try {
            $row = SystemPrompt::where('user_type', 'admin')->where('skill_type', $key)->first();

            if (!$row) {
                $row = SystemPrompt::create([
                    'name'        => self::PERSONAS[$key]['label'] ?? ucfirst($key),
                    'description' => self::PERSONAS[$key]['blurb'] ?? null,
                    'user_type'   => 'admin',
                    'skill_type'  => $key,
                    // Draft, not active: AiServiceClient resolves the general admin agent as "the
                    // most recently updated ACTIVE row for this user type", so seeding these as
                    // active would silently hand every admin chat whichever assistant was saved
                    // last. They are addressed by persona explicitly and do not need to be active.
                    'status'      => 'draft',
                    'prompt'      => self::DEFAULT_INTRO[$key] ?? '',
                ]);
            }
        } catch (\Throwable $e) {
            // A missing table or column must not take the assistants down — they simply fall back
            // to the wording in code.
            Log::warning('AdminAiPersona row lookup failed: ' . $e->getMessage());
            $row = null;
        }

        return $cache[$key] = $row;
    }

    /**
     * Label and blurb for the assistant switcher, preferring whatever the admin saved on the row.
     * The KEYS stay in code: memory bands and figure queries are bound to them, so a renamed
     * assistant must still be the same assistant.
     */
    public static function tabs(): array
    {
        $out = [];
        foreach (self::PERSONAS as $key => $meta) {
            $row = static::row($key);
            $out[$key] = [
                'label' => trim((string) ($row->name ?? '')) ?: $meta['label'],
                'icon'  => $meta['icon'],
                'blurb' => trim((string) ($row->description ?? '')) ?: $meta['blurb'],
            ];
        }

        return $out;
    }

    /** The live block for one persona. Each persona sees only its own. */
    protected static function figures(string $key): string
    {
        return match ($key) {
            self::CEO     => static::ceoFigures(),
            self::SALES   => static::salesFigures(),
            self::SUPPORT => static::supportFigures(),
        };
    }

    protected static function ceoFigures(): string
    {
        $monthStart = now()->startOfMonth();

        $stores       = DB::table('stores')->count();
        $storesActive = DB::table('stores')->where('status', 1)->count();
        $storesMonth  = DB::table('stores')->where('created_at', '>=', $monthStart)->count();
        $vendors      = DB::table('vendors')->count();

        $subsActive = VendorSubscription::whereDate('plan_expiry', '>=', now()->toDateString())->count();
        $subsMonth  = VendorSubscription::where('purchased_at', '>=', $monthStart)->count();

        // Expiring inside a fortnight is the number that decides whether this month holds.
        $subsExpiring = VendorSubscription::whereBetween('plan_expiry', [
            now()->toDateString(), now()->addDays(14)->toDateString(),
        ])->count();

        return "PLATFORM FIGURES (as of " . static::stamp() . "):\n"
            . "- Stores: {$stores} total, {$storesActive} active, {$storesMonth} joined this month\n"
            . "- Vendors: {$vendors} total\n"
            . "- Subscriptions: {$subsActive} live, {$subsMonth} taken this month, {$subsExpiring} expiring in the next 14 days\n"
            . '- Open support load: ' . SupportTicket::whereIn('status', ['open', 'reopened'])->count() . " tickets\n"
            . static::platformRevenue()
            . static::expiringList();
    }

    /**
     * Which subscriptions are about to lapse, by name.
     *
     * A count alone is the one thing nobody can act on — "1 expiring in 14 days" prompts "which
     * one?" every time, and the assistant had no way to answer. Capped so a bad month cannot
     * push the whole prompt out of shape.
     */
    protected static function expiringList(): string
    {
        // vendor_subscriptions.vendor_id holds the STORE id (see VendorSubscription::store()),
        // so this joins stores, not vendors.
        $rows = DB::table('vendor_subscriptions as vs')
            ->leftJoin('stores as s', 's.id', '=', 'vs.vendor_id')
            ->leftJoin('plans as p', 'p.id', '=', 'vs.plan_id')
            ->whereBetween('vs.plan_expiry', [now()->toDateString(), now()->addDays(14)->toDateString()])
            ->orderBy('vs.plan_expiry')
            ->limit(25)
            // plans names its label `title`, not `name` — see Plan rows built in VendorController.
            ->get(['s.name as store_name', 's.phone', 'p.title as plan_name', 'vs.plan_expiry']);

        if ($rows->isEmpty()) {
            return "\nEXPIRING SOON: none in the next 14 days.\n";
        }

        $lines = $rows->map(function ($r) {
            $expiry = $r->plan_expiry ? \Carbon\Carbon::parse($r->plan_expiry)->format('d M Y') : 'unknown date';

            return '- ' . ($r->store_name ?: 'Unnamed store')
                . ($r->plan_name ? ' (' . $r->plan_name . ')' : '')
                . ' — expires ' . $expiry
                . ($r->phone ? ', ' . $r->phone : '');
        })->implode("\n");

        return "\nSUBSCRIPTIONS EXPIRING IN THE NEXT 14 DAYS (" . $rows->count() . ", soonest first):\n"
            . $lines . "\n";
    }

    protected static function salesFigures(): string
    {
        $monthStart = now()->startOfMonth();

        $queriesNew   = SalesQuery::where('status', 'new')->count();
        $queriesWip   = SalesQuery::where('status', 'in_progress')->count();
        $queriesTotal = SalesQuery::count();

        $followToday   = SalesFollowUp::whereDate('due_date', today())->where('status', 'pending')->count();
        $followOverdue = SalesFollowUp::where('due_date', '<', today())->where('status', 'pending')->count();

        $conversions = VendorSubscription::where('purchased_at', '>=', $monthStart)->count();

        $figures = "SALES FIGURES (as of " . static::stamp() . "):\n"
            . "- Enquiries: {$queriesNew} new, {$queriesWip} in progress, {$queriesTotal} all time\n"
            . "- Follow-ups: {$followToday} due today, {$followOverdue} overdue\n"
            . "- Plan conversions this month: {$conversions} subscriptions taken\n";

        // Named, for the same reason as the expiring list: "12 new enquiries" cannot be worked,
        // a list of who they are can. Newest first, capped.
        $open = SalesQuery::whereIn('status', ['new', 'in_progress'])
            ->orderByDesc('id')
            ->limit(25)
            ->get(['ref_no', 'contact_name', 'company', 'phone', 'status', 'priority']);

        if ($open->isEmpty()) {
            return $figures . "\nOPEN ENQUIRIES: none.\n";
        }

        $lines = $open->map(function ($q) {
            return '- ' . ($q->ref_no ? $q->ref_no . ' · ' : '')
                . ($q->contact_name ?: 'No name')
                . ($q->company ? ' (' . $q->company . ')' : '')
                . ' — ' . $q->status
                . ($q->priority ? ', ' . $q->priority . ' priority' : '')
                . ($q->phone ? ', ' . $q->phone : '');
        })->implode("\n");

        return $figures
            . static::platformRevenue()
            . "\nOPEN ENQUIRIES (" . $open->count() . ", newest first):\n" . $lines . "\n";
    }

    /**
     * What the platform actually earns, beyond plan conversions.
     *
     * "Sales" for a marketplace is not only new subscriptions: WhatsApp is billed continuously
     * (monthly fee, per-message usage, token top-ups, setup and template fees) and lead
     * subscriptions are sold separately. Without these the assistant could only ever answer with
     * a conversion count, which is what prompted the question.
     *
     * Billing lines are grouped BY type rather than filtered to a hardcoded list — the types are
     * written dynamically by WhatsAppBilling, so asking the table what exists cannot go stale.
     */
    protected static function platformRevenue(): string
    {
        $monthStart = now()->startOfMonth();
        $out = "\nPLATFORM REVENUE:\n";

        $waMonth = DB::table('wa_billing_invoices')
            ->where('created_at', '>=', $monthStart)
            ->selectRaw('COALESCE(SUM(total),0) total, COUNT(*) charges')->first();
        $waAll = DB::table('wa_billing_invoices')
            ->selectRaw('COALESCE(SUM(total),0) total')->value('total');

        $out .= '- WhatsApp billed this month: ' . _price((float) $waMonth->total)
            . ' across ' . (int) $waMonth->charges . " charges\n"
            . '- WhatsApp billed all time: ' . _price((float) $waAll) . "\n";

        $byType = DB::table('wa_billing_invoices')
            ->where('created_at', '>=', $monthStart)
            ->groupBy('type')
            ->orderByDesc(DB::raw('SUM(total)'))
            ->get(['type', DB::raw('COALESCE(SUM(total),0) as total'), DB::raw('COUNT(*) as charges')]);

        foreach ($byType as $row) {
            $out .= '   · ' . $row->type . ': ' . _price((float) $row->total)
                . ' (' . (int) $row->charges . " charges)\n";
        }

        $waPlans = DB::table('wa_subscriptions')
            ->where('status', 'active')
            ->groupBy('plan')
            ->get(['plan', DB::raw('COUNT(*) as stores'), DB::raw('COALESCE(SUM(monthly_fee),0) as mrr')]);

        foreach ($waPlans as $row) {
            $out .= '- WhatsApp plan "' . $row->plan . '": ' . (int) $row->stores
                . ' active, ' . _price((float) $row->mrr) . " monthly\n";
        }

        $leadSubs = DB::table('lead_subscriptions')
            ->whereDate('expires_at', '>=', now()->toDateString())->count();
        $leadSubsMonth = DB::table('lead_subscriptions')
            ->where('starts_at', '>=', $monthStart)->count();
        $out .= "- Lead subscriptions: {$leadSubs} live, {$leadSubsMonth} started this month\n";

        return $out;
    }

    protected static function supportFigures(): string
    {
        $monthStart = now()->startOfMonth();

        $open     = SupportTicket::whereIn('status', ['open', 'reopened'])->count();
        $closed   = SupportTicket::where('status', 'closed')->count();
        $total    = SupportTicket::count();
        $newMonth = SupportTicket::where('created_at', '>=', $monthStart)->count();

        // Split by who raised it: a ticket carries a vendor_id when a vendor raised it and a
        // user_id when an end user did, so the two audiences are counted apart.
        $fromVendors = SupportTicket::whereNotNull('vendor_id')
            ->whereIn('status', ['open', 'reopened'])->count();
        $fromUsers = SupportTicket::whereNotNull('user_id')
            ->whereIn('status', ['open', 'reopened'])->count();

        // The Sales & Marketing CRM keeps its own ticket queue; support covers both, so both are
        // reported rather than silently showing one of the two.
        $crmOpen = \App\Modules\SalesCRM\Models\SupportTicket::where('status', 'open')->count();
        $crmWip  = \App\Modules\SalesCRM\Models\SupportTicket::where('status', 'in_progress')->count();

        $figures = "SUPPORT FIGURES (as of " . static::stamp() . "):\n"
            . "- Tickets: {$open} open or reopened, {$closed} closed, {$total} all time, {$newMonth} raised this month\n"
            . "- Of the open ones: {$fromVendors} raised by vendors, {$fromUsers} raised by users\n"
            . "- CRM ticket queue: {$crmOpen} open, {$crmWip} in progress\n";

        // The open queue itself — oldest first, since the oldest untouched ticket is the one that
        // matters. A count alone cannot answer "which ones are still open?".
        $queue = SupportTicket::whereIn('status', ['open', 'reopened'])
            ->orderBy('created_at')
            ->limit(25)
            ->get(['id', 'subject', 'status', 'vendor_id', 'user_id', 'created_at']);

        if ($queue->isEmpty()) {
            return $figures . "\nOPEN TICKETS: none.\n";
        }

        $lines = $queue->map(function ($t) {
            $raisedBy = $t->vendor_id ? 'vendor' : ($t->user_id ? 'user' : 'unknown');
            $age = $t->created_at ? \Carbon\Carbon::parse($t->created_at)->diffForHumans() : '';

            return '- #' . $t->id . ' ' . ($t->subject ?: 'No subject')
                . ' — ' . $t->status . ', raised by ' . $raisedBy
                . ($age ? ', ' . $age : '');
        })->implode("\n");

        return $figures . "\nOPEN TICKETS (" . $queue->count() . ", oldest first):\n" . $lines . "\n";
    }

    protected static function stamp(): string
    {
        return now()->format('d M Y, h:i A');
    }
}
