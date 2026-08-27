<?php

namespace App\Console\Commands;

use App\Models\AccountTransaction;
use App\Models\StoreWallet;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Monthly wallet renewal for the paid WhatsApp receiving add-ons (currently "Lead Notifications").
 *
 * Before this existed, an add-on was a single manual purchase: the vendor clicked subscribe once,
 * `active_until` was pushed one month, and nothing ever touched the row again. Every one of them
 * lapsed silently a month later with `enabled` still set to 1, so the vendor panel kept reading
 * "Active" while `storeHasFeature()` had already started returning false and no WhatsApp lead
 * alert was being built at all. Six stores sat like that for weeks.
 *
 * Runs daily rather than monthly, for the same reason `whatsapp:bill` does: every store renews on
 * its own anniversary, and a store whose wallet was short is retried the next day inside GRACE_DAYS.
 *
 * `auto_renew` is the money switch and the only thing consulted here — a vendor who does not want
 * their wallet touched turns it off. `enabled` is the separate notification mute (Pause/Resume)
 * and is intentionally ignored: pausing alerts for a week should not cancel the subscription.
 */
class RenewReceivingAddOns extends Command
{
    protected $signature = 'whatsapp:renew-addons
                            {--store=   : Renew only this store id}
                            {--dry-run  : Show what would be charged without touching wallets}
                            {--grace=7  : Days past expiry to keep retrying before giving up}';

    protected $description = 'Renew paid WhatsApp receiving add-ons from the vendor wallet, and warn when the wallet is short';

    public function handle(): int
    {
        WhatsAppService::ensureReceivingTable();

        $today  = now()->toDateString();
        $grace  = max(0, (int) $this->option('grace'));
        $dryRun = (bool) $this->option('dry-run');

        // Due = expiring today, or already expired but still inside the grace window. Anything
        // older than that is left alone: charging a vendor for a month that has already passed
        // is worse than letting them re-subscribe by hand.
        $due = DB::table('wa_receiving_features')
            ->where('auto_renew', 1)
            ->whereNotNull('active_until')
            ->where('active_until', '<=', $today)
            ->where('active_until', '>=', now()->subDays($grace)->toDateString())
            ->when($this->option('store'), fn($q) => $q->where('store_id', (int) $this->option('store')))
            ->orderBy('store_id')
            ->get();

        if ($due->isEmpty()) {
            $this->info('Nothing due.');
            return self::SUCCESS;
        }

        $renewed = $short = $skipped = 0;

        foreach ($due as $row) {
            try {
                $result = $this->renewOne($row, $today, $dryRun);
                $result === 'renewed' ? $renewed++ : ($result === 'short' ? $short++ : $skipped++);
            } catch (\Throwable $e) {
                $skipped++;
                Log::error('ADDON-RENEW: failed for store ' . $row->store_id . ' — ' . $e->getMessage());
                $this->error("  store {$row->store_id} — {$e->getMessage()}");
            }
        }

        $this->info("Renewed {$renewed}, wallet short {$short}, skipped {$skipped}.");
        return self::SUCCESS;
    }

