<?php

namespace App\Jobs\Scheduled;

use App\Services\DailyHospitalReport;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * The day's numbers, on WhatsApp, to the hospitals that asked for them.
 *
 * Runs hourly and sends to whichever hospitals set that hour as their send time, so every
 * hospital gets its report when its day actually ends — a 24-hour clinic and a 6 p.m. dental
 * practice do not share one deadline.
 *
 * Sent from the PLATFORM's number, never the hospital's. This is MyChitti reporting to its own
 * customer about their account, not the hospital messaging a patient: it needs one template
 * approved once rather than one per hospital, it reaches hospitals that never connected
 * WhatsApp, and no hospital pays Meta to message its own owner.
 */
class SendDailyHospitalReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600;

    /** Overrides the hour match — used by the "send me a test" button and by CLI runs. */
    public function __construct(public ?int $onlyStoreId = null)
    {
    }

    public function handle(): void
    {
        // The config table is named storeConfigs on some installs and store_configs on others,
        // so it is resolved from the model rather than assumed.
        $cfgTable = (new \App\Models\StoreConfig)->getTable();

        if (!Schema::hasColumn($cfgTable, 'hmis_daily_report_enabled')) {
            return; // No hospital has ever opened the setting.
        }

        $wa = WhatsAppService::make(null, 'daily_report');
        if (!$wa->isConfigured()) {
            Log::warning('Daily hospital report: the platform WhatsApp number is not configured — nothing sent.');
            return;
        }

        $stores = DB::table($cfgTable . ' as sc')
            ->join('stores as s', 's.id', '=', 'sc.store_id')
            ->where('sc.hmis_daily_report_enabled', 1)
            ->when($this->onlyStoreId, fn($q) => $q->where('s.id', $this->onlyStoreId))
            ->select('s.id', 's.name', 's.phone', 'sc.hmis_daily_report_metrics', 'sc.hmis_daily_report_time')
            ->get();

        foreach ($stores as $store) {
            try {
                $this->sendFor($wa, $store);
            } catch (\Throwable $e) {
                // One hospital's bad data must not stop everyone else's report.
                Log::warning('Daily report failed for store ' . $store->id . ': ' . $e->getMessage());
            }
        }
    }

    /**
     * Send one hospital its report right now, and say what happened.
     *
     * Behind the "Send a test now" button: same code path as the scheduled run — same figures,
     * same template, same number — so a test that arrives proves the real one will. The hour
     * check and the quiet-day skip are the only things bypassed, otherwise a test at 3 p.m. on a
     * slow day would silently do nothing and look broken.
     *
     * @return array{success: bool, message: string}
     */
    public static function test(int $storeId): array
    {
        $job = new self($storeId);

        $cfgTable = (new \App\Models\StoreConfig)->getTable();
        if (!Schema::hasColumn($cfgTable, 'hmis_daily_report_enabled')) {
            return ['success' => false, 'message' => 'Save your settings once before sending a test.'];
        }

        $wa = WhatsAppService::make(null, 'daily_report');
        if (!$wa->isConfigured()) {
            return ['success' => false, 'message' => 'The platform WhatsApp number is not set up yet — ask support to enable it.'];
        }

        $store = DB::table($cfgTable . ' as sc')
            ->join('stores as s', 's.id', '=', 'sc.store_id')
            ->where('s.id', $storeId)
            ->select('s.id', 's.name', 's.phone', 'sc.hmis_daily_report_metrics', 'sc.hmis_daily_report_time')
            ->first();

        if (!$store) {
            return ['success' => false, 'message' => 'Save your settings once before sending a test.'];
        }

        if (strlen(preg_replace('/[^0-9]/', '', (string) $store->phone)) < 10) {
            return ['success' => false, 'message' => 'Add a phone number to your store profile — there is nowhere to send the report.'];
        }

        try {
            $result = $job->sendFor($wa, $store);
        } catch (\Throwable $e) {
            Log::warning('Daily report test failed for store ' . $storeId . ': ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not build the report: ' . $e->getMessage()];
        }

        return $result['success']
            ? ['success' => true, 'message' => 'Test report sent to ' . $store->phone . '.']
            : ['success' => false, 'message' => $result['message']];
    }

    /** @return array{success: bool, message: string} */
    private function sendFor(WhatsAppService $wa, object $store): array
    {
        // The hour the hospital chose. A test run ignores it.
        if (!$this->onlyStoreId) {
            $hour = (int) substr((string) ($store->hmis_daily_report_time ?: '21:00'), 0, 2);
            if ($hour !== (int) now()->format('G')) {
                return ['success' => false, 'message' => 'Not this hospital\'s hour.'];
            }
        }

        $phone = preg_replace('/[^0-9]/', '', (string) $store->phone);
        if (strlen($phone) < 10) {
            return ['success' => false, 'message' => 'No phone number on the store profile.'];
        }

        // One report per hospital per day, whatever else re-runs the schedule.
        $context = 'daily report:' . $store->id . ':' . now()->toDateString();
        if (!$this->onlyStoreId && $this->alreadySent($store->id, $context)) {
            return ['success' => false, 'message' => 'Already sent today.'];
        }

        $metrics = json_decode((string) $store->hmis_daily_report_metrics, true);
        $metrics = is_array($metrics) && $metrics ? $metrics : DailyHospitalReport::DEFAULT_METRICS;

        $report = DailyHospitalReport::for((int) $store->id);
        $built  = $report->build($metrics);

        if (!$built) {
            return ['success' => false, 'message' => 'Nothing is ticked to report.'];
        }

        // A day with nothing in it is not worth a message — and on the platform's number it is
        // not worth a conversation fee either.
        if ($report->isQuiet($built) && !$this->onlyStoreId) {
            return ['success' => false, 'message' => 'Nothing happened today.'];
        }

        $lines = [];
        foreach ($built as $row) {
            $lines[] = $row['label'] . ': ' . $row['value'];
        }

        // A test carries the same context plus a marker, so it never counts as the day's report
        // and can be sent as often as needed while getting the template right.
        if ($this->onlyStoreId) {
            $context .= ':test';
        }

        // Template parameters may not contain newlines, so the figures go as one comma-separated
        // parameter. {{1}} hospital name, {{2}} date, {{3}} the figures.
        $components = [[
            'type' => 'body',
            'parameters' => array_map(fn($v) => ['type' => 'text', 'text' => $v], [
                $store->name ?: 'your hospital',
                now()->format('d M Y'),
                // Middle dot, not a pipe: the template form's "example" field is pipe-separated,
                // so a pipe here would split one parameter into several when Meta reviews it.
                implode(' · ', $lines),
            ]),
        ]];

        $template = config('services.whatsapp.daily_report_template', 'daily_report');
        $language = config('services.whatsapp.daily_report_language', 'en_US');

        $result = $wa->sendTemplate($phone, $template, $language, $components, $context);

        if (empty($result['success'])) {
            $why = $result['error'] ?? $result['message'] ?? 'unknown error';
            Log::warning('Daily report not delivered for store ' . $store->id . ': ' . $why
                . ' (template "' . $template . '" must exist and be approved on the platform number)');

            return ['success' => false, 'message' => 'WhatsApp refused it: ' . $why
                . ' — the template "' . $template . '" must be approved on the platform number.'];
        }

        return ['success' => true, 'message' => 'Sent.'];
    }

    private function alreadySent(int $storeId, string $context): bool
    {
        if (!Schema::hasTable('whatsapp_messages')) {
            return false;
        }

        return DB::table('whatsapp_messages')
            ->where('context', $context)
            ->where('status', '!=', 'failed')
            ->exists();
    }
}
