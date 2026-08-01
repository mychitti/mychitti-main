<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Services\WhatsAppAgent;
use App\Services\WhatsAppBilling;
use App\Services\WhatsAppCampaign;
use App\Services\WhatsAppService;
use App\Traits\WhatsAppAudience;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Drip campaigns: a series of approved templates sent to one audience, where the customer's reply
 * to each step decides whether they receive the next one.
 *
 * The builder only writes rows — nothing is sent from a web request. WhatsAppCampaign does the
 * sending from the five-minute scheduler, so a 3,000-person series can't hang on
 * max_execution_time and the vendor can close the tab.
 */
class WhatsAppCampaignController extends Controller
{
    use WhatsAppAudience;

    /** Recipients one campaign may hold. A bigger list is a reason to talk to the vendor first. */
    const MAX_RECIPIENTS = 5000;

    /** Templates in one series. Past this the vendor is nagging, not following up. */
    const MAX_STEPS = 6;

    /** Rows per page in the recipient tracker. */
    const PER_PAGE = 50;

    public function index(Request $request)
    {
        WhatsAppCampaign::ensureTables();
        $storeId = Helpers::get_store_id();
        $wa = WhatsAppService::make($storeId);

        $campaigns = DB::table('wa_campaigns')
            ->where('store_id', $storeId)
            ->orderByDesc('id')
            ->paginate(15);

        $summaries = [];
        $nextSteps = [];
        foreach ($campaigns as $campaign) {
            $summaries[$campaign->id] = WhatsAppCampaign::summary($campaign->id);
            $nextSteps[$campaign->id] = DB::table('wa_campaign_steps')
                ->where('campaign_id', $campaign->id)
                ->whereIn('status', ['pending', 'sending'])
                ->orderBy('step_no')
                ->first();
        }

        return view('vendor-views.whatsapp.campaigns.index', [
            'campaigns'  => $campaigns,
            'summaries'  => $summaries,
            'nextSteps'  => $nextSteps,
            'connected'  => $wa->source() === 'vendor',
            'active'     => WhatsAppBilling::isActive($storeId),
        ]);
    }

