<?php

namespace App\Console\Commands;

use App\Services\WhatsAppBilling;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Recurring WhatsApp Business Platform billing: monthly plan renewals, each store on its own
 * anniversary, at whichever tier it is on.
 *
 * Per-message charges are NOT handled here. They come out of the wallet at dispatch
 * (WhatsAppBilling::chargeMessage), so by the time this runs there is nothing left to settle —
 * billing them again from whatsapp_messages would charge the vendor twice.
 *
 * Runs daily. A vendor whose wallet is short is marked past_due and retried on the next run —
 * WhatsAppBilling::GRACE_DAYS decides how long service keeps working while that happens. Every
 * charge is idempotent on its ref (one per store per billing month), so re-running this command
 * can never double-bill.
 */
class BillWhatsAppSubscriptions extends Command
{
    protected $signature = 'whatsapp:bill
                            {--store= : Bill only this store id}
                            {--dry-run : Show what would be charged without touching wallets}';

    protected $description = 'Charge the monthly WhatsApp Business Platform plan fee to vendor wallets';

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
            $plan   = $sub->plan ?? WhatsAppBilling::DEFAULT_PLAN;
            $amount = WhatsAppBilling::monthlyFee($plan) + ($sub->account_manager ? WhatsAppBilling::accountManagerFee() : 0);
            $total  = WhatsAppBilling::withTax($amount);

            if ($this->option('dry-run')) {
                $this->line("• store {$sub->store_id} — would charge " . $total . " for '{$plan}' (period ended {$sub->current_period_end})");
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

        $this->info("WhatsApp billing complete. Plans renewed: {$charged}, failed: {$failed}.");
        return self::SUCCESS;
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
