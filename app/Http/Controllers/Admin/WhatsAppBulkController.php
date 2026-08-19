<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Jobs\SendPlatformBulkWhatsAppJob;
use App\Models\Zone;
use App\Services\WhatsAppBulkRun;
use App\Services\WhatsAppService;
use App\Traits\PlatformWhatsAppAudience;
use App\Traits\WhatsAppAudience;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
    use WhatsAppAudience, PlatformWhatsAppAudience;

    /** Rows the recipient picker loads at once — the browser holds these ids to send by. */
    const PICKER_LIMIT = 500;

    /** Most recipients one run may cover, whether pasted or asked for by "send to everyone". */
    const RUN_LIMIT = 200000;

    /**
     * Most recipients one run may be ticked for. Deliberately far above the picker limit: the
     * picker only shows a page at a time, but a tick survives a filter change, so a selection can
     * be gathered several pages deep before Send is pressed.
     */
    const SELECT_LIMIT = 20000;

    /**
     * Scope for platform runs in wa_bulk_sends. The column is NOT NULL (it was written for
     * vendors, who always have an id), so "no store" is recorded as 0 rather than NULL.
     */
    const PLATFORM_SCOPE = WhatsAppBulkRun::PLATFORM_SCOPE;

    /** Delivery-log context for these sends. Read by the inbox to keep announcements out of it. */
    const CONTEXT = WhatsAppBulkRun::PLATFORM_CONTEXT;

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

        // A send in flight, if there is one. It carries on with nobody watching, so the composer
        // has to be able to pick the thread back up when this page is reopened.
        $activeRun = WhatsAppBulkRun::current(self::PLATFORM_SCOPE);

        return view('admin-views.whatsapp.bulk', array_merge(
            compact('connected', 'templates', 'templateError', 'counts', 'zones', 'optOutCount', 'tab', 'activeRun'),
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
     * Start a bulk send from the platform number.
     *
     * The composer used to drive the run itself — post 25 recipients, wait, post the next 25 —
     * which made the browser tab part of the machinery: closing it, or a laptop going to sleep,
     * stopped a 90,000-person announcement halfway with nothing to pick it up.
     *
     * Now one call books the run and hands it to the queue. What has to be refused is still
     * refused here, in front of the admin; the recipients themselves are claimed and messaged by
     * SendPlatformBulkWhatsAppJob, which the composer follows through progress().
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
            'audience'     => 'required|in:vendors,customers,manual',
            'mode'         => 'required|in:selected,all',
            'ids'          => 'array|max:' . self::SELECT_LIMIT,
            'ids.*'        => 'integer',
            'numbers'      => 'required_if:audience,manual|array|max:' . self::RUN_LIMIT,
            'numbers.*'    => 'string|max:32',
            'limit'        => 'required_if:mode,all|integer|min:1|max:' . self::RUN_LIMIT,
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
        WhatsAppBulkRun::ensureTable();

        $audience = $request->audience;
        $mode = $request->mode;

        // 'ids' cannot be a blanket required_if: a pasted list also sends with mode 'selected',
        // and it carries numbers instead. Checked here so an empty tick-list is refused out loud
        // rather than reported as a run that sent to nobody.
        if ($audience !== 'manual' && $mode === 'selected' && empty($request->input('ids'))) {
            return response()->json([
                'success' => false,
                'message' => 'Pick at least one recipient before sending.',
            ], 422);
        }

        // One platform run at a time. Two overlapping runs claim under their own ids, so the same
        // number could be reached by both — and a double-clicked Send is exactly how that happens
        // now that one press books a whole run rather than a single batch.
        if ($live = WhatsAppBulkRun::current(self::PLATFORM_SCOPE)) {
            return response()->json([
                'success' => false,
                'run_id'  => $live->run_id,
                'message' => 'A platform bulk send is already running. Wait for it to finish, or stop it first.',
            ], 409);
        }

        // A template is sent with the components it was approved with: one carrying an image,
        // video or document header needs that file on every message, or Graph refuses the lot
        // with "(#132012) Parameter format does not match format in the created template".
        $tpl = $this->platformTemplate((string) $request->template, (string) $request->language);
        $headerFormat = $this->headerFormatOf($tpl);
        if (in_array($headerFormat, WhatsAppService::MEDIA_HEADERS, true) && trim((string) $request->input('header_media')) === '') {
            return response()->json([
                'success' => false,
                'message' => 'This template has ' . ($headerFormat === 'IMAGE' ? 'an image' : 'a ' . strtolower($headerFormat))
                    . ' at the top, so a file has to be attached before it can be sent.',
            ], 422);
        }

        $ids = array_values(array_unique(array_map('intval', (array) $request->input('ids', []))));
        $numbers = array_values(array_unique(array_map('trim', (array) $request->input('numbers', []))));
        $limit = (int) $request->input('limit', 0);

        $requested = $audience === 'manual'
            ? count($numbers)
            : ($mode === 'selected' ? count($ids) : $limit);

        if ($requested < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Pick at least one recipient before sending.',
            ], 422);
        }

        $runId = (string) Str::uuid();

        WhatsAppBulkRun::open(self::PLATFORM_SCOPE, $runId, [
            'template'     => (string) $request->template,
            'language'     => (string) $request->language,
            'params'       => array_values((array) $request->input('params', [])),
            'header_media' => trim((string) $request->input('header_media')),
            'audience'     => $audience,
            'mode'         => $mode,
            'ids'          => $ids,
            'numbers'      => $numbers,
            'limit'        => $limit,
            'filters'      => $this->filters($request),
        ], $requested, [
            'scope'    => 'platform',
            'template' => (string) $request->template,
            'language' => (string) $request->language,
            'audience' => $audience,
        ]);

        SendPlatformBulkWhatsAppJob::dispatch($runId);

        return response()->json([
            'success' => true,
            'run_id'  => $runId,
            'total'   => $requested,
            'message' => 'Sending has started. It carries on in the background — you can close this page.',
        ]);
    }

    /** How a run is going. Polled by the composer, and readable long after it stopped watching. */
    public function progress(Request $request, $runId)
    {
        $progress = WhatsAppBulkRun::progress($runId, self::PLATFORM_SCOPE);

        return $progress
            ? response()->json(['success' => true] + $progress)
            : response()->json(['success' => false, 'message' => 'That send could not be found.'], 404);
    }

    /**
     * Stop a run early.
     *
     * The pass carrying it finishes the message it is on and puts the run down — everyone past
     * that point is left unclaimed and unmessaged.
     */
    public function stop(Request $request, $runId)
    {
        $stopped = WhatsAppBulkRun::requestStop(
            $runId,
            self::PLATFORM_SCOPE,
            'Stopped from the composer. Nobody past this point was messaged.'
        );

        return response()->json([
            'success' => $stopped,
            'message' => $stopped
                ? 'Stopping — the send halts within a few seconds.'
                : 'That send has already finished.',
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
