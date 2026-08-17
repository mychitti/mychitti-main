<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\UserNotificationPreference;
use App\Models\Zone;
use App\Services\WhatsAppService;
use App\Traits\WhatsAppAudience;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bulk WhatsApp from the MyChitti platform number.
 *
 * The vendor panel has had this for its own customer book (Vendor\WhatsAppController::bulkSend);
 * this is the platform's own version — one approved template on the platform WABA sent to vendors,
 * to MyChitti customers, or to a pasted list of numbers. Nothing here touches a vendor's WABA,
 * their wallet or their templates: platform sends carry store_id NULL in whatsapp_messages, which
 * is what keeps them out of vendor billing entirely.
 *
 * The claim table is shared with the vendor composer (wa_bulk_sends), whose store_id is NOT NULL —
 * platform runs are written under store id 0, a value no store can have, so the two histories can
 * never read each other's rows.
 */
class WhatsAppBulkController extends Controller
{
    use WhatsAppAudience;

    /** Rows the recipient picker loads at once — the browser holds these ids to send by. */
    const PICKER_LIMIT = 500;

    /** Recipients per send call. The browser drives the batches so a long run never hits
     *  max_execution_time and the admin watches it progress. */
    const BATCH_LIMIT = 25;

    /**
     * Scope for platform runs in wa_bulk_sends. The column is NOT NULL (it was written for
     * vendors, who always have an id), so "no store" is recorded as 0 rather than NULL.
     */
    const PLATFORM_SCOPE = 0;

    /**
     * Delivery-log context for these sends. Deliberately not 'nearby': that context is what
     * WhatsAppService::nearbyCappedPhones() counts to hold every vendor in a city to four
     * messages per person per month, and platform announcements must not eat a vendor's quota.
     */
    const CONTEXT = 'admin bulk';

    /** Most numbers the "already messaged recently" exclusion will hold in memory at once. */
    const RECENT_EXCLUSION_CAP = 100000;

    /** Composer and history on one page — the composer is where a send is repeated from. */
    public function index(Request $request)
    {
        WhatsAppService::ensureMessagesTable();
        WhatsAppService::ensureBulkSendTable();

        $wa = WhatsAppService::make();
        $connected = $wa->isConfigured();

        $tab = in_array($request->get('tab'), ['compose', 'history'], true)
            ? $request->get('tab') : 'compose';

        $templates = [];
        $templateError = null;
        if ($connected && $wa->hasWaba()) {
            $res = $wa->listTemplates();
            $templates = $this->bulkTemplateOptions($res['data']);
            if (!$res['success']) {
                $templateError = $res['error'];
            }
        }

        $counts = $this->audienceCounts();
        $zones = Zone::active()->orderBy('name')->get(['id', 'name']);
        $optOutCount = count($this->optOutSuffixes());

        return view('admin-views.whatsapp.bulk', array_merge(
            compact('connected', 'templates', 'templateError', 'counts', 'zones', 'optOutCount', 'tab'),
            $this->historyData()
        ));
    }

    /**
     * The audience behind the picker, as JSON.
     *
     * `total` is the whole filtered audience, not the page — it is what the "send to all matching"
     * option offers, and the reason a 90,000-customer send never has to ship 90,000 ids to the
     * browser.
     */
    public function recipients(Request $request)
    {
        $request->validate([
            'audience'  => 'required|in:vendors,customers',
            'search'    => 'nullable|string|max:120',
            'zone_id'   => 'nullable|integer',
            'status'    => 'nullable|in:active,all',
            'skip_days' => 'nullable|integer|min:0|max:365',
        ]);

        $query = $this->audienceQuery($request->audience, $this->filters($request));

        $total = (clone $query)->count();
        $rows = $query->orderBy('name')->limit(self::PICKER_LIMIT)->get();

        return response()->json([
            'success'   => true,
            'total'     => $total,
            'truncated' => $total > self::PICKER_LIMIT,
            'rows'      => $rows,
        ]);
    }

