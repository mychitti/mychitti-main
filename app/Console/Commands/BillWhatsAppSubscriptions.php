<?php

namespace App\Console\Commands;

use App\Services\WhatsAppBilling;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Recurring WhatsApp Business Platform billing. Three independent passes, in order:
 *   1. platform fee renewals (each store on its own anniversary),
 *   2. AI Agent plan renewals,
 *   3. per-message usage for the calendar month that just ended, billed in arrears.
 *
 * Runs daily. A vendor whose wallet is short is marked past_due and retried on the next run —
 * WhatsAppBilling::GRACE_DAYS decides how long service keeps working while that happens. Every
 * charge is idempotent on its ref (one per store per billing month), so re-running this command
 * can never double-bill, and one pass failing never blocks the others.
 */
class BillWhatsAppSubscriptions extends Command
{
    protected $signature = 'whatsapp:bill
                            {--store= : Bill only this store id}
                            {--month= : Message-usage month in Y-m format, defaults to last month}
                            {--dry-run : Show what would be charged without touching wallets}';

    protected $description = 'Charge the monthly WhatsApp Business Platform fee to vendor wallets';

    public function handle()
    {
        WhatsAppBilling::ensureTables();

        $due = DB::table('wa_subscriptions')
            ->whereIn('status', ['active', 'past_due'])
            ->whereNotNull('current_period_end')
            ->whereDate('current_period_end', '<=', now()->toDateString())
            ->when($this->option('store'), fn($q) => $q->where('store_id', (int) $this->option('store')))
            ->orderBy('store_id')
            ->get();

        if ($due->isEmpty()) {
            $this->info('No WhatsApp subscriptions due today.');
            return self::SUCCESS;
        }

        $this->info('Renewing ' . $due->count() . ' WhatsApp subscription(s)...');
        $charged = 0;
        $failed  = 0;

        foreach ($due as $sub) {
            $amount = WhatsAppBilling::monthlyFee() + ($sub->account_manager ? WhatsAppBilling::accountManagerFee() : 0);
            $total  = WhatsAppBilling::withTax($amount);

            if ($this->option('dry-run')) {
                $this->line("• store {$sub->store_id} — would charge " . $total . " (period ended {$sub->current_period_end})");
                continue;
            }

            try {
                $res = WhatsAppBilling::renew((int) $sub->store_id);
            } catch (\Throwable $e) {
                Log::error('WA renewal crashed for store ' . $sub->store_id . ': ' . $e->getMessage());
                $this->error("✗ store {$sub->store_id} — {$e->getMessage()}");
                $failed++;
                continue;
            }

            if ($res['success']) {
                $charged++;
                $this->line("✅ store {$sub->store_id} — {$res['message']}");
                continue;
            }

            $failed++;
            $this->error("✗ store {$sub->store_id} — {$res['message']}");
            $this->notifyFailure((int) $sub->store_id, $sub, $res['message'], 'WhatsApp');
        }

        [$agentCharged, $agentFailed] = $this->renewAgentPlans();
        $usageCharged = $this->chargeMessageUsage();

        $this->info("WhatsApp billing complete. Platform renewed: {$charged}, failed: {$failed}. "
            . "AI Agent renewed: {$agentCharged}, failed: {$agentFailed}. Usage billed: {$usageCharged}.");
        return self::SUCCESS;
    }

    /**
     * Per-message charges for the calendar month that just ended, billed in arrears.
     * Idempotent per store per month, so running daily lands exactly one usage line.
     */
    protected function chargeMessageUsage(): int
    {
        $month = $this->option('month') ?: now()->subMonthNoOverflow()->format('Y-m');

        $stores = DB::table('wa_subscriptions')
            ->whereIn('status', ['active', 'past_due'])
            ->when($this->option('store'), fn($q) => $q->where('store_id', (int) $this->option('store')))
            ->orderBy('store_id')
            ->pluck('store_id');

        $billed = 0;
        foreach ($stores as $storeId) {
            $storeId = (int) $storeId;

            if ($this->option('dry-run')) {
                $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
                $usage = WhatsAppBilling::usageFor($storeId, $start->toDateTimeString(), $start->copy()->endOfMonth()->toDateTimeString());
                if ($usage['amount'] > 0) {
                    $this->line("• store {$storeId} — would bill {$usage['total']} messages "
                        . "({$usage['own']} own / {$usage['platform']} MyChitti list) = {$usage['amount']} + GST");
                }
                continue;
            }

            try {
                $res = WhatsAppBilling::chargeUsage($storeId, $month);
            } catch (\Throwable $e) {
                Log::error('WA usage billing crashed for store ' . $storeId . ': ' . $e->getMessage());
                $this->error("✗ store {$storeId} (usage) — {$e->getMessage()}");
                continue;
            }

            if (!empty($res['skipped'])) {
                continue;
            }

            if ($res['success']) {
                $billed++;
                $this->line("✅ store {$storeId} (usage {$month}) — {$res['message']}");
            } else {
                $this->error("✗ store {$storeId} (usage {$month}) — {$res['message']}");
            }
        }

        return $billed;
    }

    /**
     * AI Agent plans renew on their own cycle — the platform fee and the agent plan are
     * separate charges, so one failing never blocks the other.
     */
    protected function renewAgentPlans(): array
    {
        $due = DB::table('wa_agent_subscriptions')
            ->whereIn('status', ['active', 'past_due'])
            ->whereNotNull('current_period_end')
            ->whereDate('current_period_end', '<=', now()->toDateString())
            ->when($this->option('store'), fn($q) => $q->where('store_id', (int) $this->option('store')))
            ->orderBy('store_id')
            ->get();

        $charged = 0;
        $failed  = 0;

        foreach ($due as $sub) {
            if ($this->option('dry-run')) {
                $this->line("• store {$sub->store_id} — would renew AI Agent plan '{$sub->plan}' (period ended {$sub->current_period_end})");
                continue;
            }

            try {
                $res = WhatsAppBilling::renewAgent((int) $sub->store_id);
            } catch (\Throwable $e) {
                Log::error('WA agent renewal crashed for store ' . $sub->store_id . ': ' . $e->getMessage());
                $this->error("✗ store {$sub->store_id} (AI Agent) — {$e->getMessage()}");
                $failed++;
                continue;
            }

            if ($res['success']) {
                $charged++;
                $this->line("✅ store {$sub->store_id} (AI Agent) — {$res['message']}");
                continue;
            }

            $failed++;
            $this->error("✗ store {$sub->store_id} (AI Agent) — {$res['message']}");
            $this->notifyFailure((int) $sub->store_id, $sub, $res['message'], 'AI Agent');
        }

        return [$charged, $failed];
    }

    /**
     * Tell the vendor their renewal failed — once per calendar day, so a vendor sitting on an
     * empty wallet for a week gets one reminder a day rather than a stack of identical alerts.
     */
    protected function notifyFailure(int $storeId, $sub, string $reason, string $what): void
    {
        $endsOn = Carbon::parse($sub->current_period_end)->addDays(WhatsAppBilling::GRACE_DAYS);

        if (!\Illuminate\Support\Facades\Cache::add("wa_bill_fail:{$what}:{$storeId}:" . now()->toDateString(), 1, 86400)) {
            return;
        }

        _inAppNotification(
            $what . ' renewal failed',
            $reason . ' Recharge your wallet to keep ' . $what . ' running — it stops on ' . $endsOn->format('d M Y') . '.',
            null,
            $storeId,
            route('vendor.whatsapp.billing'),
            'vendor'
        );
    }
}