    /** @return string 'renewed' | 'short' | 'skipped' */
    private function renewOne(object $row, string $today, bool $dryRun): string
    {
        $meta  = WhatsAppService::RECEIVING_FEATURES[$row->feature] ?? null;
        $label = $meta['label'] ?? $row->feature;

        // Never bill the same row twice on one day, however many times this is invoked.
        if ($row->last_renewed_on === $today) {
            $this->line("  store {$row->store_id} — already renewed today, skipping");
            return 'skipped';
        }

        $store = DB::table('stores')->where('id', $row->store_id)->first();
        if (!$store) {
            $this->line("  store {$row->store_id} — store row missing, skipping");
            return 'skipped';
        }

        // The price paid last time wins over the current list price, so a vendor on an older
        // rate is not silently moved onto a new one by an automatic charge.
        $price = (float) ($row->price > 0 ? $row->price : ($meta['price'] ?? 0));

        // Admin grants sit at ₹0 — extend them without involving a wallet at all.
        if ($price <= 0) {
            if ($dryRun) {
                $this->line("  store {$row->store_id} — would extend {$label} free (₹0 grant)");
                return 'renewed';
            }
            $this->extend($row, $today);
            $this->info("  store {$row->store_id} — {$label} extended free (₹0 grant)");
            return 'renewed';
        }

        // Wallet is keyed by the store owner (vendor user), not the store.
        $vendorId = $store->vendor_id;
        $wallet   = StoreWallet::where('vendor_id', $vendorId)->first();
        $balance  = $wallet ? (float) $wallet->total_earning : 0.0;

        if (!$wallet || $balance < $price) {
            if ($dryRun) {
                $this->warn("  store {$row->store_id} — {$label} SHORT (need " . _price($price) . ", has " . _price($balance) . ')');
                return 'short';
            }
            $this->warnVendor($row, $label, $price, $balance, $today);
            $this->warn("  store {$row->store_id} — {$label} wallet short, vendor warned");
            return 'short';
        }

        if ($dryRun) {
            $this->line("  store {$row->store_id} — would charge " . _price($price) . " for {$label}");
            return 'renewed';
        }

        DB::transaction(function () use ($vendorId, $price, $label, $row, $today) {
            $wallet = StoreWallet::where('vendor_id', $vendorId)->lockForUpdate()->first();

            // Re-check under the lock: the balance may have moved since the read above.
            if ((float) $wallet->total_earning < $price) {
                throw new \RuntimeException('balance fell below the price before the charge');
            }

            $wallet->decrement('total_earning', $price);

            $txn = new AccountTransaction();
            $txn->current_balance = $wallet->total_earning;
            $txn->from_type  = 'store';
            $txn->amount     = $price;
            $txn->from_id    = $vendorId;
            $txn->method     = 'wallet';
            $txn->action     = 'debit';
            $txn->reason     = 'WhatsApp Receiving — ' . $label . ' (auto-renew)';
            $txn->created_by = 'system';
            $txn->save();

            $this->extend($row, $today);
        });

        Log::info('ADDON-RENEW: renewed', [
            'store' => $row->store_id, 'feature' => $row->feature, 'price' => $price,
        ]);
        $this->info("  store {$row->store_id} — {$label} renewed for " . _price($price));

        return 'renewed';
    }

    /**
     * Push active_until one month on. Measured from the existing expiry when that is still in
     * the future, so renewing early never costs the vendor the days they already paid for.
     */
    private function extend(object $row, string $today): void
    {
        $base = ($row->active_until && $row->active_until >= $today)
            ? Carbon::parse($row->active_until)
            : now();

        DB::table('wa_receiving_features')->where('id', $row->id)->update([
            'active_until'    => $base->copy()->addMonth()->toDateString(),
            'last_renewed_on' => $today,
            'last_alert_on'   => null,
            'updated_at'      => now(),
        ]);
    }

    /** WhatsApp + panel alert that the wallet could not cover the renewal, at most once a day. */
    private function warnVendor(object $row, string $label, float $price, float $balance, string $today): void
    {
        Log::info('ADDON-RENEW: wallet short', [
            'store' => $row->store_id, 'feature' => $row->feature,
            'price' => $price, 'balance' => $balance, 'active_until' => $row->active_until,
        ]);

        if ($row->last_alert_on === $today) {
            return;
        }

        // Stamped before sending: a send that throws must not re-alert on the next invocation.
        DB::table('wa_receiving_features')->where('id', $row->id)
            ->update(['last_alert_on' => $today, 'updated_at' => now()]);

        try {
            WhatsAppService::sendAddOnRenewalFailedNotification(
                (int) $row->store_id, $label, $price, $balance, $row->active_until
            );
        } catch (\Throwable $e) {
            Log::warning('ADDON-RENEW: WhatsApp warning failed for store ' . $row->store_id . ' — ' . $e->getMessage());
        }

        // The panel notification is not subject to WhatsApp's 24-hour window, so this is the
        // half of the warning that always lands.
        try {
            _inAppNotification(
                'Wallet too low to renew ' . $label,
                'We could not renew your ' . $label . ' add-on — it needs ' . _price($price)
                    . ' and your wallet has ' . _price($balance) . '. Top up to keep receiving lead alerts on WhatsApp.',
                null,
                $row->store_id,
                parse_url(route('vendor.service.lead-settings'), PHP_URL_PATH),
                'vendor'
            );
        } catch (\Throwable $e) {
            Log::warning('ADDON-RENEW: panel alert failed for store ' . $row->store_id . ' — ' . $e->getMessage());
        }
    }
}