    /**
     * Send one approved template to a batch of recipients from the platform number.
     *
     * One audience per run. The vendor composer merges two because a vendor is billed differently
     * for each; here the distinction that matters is who is being addressed, and mixing vendors and
     * customers into one blast is a mistake worth making the admin state twice.
     */
    public function send(Request $request)
    {
        $request->validate([
            'template'     => 'required|string',
            'language'     => 'required|string',
            'run_id'       => 'required|string|max:40',
            'audience'     => 'required|in:vendors,customers,manual',
            'mode'         => 'required|in:selected,all',
            'ids'          => 'array|max:' . self::BATCH_LIMIT,
            'ids.*'        => 'integer',
            'numbers'      => 'required_if:audience,manual|array|max:' . self::BATCH_LIMIT,
            'numbers.*'    => 'string|max:32',
            'limit'        => 'required_if:mode,all|integer|min:1|max:' . self::BATCH_LIMIT,
            'zone_id'      => 'nullable|integer',
            'search'       => 'nullable|string|max:120',
            'status'       => 'nullable|in:active,all',
            'skip_days'    => 'nullable|integer|min:0|max:365',
            'params'       => 'nullable|array',
            'params.*.key' => 'nullable|string|max:64',
            // Meta fetches header media themselves, so it has to be a public URL —
            // headerMedia() below turns an upload into one.
            'header_media' => 'nullable|url|max:1000',
        ]);

        $wa = WhatsAppService::make();
        if (!$wa->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'The MyChitti WhatsApp number is not configured yet.',
            ], 422);
        }

        WhatsAppService::ensureBulkSendTable();

        $audience = $request->audience;
        $runId = trim((string) $request->run_id);

        // 'ids' cannot be a blanket required_if: a pasted list also sends with mode 'selected',
        // and it carries numbers instead. Checked here so an empty tick-list is refused out loud
        // rather than reported as a batch that sent to nobody.
        if ($audience !== 'manual' && $request->mode === 'selected' && empty($request->input('ids'))) {
            return response()->json([
                'success' => false,
                'message' => 'Pick at least one recipient before sending.',
            ], 422);
        }

        $recipients = $this->batchRecipients($request, $audience, $runId);

        // A template is sent with the components it was approved with: one carrying an image,
        // video or document header needs that file on every message, or Graph refuses the lot
        // with "(#132012) Parameter format does not match format in the created template".
        $tpl = $this->platformTemplate((string) $request->template, (string) $request->language);
        $headerFormat = $this->headerFormatOf($tpl);
        $headerComponent = null;
        if (in_array($headerFormat, WhatsAppService::MEDIA_HEADERS, true)) {
            $mediaUrl = trim((string) $request->input('header_media'));
            if ($mediaUrl === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'This template has ' . ($headerFormat === 'IMAGE' ? 'an image' : 'a ' . strtolower($headerFormat))
                        . ' at the top, so a file has to be attached before it can be sent.',
                ], 422);
            }
            $headerComponent = WhatsAppService::mediaHeaderComponent($headerFormat, $mediaUrl);
        }

        // Read once for the batch and filled per recipient below, so the history can show the
        // words each number actually read — the delivery log only keeps "template: {name}", and a
        // template can be edited or deleted long before anyone asks what was sent.
        $templateBody = $this->bodyTextOf($tpl);

        $rawParams = array_values((array) $request->input('params', []));
        $optedOut = $this->optOutSuffixes();
        $results = [];
        $skipped = 0;
        $blocked = 0;

        foreach ($recipients as $person) {
            $name = trim((string) $person->name) ?: 'Customer';
            $phone = trim((string) $person->phone);
            $phone10 = substr(preg_replace('/[^0-9]/', '', $phone) ?? '', -10);

            // The audience queries already drop these; a pasted list has not been through them,
            // and someone who replied STOP must not be reachable again by typing their number in.
            if ($phone10 !== '' && in_array($phone10, $optedOut, true)) {
                $blocked++;
                continue;
            }

            // Claimed before dispatch, never after. The unique key on (run_id, phone10) is what
            // answers "if a run breaks halfway and I press send again, does anyone get it twice?"
            // — a repeat claim fails and the person is skipped. Claiming first means even a crash
            // between the claim and the API call leaves the row behind to block the re-send.
            $sendId = $this->claimRecipient($runId, $person, $phone, $name, $audience, $request->template);
            if (!$sendId) {
                $skipped++;
                continue;
            }

            $auto = ['customer_name' => $name, 'customer_phone' => $phone];
            $tokens = [
                '{name}'           => $name,
                '{customer_name}'  => $name,
                '{phone}'          => $phone,
                '{customer_phone}' => $phone,
            ];

            $parameters = [];
            $filled = [];
            foreach ($rawParams as $i => $raw) {
                $key   = trim(is_array($raw) ? (string) ($raw['key'] ?? '') : '') ?: (string) ($i + 1);
                $value = is_array($raw) ? (string) ($raw['value'] ?? '') : (string) $raw;

                $value = array_key_exists($key, $auto) ? $auto[$key] : strtr($value, $tokens);

                $clean = $this->sanitizeParam($value);
                $filled[$key] = $clean;
                $parameters[] = WhatsAppService::bodyParameter($key, $clean);
            }

            // Header first — Meta matches components against the approved template in order.
            $components = [];
            if ($headerComponent) {
                $components[] = $headerComponent;
            }
            if ($parameters) {
                $components[] = ['type' => 'body', 'parameters' => $parameters];
            }

            $res = $wa->sendTemplate($phone, $request->template, $request->language, $components, self::CONTEXT);

            DB::table('wa_bulk_sends')->where('id', $sendId)->update([
                'wamid'      => $res['id'] ?? null,
                'status'     => $res['success'] ? 'sent' : 'failed',
                'error'      => $res['error'] ?? null,
                'body'       => $templateBody ? mb_substr($this->fillBody($templateBody, $filled), 0, 2000) : null,
                'language'   => mb_substr((string) $request->language, 0, 20),
                'updated_at' => now(),
            ]);

            $results[] = [
                'id'      => $person->id,
                'name'    => $name,
                'phone'   => $phone,
                'success' => (bool) $res['success'],
                'error'   => $res['error'] ?? null,
            ];
        }

        return response()->json([
            'success' => true,
            'sent'    => count(array_filter($results, fn($r) => $r['success'])),
            'failed'  => count(array_filter($results, fn($r) => !$r['success'])),
            // Already claimed in this run — a resend after a broken batch, which is what the
            // claim exists for. Reported so the composer can say so rather than double-count.
            'skipped' => $skipped,
            'blocked' => $blocked,
            'results' => $results,
        ]);
    }

    /**
     * Take the file for a media-header template and hand back a public URL for it.
     *
     * Meta fetches header media from their own servers at send time, so it cannot be a private
     * path or a data URI. The same URL is reused for every recipient of the run.
     */
    public function headerMedia(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:5120|mimes:jpg,jpeg,png,mp4,pdf',
        ], [
            'file.mimes' => 'Attach a JPG or PNG image, an MP4 video, or a PDF document.',
            'file.max'   => 'The file must be 5 MB or smaller.',
        ]);

        try {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension() ?: $file->extension();
            $name = Helpers::upload('whatsapp/header/', $extension, $file);

            return response()->json([
                'success' => true,
                'url'     => asset('storage/app/public/whatsapp/header/' . $name),
                'name'    => $file->getClientOriginalName(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Admin WhatsApp header media upload failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Could not upload that file. Try again.'], 500);
        }
    }

    /**
     * One run, number by number.
     *
     * Delivery state comes from whatsapp_messages joined on the wamid Meta returned — the claim
     * row only knows the send was accepted, while the webhook is what later says delivered, read
     * or failed at the handset.
     */
    public function run(Request $request, $runId)
    {
        WhatsAppService::ensureBulkSendTable();
        WhatsAppService::ensureMessagesTable();

        $run = $this->platformRun($runId);
        if (!$run) {
            Toastr::error(translate('That send could not be found.'));
            return redirect()->route('admin.business-settings.third-party.whatsapp-bulk', ['tab' => 'history']);
        }

        $this->attachDeliveryCounts($run, self::PLATFORM_SCOPE);

        $query = DB::table('wa_bulk_sends as b')
            ->leftJoin('whatsapp_messages as m', function ($join) {
                $join->on('m.wamid', '=', 'b.wamid')->where('m.direction', 'out');
            })
            ->where('b.store_id', self::PLATFORM_SCOPE)
            ->where('b.run_id', $run->run_id);

        if ($status = $request->input('status')) {
            $status === 'delivered'
                ? $query->whereIn('m.status', ['delivered', 'read'])
                : $query->where('b.status', $status);
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('b.name', 'like', "%{$search}%")->orWhere('b.phone', 'like', "%{$search}%");
            });
        }

        // Aliased, not `select *` — both tables carry a `status` column and the join would leave
        // only one standing, which is the difference between "we accepted it" and "it arrived".
        $rows = $query->orderBy('b.id')
            ->select(
                'b.name', 'b.phone', 'b.client_id', 'b.template', 'b.body', 'b.error', 'b.sent_at',
                'b.audience',
                'b.status as send_status',
                'm.status as delivery_status',
                'm.status_at as delivery_at'
            )
            ->paginate(50)
            ->appends($request->only('search', 'status'));

        return view('admin-views.whatsapp.bulk-run', compact('run', 'rows'));
    }

    /** The same run as a spreadsheet. */
    public function export(Request $request, $runId)
    {
        WhatsAppService::ensureBulkSendTable();
        WhatsAppService::ensureMessagesTable();

        $run = $this->platformRun($runId);
        if (!$run) {
            Toastr::error(translate('That send could not be found.'));
            return redirect()->route('admin.business-settings.third-party.whatsapp-bulk', ['tab' => 'history']);
        }

        $filename = 'whatsapp-bulk-' . substr($run->run_id, 0, 8) . '-' . now()->format('Ymd-Hi') . '.csv';

        return response()->streamDownload(function () use ($run) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Phone', 'Audience', 'Sent at', 'Template', 'Message sent', 'Result', 'Delivery', 'Error']);

            DB::table('wa_bulk_sends as b')
                ->leftJoin('whatsapp_messages as m', function ($join) {
                    $join->on('m.wamid', '=', 'b.wamid')->where('m.direction', 'out');
                })
                ->where('b.store_id', self::PLATFORM_SCOPE)
                ->where('b.run_id', $run->run_id)
                ->orderBy('b.id')
                ->select('b.name', 'b.phone', 'b.audience', 'b.sent_at', 'b.template', 'b.body', 'b.status', 'b.error', 'm.status as delivery')
                ->chunk(500, function ($chunk) use ($out) {
                    foreach ($chunk as $row) {
                        fputcsv($out, [
                            $row->name ?: 'Customer',
                            $row->phone,
                            self::audienceLabel($row->audience),
                            $row->sent_at,
                            $row->template,
                            $row->body,
                            $row->status,
                            $row->delivery ?: '—',
                            $row->error,
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * The recipients this one call may message.
     *
     * 'all' takes the next unclaimed people in the filtered audience rather than an offset page:
     * an offset restarts at zero on every call, which is how a broken run re-messages whoever it
     * already reached. Exclusion by claim is what makes each batch return the *next* people.
     */
    private function batchRecipients(Request $request, string $audience, string $runId)
    {
        if ($audience === 'manual') {
            return collect((array) $request->input('numbers', []))
                ->map(fn($n) => (object) ['id' => null, 'name' => '', 'phone' => trim((string) $n)])
                ->filter(fn($r) => strlen(preg_replace('/[^0-9]/', '', $r->phone) ?? '') >= 10)
                ->values();
        }

        $query = $this->audienceQuery($audience, $this->filters($request));

        if ($request->mode === 'selected') {
            $column = $audience === 'vendors' ? 'stores.id' : 'users.id';
            return $query->whereIn($column, (array) $request->input('ids', []))->get();
        }

        $phone10 = $this->phone10Sql($audience === 'vendors' ? 'stores.phone' : 'users.phone');
        // Column against column, so both sides are pinned to one collation — the claim table and
        // the contact tables can carry different ones. See WhatsAppAudience::collatedPhone().
        $claimed = $this->collatedPhone('b.`phone10`');
        $candidate = $this->collatedPhone($phone10);

        return $query
            ->whereNotExists(function ($q) use ($runId, $claimed, $candidate) {
                $q->select(DB::raw(1))->from('wa_bulk_sends as b')
                    ->where('b.run_id', $runId)
                    ->whereRaw("{$claimed} = {$candidate}");
            })
            ->orderByRaw($phone10)
            ->limit((int) $request->input('limit'))
            ->get();
    }

    /** The audience knobs the composer sends with both the picker request and the send. */
    private function filters(Request $request): array
    {
        return [
            'zone_id'   => (int) $request->input('zone_id'),
            'search'    => trim((string) $request->input('search')),
            'status'    => (string) $request->input('status', 'active'),
            // Default 30 rather than 0: the composer's own "send to all" walk is only protected
            // from restarting at the same people while one run id lives, and a run resumed
            // tomorrow is a new one. This is what keeps the second press from messaging everybody
            // the first press already reached.
            'skip_days' => $request->has('skip_days') ? (int) $request->input('skip_days') : 30,
        ];
    }

    /** Either audience as {id, name, phone}, already stripped of everyone who opted out. */
    private function audienceQuery(string $audience, array $filters = [])
    {
        $zoneId = $filters['zone_id'] ?? null;
        $search = $filters['search'] ?? null;

        $query = $audience === 'vendors'
            ? $this->vendorQuery($zoneId, $search, $filters['status'] ?? 'active')
            : $this->customerQuery($zoneId, $search);

        $skipDays = (int) ($filters['skip_days'] ?? 0);
        if ($skipDays > 0) {
            $recent = $this->recentlyMessagedSuffixes($skipDays);
            if (!empty($recent)) {
                $column = $audience === 'vendors' ? 'stores.phone' : 'users.phone';
                $query->whereNotIn(DB::raw($this->phone10Sql($column)), $recent);
            }
        }

        return $query;
    }

    /**
     * Numbers a platform bulk send already reached inside the window.
     *
     * A bound list rather than a correlated NOT EXISTS: both sides of a column-to-column phone
     * comparison have to be wrapped in CONVERT/COLLATE, which makes every index useless and turns
     * an audience count into a per-candidate subquery. phone10 is stored already normalized, so a
     * list compares straight against the same suffix expression the opt-out filter uses.
     *
     * Cached for a minute because a long run asks per batch, and re-reading a month of sends
     * hundreds of times over is most of the work the run would do. Staleness inside that minute
     * cannot cause a double message: the per-run claim, not this, is the hard guarantee.
     *
     * Capped — this screen exists for sends big enough that a month of them should not be pulled
     * into memory whole. Past the cap the list is partial and someone may be offered again; the
     * claim still stops them being messaged twice inside one run.
     */
    private function recentlyMessagedSuffixes(int $days): array
    {
        try {
            return Cache::remember('wa_admin_bulk_recent_' . $days, 60, function () use ($days) {
                return DB::table('wa_bulk_sends')
                    ->where('store_id', self::PLATFORM_SCOPE)
                    ->where('status', 'sent')
                    ->where('sent_at', '>=', now()->subDays($days))
                    ->distinct()
                    ->limit(self::RECENT_EXCLUSION_CAP)
                    ->pluck('phone10')
                    ->all();
            });
        } catch (\Throwable $e) {
            Log::warning('recent platform send lookup failed: ' . $e->getMessage());
            return [];
        }
    }

    /** Vendors, by the phone on their store record. */
    private function vendorQuery(?int $zoneId = null, ?string $search = null, string $status = 'active')
    {
        $query = DB::table('stores')
            ->whereNotNull('stores.phone')
            ->where('stores.phone', '!=', '')
            ->select('stores.id as id', 'stores.name as name', 'stores.phone as phone');

        // Deactivated stores are still real businesses an admin may need to reach (an onboarding
        // nudge, a billing notice), so 'all' is offered — it is simply not the default.
        if ($status !== 'all') {
            $query->where('stores.status', 1);
        }

        if ($zoneId) {
            $query->whereIn('stores.zone_id', Helpers::zone_with_descendants($zoneId));
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('stores.name', 'like', "%{$search}%")->orWhere('stores.phone', 'like', "%{$search}%");
            });
        }

        return $this->excludeOptOuts($query, 'stores.phone');
    }

    /**
     * MyChitti account holders.
     *
     * The WhatsApp channel preference is honoured here as well as through the opt-out list:
     * turning the dashboard toggle off writes a platform-wide opt-out row today, but accounts that
     * set it before that behaviour existed only have the preference to speak for them.
     */
    private function customerQuery(?int $zoneId = null, ?string $search = null)
    {
        $query = DB::table('users')
            ->whereNotNull('users.phone')
            ->where('users.phone', '!=', '')
            ->select(
                'users.id as id',
                DB::raw("TRIM(CONCAT(COALESCE(`users`.`f_name`, ''), ' ', COALESCE(`users`.`l_name`, ''))) as name"),
                'users.phone as phone'
            );

        // leftJoin, not an inner one: WhatsApp is on by default and most accounts have no
        // preference row at all, which an inner join would read as a refusal.
        try {
            UserNotificationPreference::ensureTable();
            $query->leftJoin('user_notification_prefs as p', 'p.user_id', '=', 'users.id')
                ->where(fn($q) => $q->whereNull('p.whatsapp')->orWhere('p.whatsapp', 1));
        } catch (\Throwable $e) {
            Log::warning('WhatsApp preference lookup unavailable: ' . $e->getMessage());
        }

        if ($zoneId) {
            $query->whereIn('users.zone_id', Helpers::zone_with_descendants($zoneId));
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.f_name', 'like', "%{$search}%")
                    ->orWhere('users.l_name', 'like', "%{$search}%")
                    ->orWhere('users.phone', 'like', "%{$search}%");
            });
        }

        return $this->excludeOptOuts($query, 'users.phone');
    }

    /** Drop everyone who has replied STOP or switched WhatsApp off platform-wide. */
    private function excludeOptOuts($query, string $phoneColumn)
    {
        $suffixes = $this->optOutSuffixes();

        return empty($suffixes)
            ? $query
            : $query->whereNotIn(DB::raw($this->phone10Sql($phoneColumn)), $suffixes);
    }

    /** Last-10-digit forms of every platform-wide opt-out. */
    private function optOutSuffixes(): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn($p) => substr(preg_replace('/[^0-9]/', '', (string) $p) ?? '', -10),
            WhatsAppService::optedOutPhones(null)
        ))));
    }

    /**
     * How big each audience is, for the headline figures on the composer.
     *
     * Cached: both are full scans (the opt-out exclusion computes a phone suffix no index covers)
     * and the page is opened and reopened. Display only — a send re-runs the query live, so a
     * stale figure can at worst offer a number that comes back a little smaller when built.
     */
    private function audienceCounts(): array
    {
        return [
            'vendors'   => (int) Cache::remember('wa_admin_bulk_vendors', 600, fn() => $this->vendorQuery()->count()),
            'customers' => (int) Cache::remember('wa_admin_bulk_customers', 600, fn() => $this->customerQuery()->count()),
        ];
    }

    /**
     * Reserve one recipient for this run. Returns the claim row id, or null when an earlier batch
     * (or a repeat of one) already holds them.
     */
    private function claimRecipient(string $runId, $person, string $phone, string $name, string $audience, ?string $template): ?int
    {
        $phone10 = substr(preg_replace('/[^0-9]/', '', $phone) ?? '', -10);
        if ($phone10 === '') {
            return null;
        }

        try {
            return (int) DB::table('wa_bulk_sends')->insertGetId([
                'store_id'   => self::PLATFORM_SCOPE,
                'run_id'     => $runId,
                'phone10'    => $phone10,
                'phone'      => $phone,
                'name'       => mb_substr($name, 0, 190),
                'client_id'  => $person->id ?: null,
                'audience'   => $audience,
                'template'   => $template ? mb_substr($template, 0, 190) : null,
                'status'     => 'queued',
                'sent_at'    => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Duplicate key — already claimed in this run. Any other failure is treated the same
            // way deliberately: without a claim we cannot promise the person is not messaged
            // twice, and not sending is the safer half of that bargain.
            return null;
        }
    }

    /**
     * One template as Meta holds it on the PLATFORM's account.
     *
     * WhatsAppService::templateHeaderFormat() and templateBodyText() answer the same questions,
     * but both return null unless the credentials resolve to a vendor's own WABA — they were
     * written for the vendor composer. This is the platform's equivalent, cached for the same
     * reason: a run posts a batch at a time, and without it a 1,000-person send would ask Graph
     * for one unchanging template list forty times over.
     */
    private function platformTemplate(string $name, ?string $lang = null): ?array
    {
        try {
            $key = 'wa_admin_tpl_' . md5(strtolower($name . '|' . $lang));

            return Cache::remember($key, 600, function () use ($name, $lang) {
                $wa = WhatsAppService::make();
                if (!$wa->hasWaba()) {
                    return null;
                }

                $res = $wa->listTemplates();
                if (!$res['success']) {
                    return null;
                }

                foreach ($res['data'] as $tpl) {
                    if (strtolower((string) data_get($tpl, 'name')) !== strtolower($name)) {
                        continue;
                    }
                    if ($lang && strtolower((string) data_get($tpl, 'language')) !== strtolower($lang)) {
                        continue;
                    }
                    return (array) $tpl;
                }

                return null;
            });
        } catch (\Throwable $e) {
            // Unknown, never "no header" — the caller must not read a Graph outage as permission
            // to send a media template without its file.
            return null;
        }
    }

    /** TEXT / IMAGE / VIDEO / DOCUMENT, or null when the template has no header. */
    private function headerFormatOf(?array $tpl): ?string
    {
        foreach ((array) data_get($tpl, 'components', []) as $c) {
            if (strtoupper((string) data_get($c, 'type')) === 'HEADER') {
                return strtoupper((string) data_get($c, 'format', 'TEXT')) ?: 'TEXT';
            }
        }

        return null;
    }

    /** The approved body text, still carrying its {{variables}}. */
    private function bodyTextOf(?array $tpl): ?string
    {
        foreach ((array) data_get($tpl, 'components', []) as $c) {
            if (strtoupper((string) data_get($c, 'type')) === 'BODY') {
                return (string) data_get($c, 'text', '');
            }
        }

        return null;
    }

    /** Meta rejects newlines and runs of spaces inside a parameter, and caps its length. */
    private function sanitizeParam(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return trim(mb_substr($value, 0, 900));
    }

    /** Put this recipient's parameter values into the template body, for the record kept of it. */
    private function fillBody(string $body, array $values): string
    {
        $tokens = [];
        foreach ($values as $key => $value) {
            $tokens['{{' . $key . '}}'] = $value;
            $tokens['{{ ' . $key . ' }}'] = $value;
        }

        return strtr($body, $tokens);
    }

    /**
     * Every platform bulk send, one row per run.
     *
     * Grouped in SQL rather than in PHP: one row per recipient lives in this table, and the
     * listing must never load a year of them to count them.
     */
    private function historyData(): array
    {
        $runs = DB::table('wa_bulk_sends')
            ->where('store_id', self::PLATFORM_SCOPE)
            ->select(
                'run_id',
                DB::raw('MAX(template) as template'),
                DB::raw('MAX(language) as language'),
                DB::raw('MAX(audience) as audience'),
                // LEFT(), not the bare column: body is TEXT, and a GROUP BY carrying a TEXT value
                // cannot use an in-memory temp table — MySQL spills the whole grouping to disk.
                DB::raw('MAX(LEFT(body, 200)) as body'),
                DB::raw('COUNT(*) as recipients'),
                DB::raw("SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent"),
                DB::raw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed"),
                DB::raw("SUM(CASE WHEN status = 'queued' THEN 1 ELSE 0 END) as queued"),
                DB::raw('MIN(sent_at) as started_at'),
                DB::raw('MAX(sent_at) as finished_at')
            )
            ->groupBy('run_id')
            ->orderByRaw('MIN(sent_at) DESC')
            ->paginate(20)
            ->appends(['tab' => 'history']);

        $totals = DB::table('wa_bulk_sends')
            ->where('store_id', self::PLATFORM_SCOPE)
            ->selectRaw("COUNT(*) as recipients,
                COUNT(DISTINCT run_id) as runs,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN sent_at >= ? THEN 1 ELSE 0 END) as last30", [now()->subDays(30)])
            ->first();

        return compact('runs', 'totals');
    }

    /** One run's header, scoped to the platform so a vendor's run id reads as not found. */
    private function platformRun(string $runId)
    {
        return DB::table('wa_bulk_sends')
            ->where('store_id', self::PLATFORM_SCOPE)
            ->where('run_id', $runId)
            ->select(
                'run_id',
                DB::raw('MAX(template) as template'),
                DB::raw('MAX(language) as language'),
                DB::raw('MAX(audience) as audience'),
                DB::raw('MAX(body) as body'),
                DB::raw('COUNT(*) as recipients'),
                DB::raw("SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent"),
                DB::raw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed"),
                DB::raw("SUM(CASE WHEN status = 'queued' THEN 1 ELSE 0 END) as queued"),
                DB::raw('MIN(sent_at) as started_at'),
                DB::raw('MAX(sent_at) as finished_at')
            )
            ->groupBy('run_id')
            ->first();
    }

    /**
     * What the handsets reported back, counted onto the run header.
     *
     * Kept out of platformRun() because these come from whatsapp_messages: the claim row only says
     * Meta accepted the send, the webhook is what later says delivered, read or failed. `read` is a
     * MySQL keyword, hence the aliases. Anything sent with no receipt yet is `awaiting`.
     */
    private function attachDeliveryCounts($run, $storeId): void
    {
        $counts = DB::table('wa_bulk_sends as b')
            ->join('whatsapp_messages as m', function ($join) {
                $join->on('m.wamid', '=', 'b.wamid')->where('m.direction', 'out');
            })
            ->where('b.store_id', $storeId)
            ->where('b.run_id', $run->run_id)
            ->selectRaw("SUM(CASE WHEN m.status IN ('delivered','read') THEN 1 ELSE 0 END) as delivered_count,
                SUM(CASE WHEN m.status = 'read' THEN 1 ELSE 0 END) as read_count,
                SUM(CASE WHEN m.status = 'failed' THEN 1 ELSE 0 END) as undelivered_count")
            ->first();

        $run->delivered   = (int) ($counts->delivered_count ?? 0);
        $run->read        = (int) ($counts->read_count ?? 0);
        $run->undelivered = (int) ($counts->undelivered_count ?? 0);
        $run->awaiting    = max(0, (int) $run->sent - $run->delivered - $run->undelivered);
    }

    /** What the stored audience key is called on screen. Static so the history blades share it. */
    public static function audienceLabel(?string $audience): string
    {
        return [
            'vendors'   => 'Vendors',
            'customers' => 'Customers',
            'manual'    => 'Pasted numbers',
        ][$audience] ?? ucfirst((string) $audience);
    }
}