    public function create(Request $request)
    {
        WhatsAppCampaign::ensureTables();
        $storeId = Helpers::get_store_id();
        $wa = WhatsAppService::make($storeId);
        $connected = $wa->source() === 'vendor';

        $templates = [];
        $templateError = null;
        $clientCount = 0;
        $platformUserCount = 0;

        if ($connected) {
            $res = $wa->listTemplates();
            $trashedKeys = WhatsAppService::trashedTemplateKeys($storeId);
            $available = array_values(array_filter($res['data'], fn($tpl) => !in_array(
                strtolower(data_get($tpl, 'name') . '|' . data_get($tpl, 'language', 'en_US')),
                $trashedKeys,
                true
            )));

            $templates = $this->bulkTemplateOptions($available);
            $templateError = $res['success'] ? null : $res['error'];
            $clientCount = $this->clientQuery($storeId)->count();
            $platformUserCount = $this->outreachCount($storeId);
        }

        return view('vendor-views.whatsapp.campaigns.create', [
            'connected'         => $connected,
            'active'            => WhatsAppBilling::isActive($storeId),
            'templates'         => $templates,
            'templateError'     => $templateError,
            'clientCount'       => $clientCount,
            'platformUserCount' => $platformUserCount,
            'targets'           => WhatsAppCampaign::TARGETS,
            'defaultPositive'   => implode(', ', WhatsAppCampaign::DEFAULT_POSITIVE),
            'defaultNegative'   => implode(', ', WhatsAppCampaign::DEFAULT_NEGATIVE),
            'maxSteps'          => self::MAX_STEPS,
            'maxRecipients'     => self::MAX_RECIPIENTS,
            // Free-text replies are read by AI only on an AI Agent plan; everyone else gets the
            // word lists, so the page has to say which one this vendor is actually on.
            'aiReading'         => WhatsAppAgent::isAgent($storeId),
            'ownRate'           => WhatsAppBilling::messageCost('own'),
            'platformRate'      => WhatsAppBilling::messageCost('platform'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:150',
            'audience'              => 'required|in:clients,platform',
            'recipient_mode'        => 'required|in:all,selected,limit',
            'client_ids'            => 'required_if:recipient_mode,selected|array',
            'client_ids.*'          => 'integer',
            'recipient_limit'       => 'nullable|integer|min:1|max:' . self::MAX_RECIPIENTS,
            'positive_labels'       => 'nullable|string|max:500',
            'negative_labels'       => 'nullable|string|max:500',
            'steps'                 => 'required|array|min:1|max:' . self::MAX_STEPS,
            'steps.*.template'      => 'required|string|max:190',
            'steps.*.language'      => 'required|string|max:20',
            'steps.*.target'        => 'required|string|max:30',
            'steps.*.delay_hours'   => 'required|integer|min:0|max:2160',
            'steps.*.params'        => 'nullable|array',
            'steps.*.params.*.key'  => 'nullable|string|max:64',
        ]);

        $storeId = Helpers::get_store_id();
        $wa = WhatsAppService::make($storeId);

        if ($wa->source() !== 'vendor') {
            Toastr::error('Connect your own WhatsApp number before building a campaign.');
            return back()->withInput();
        }

        $audience = $request->input('audience');
        $recipients = $this->collectRecipients($request, $storeId, $audience);

        if ($recipients->isEmpty()) {
            Toastr::error('That audience has nobody in it right now. Import customers or widen the selection.');
            return back()->withInput();
        }

        $steps = array_values($request->input('steps'));
        foreach ($steps as $i => $step) {
            if (!array_key_exists($step['target'], WhatsAppCampaign::TARGETS)) {
                Toastr::error('Step ' . ($i + 1) . ' has an unknown audience filter.');
                return back()->withInput();
            }
        }

        WhatsAppCampaign::ensureTables();
        $now = now();

        $campaignId = DB::table('wa_campaigns')->insertGetId([
            'store_id'        => $storeId,
            'name'            => trim((string) $request->input('name')),
            'audience'        => $audience,
            'status'          => WhatsAppCampaign::STATUS_DRAFT,
            'positive_labels' => json_encode($this->labelList($request->input('positive_labels'), WhatsAppCampaign::DEFAULT_POSITIVE)),
            'negative_labels' => json_encode($this->labelList($request->input('negative_labels'), WhatsAppCampaign::DEFAULT_NEGATIVE)),
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        foreach ($steps as $i => $step) {
            DB::table('wa_campaign_steps')->insert([
                'campaign_id'   => $campaignId,
                'store_id'      => $storeId,
                'step_no'       => $i + 1,
                'template_name' => $step['template'],
                'language'      => $step['language'],
                'params'        => json_encode(array_values((array) ($step['params'] ?? []))),
                'target'        => $step['target'],
                // Step 1 opens the series, so its delay is meaningless — it goes out as soon as
                // the campaign starts.
                'delay_hours'   => $i === 0 ? 0 : max(0, (int) $step['delay_hours']),
                'status'        => 'pending',
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        $added = WhatsAppCampaign::addRecipients($campaignId, $storeId, $recipients);

        if ($added >= self::MAX_RECIPIENTS) {
            Toastr::info('Capped at ' . self::MAX_RECIPIENTS . ' recipients per campaign — run a second campaign for the rest.');
        }

        Toastr::success('Campaign saved with ' . $added . ' recipient' . ($added === 1 ? '' : 's') . '. Review it, then start the series.');
        return redirect()->route('vendor.whatsapp.campaigns.show', $campaignId);
    }

    public function show(Request $request, $id)
    {
        $campaign = $this->ownedCampaign($id);
        if (!$campaign) {
            Toastr::error('Campaign not found.');
            return redirect()->route('vendor.whatsapp.campaigns');
        }

        $stats = WhatsAppCampaign::stepStats($campaign->id);

        return view('vendor-views.whatsapp.campaigns.show', [
            'campaign'   => $campaign,
            'summary'    => WhatsAppCampaign::summary($campaign->id),
            'stats'      => $stats,
            'estimate'   => WhatsAppCampaign::estimatedCost($campaign, $stats),
            'rate'       => WhatsAppBilling::messageCost($campaign->audience === 'platform' ? 'platform' : 'own'),
            'targets'    => WhatsAppCampaign::TARGETS,
            'positive'   => WhatsAppCampaign::positiveLabels($campaign),
            'negative'   => WhatsAppCampaign::negativeLabels($campaign),
            'active'     => WhatsAppBilling::isActive((int) $campaign->store_id),
            'aiReading'  => WhatsAppAgent::isAgent((int) $campaign->store_id),
        ]);
    }

    /** Paginated recipient tracker for the detail page — filterable by how they answered. */
    public function recipients(Request $request, $id)
    {
        $campaign = $this->ownedCampaign($id);
        if (!$campaign) {
            return response()->json(['success' => false, 'message' => 'Campaign not found.'], 404);
        }

        $query = DB::table('wa_campaign_recipients')->where('campaign_id', $campaign->id);

        switch ($request->input('filter')) {
            case 'interested':
                $query->where('reply', 'interested');
                break;
            case 'not_interested':
                $query->where('reply', 'not_interested');
                break;
            case 'no_reply':
                $query->whereNull('reply');
                break;
            case 'excluded':
                $query->whereIn('state', ['excluded', 'opted_out', 'capped']);
                break;
            case 'active':
                $query->where('state', 'active');
                break;
        }

        if ($search = trim((string) $request->input('search'))) {
            // Own-list campaigns can be searched by number; platform numbers are never shown, so
            // searching them would be a way to read them back one digit at a time.
            $query->where(function ($q) use ($search, $campaign) {
                $q->where('name', 'like', "%{$search}%");
                if ($campaign->audience !== 'platform') {
                    $q->orWhere('phone', 'like', "%{$search}%");
                }
            });
        }

        $rows = $query->orderByDesc('reply_at')->orderBy('id')->paginate(self::PER_PAGE);

        $sends = DB::table('wa_campaign_sends as s')
            ->join('wa_campaign_steps as st', 'st.id', '=', 's.step_id')
            ->where('s.campaign_id', $campaign->id)
            ->whereIn('s.recipient_id', collect($rows->items())->pluck('id'))
            ->orderBy('st.step_no')
            ->get(['s.recipient_id', 's.status', 's.reply', 's.reply_label', 'st.step_no'])
            ->groupBy('recipient_id');

        $masked = $campaign->audience === 'platform';

        return response()->json([
            'success'  => true,
            'total'    => $rows->total(),
            'page'     => $rows->currentPage(),
            'pages'    => $rows->lastPage(),
            'rows'     => collect($rows->items())->map(fn($r) => [
                'id'          => $r->id,
                'name'        => $r->name ?: 'Customer',
                'phone'       => $masked ? $this->maskPhone($r->phone) : $r->phone,
                'state'       => $r->state,
                'reply'       => $r->reply,
                'reply_label' => $r->reply_label,
                'verdict_by'  => $r->verdict_by ?? null,
                'reply_at'    => $r->reply_at,
                'steps_sent'  => (int) $r->steps_sent,
                'sends'       => ($sends[$r->id] ?? collect())->map(fn($s) => [
                    'step'   => (int) $s->step_no,
                    'status' => $s->status,
                    'reply'  => $s->reply,
                ])->values(),
            ])->values(),
        ]);
    }

    public function start(Request $request, $id)
    {
        $campaign = $this->ownedCampaign($id);
        if (!$campaign) {
            Toastr::error('Campaign not found.');
            return redirect()->route('vendor.whatsapp.campaigns');
        }
        if (!WhatsAppBilling::isActive((int) $campaign->store_id)) {
            Toastr::error('Your WhatsApp subscription isn’t active. Activate it under WhatsApp → Plan & Billing first.');
            return back();
        }

        $outcome = WhatsAppCampaign::start((int) $campaign->id);
        $outcome['success'] ? Toastr::success($outcome['message']) : Toastr::error($outcome['message']);

        return back();
    }

    public function pause(Request $request, $id)
    {
        $campaign = $this->ownedCampaign($id);
        if ($campaign) {
            WhatsAppCampaign::setStatus((int) $campaign->id, WhatsAppCampaign::STATUS_PAUSED, 'Paused by you.');
            Toastr::success('Campaign paused. Nothing more goes out until you resume it.');
        }
        return back();
    }

    public function cancel(Request $request, $id)
    {
        $campaign = $this->ownedCampaign($id);
        if ($campaign) {
            WhatsAppCampaign::setStatus((int) $campaign->id, WhatsAppCampaign::STATUS_CANCELLED, 'Cancelled by you.');
            Toastr::success('Campaign cancelled. The tracking stays here for your records.');
        }
        return back();
    }

    /**
     * Send the due step's next batch right now instead of waiting for the scheduler. Handy on the
     * first step, when the vendor wants to watch the first messages land.
     */
    public function runNow(Request $request, $id)
    {
        $campaign = $this->ownedCampaign($id);
        if (!$campaign) {
            Toastr::error('Campaign not found.');
            return redirect()->route('vendor.whatsapp.campaigns');
        }
        if ($campaign->status !== WhatsAppCampaign::STATUS_RUNNING) {
            Toastr::error('Start the campaign first.');
            return back();
        }

        // Smaller than the scheduler's batch: this one runs inside the vendor's request.
        $report = WhatsAppCampaign::runCampaign($campaign, 25);

        if ($report['sent'] || $report['failed']) {
            Toastr::success('Step ' . $report['step'] . ': ' . $report['sent'] . ' sent, ' . $report['failed'] . ' failed.');
        } else {
            Toastr::info($report['message'] ?: 'Nothing to send right now.');
        }

        return back();
    }

    public function destroy(Request $request, $id)
    {
        $campaign = $this->ownedCampaign($id);
        if (!$campaign) {
            Toastr::error('Campaign not found.');
            return redirect()->route('vendor.whatsapp.campaigns');
        }
        if ($campaign->status === WhatsAppCampaign::STATUS_RUNNING) {
            Toastr::error('Pause the campaign before deleting it.');
            return back();
        }

        DB::table('wa_campaign_sends')->where('campaign_id', $campaign->id)->delete();
        DB::table('wa_campaign_recipients')->where('campaign_id', $campaign->id)->delete();
        DB::table('wa_campaign_steps')->where('campaign_id', $campaign->id)->delete();
        DB::table('wa_campaigns')->where('id', $campaign->id)->delete();

        Toastr::success('Campaign deleted.');
        return redirect()->route('vendor.whatsapp.campaigns');
    }

    /** Every recipient with their verdict and per-step outcome, as a CSV. */
    public function export(Request $request, $id)
    {
        $campaign = $this->ownedCampaign($id);
        if (!$campaign) {
            Toastr::error('Campaign not found.');
            return redirect()->route('vendor.whatsapp.campaigns');
        }

        $steps = DB::table('wa_campaign_steps')->where('campaign_id', $campaign->id)
            ->orderBy('step_no')->get(['id', 'step_no']);
        $masked = $campaign->audience === 'platform';

        $filename = 'campaign-' . $campaign->id . '-' . now()->format('Ymd-Hi') . '.csv';

        return response()->streamDownload(function () use ($campaign, $steps, $masked) {
            $out = fopen('php://output', 'w');

            $header = ['Name', 'Phone', 'Status', 'Reply', 'Reply text', 'Replied at'];
            foreach ($steps as $step) {
                $header[] = 'Step ' . $step->step_no;
            }
            fputcsv($out, $header);

            DB::table('wa_campaign_recipients')->where('campaign_id', $campaign->id)
                ->orderBy('id')->chunk(500, function ($rows) use ($out, $steps, $masked, $campaign) {
                    $sends = DB::table('wa_campaign_sends')
                        ->where('campaign_id', $campaign->id)
                        ->whereIn('recipient_id', $rows->pluck('id'))
                        ->get(['recipient_id', 'step_id', 'status', 'reply']);

                    $byRecipient = [];
                    foreach ($sends as $send) {
                        $byRecipient[$send->recipient_id][$send->step_id] = $send;
                    }

                    foreach ($rows as $row) {
                        $line = [
                            $row->name ?: 'Customer',
                            $masked ? $this->maskPhone($row->phone) : $row->phone,
                            $row->state,
                            $row->reply ?: 'no reply',
                            $row->reply_label,
                            $row->reply_at,
                        ];
                        foreach ($steps as $step) {
                            $send = $byRecipient[$row->id][$step->id] ?? null;
                            $line[] = $send
                                ? $send->status . ($send->reply ? ' / ' . $send->reply : '')
                                : 'not sent';
                        }
                        fputcsv($out, $line);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** Scoped lookup — a campaign id from another store must never resolve. */
    protected function ownedCampaign($id)
    {
        WhatsAppCampaign::ensureTables();
        return DB::table('wa_campaigns')
            ->where('id', (int) $id)
            ->where('store_id', Helpers::get_store_id())
            ->first();
    }

    /**
     * Freeze the audience at build time: a campaign messages the people who were in it when the
     * vendor pressed save. A list re-queried at each step would quietly pull in customers added
     * mid-series, who would then receive step 4 as their first contact.
     */
    protected function collectRecipients(Request $request, int $storeId, string $audience)
    {
        $limit = min(self::MAX_RECIPIENTS, (int) ($request->input('recipient_limit') ?: self::MAX_RECIPIENTS));

        if ($audience === 'platform') {
            return $this->outreachQuery($storeId)
                ->orderByRaw($this->phone10Sql('t.phone'))
                ->limit($limit)
                ->get()
                ->map(fn($r) => (object) [
                    'id'    => null,
                    'name'  => trim((string) $r->name),
                    'phone' => $r->phone,
                ]);
        }

        $query = $this->clientQuery($storeId);
        if ($request->input('recipient_mode') === 'selected') {
            $query->whereIn('id', (array) $request->input('client_ids'));
        }

        return $query->orderBy('f_name')->limit($limit)
            ->get(['id', 'f_name', 'phone'])
            ->map(fn($c) => (object) [
                'id'    => $c->id,
                'name'  => trim((string) $c->f_name),
                'phone' => $c->phone,
            ]);
    }

    /** Comma-separated answer labels → a clean list, falling back to the defaults. */
    protected function labelList($raw, array $fallback): array
    {
        $items = array_values(array_filter(array_map(
            fn($v) => trim(mb_strtolower($v)),
            explode(',', (string) $raw)
        ), fn($v) => $v !== ''));

        return $items ?: $fallback;
    }
}
