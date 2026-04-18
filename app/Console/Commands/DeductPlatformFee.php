<?php

namespace App\Console\Commands;

use App\Models\AccountTransaction;
use App\Models\BusinessSetting;
use App\Models\Store;
use App\Models\StoreWallet;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeductPlatformFee extends Command
{
    protected $signature = 'platform-fee:deduct {--month= : Month in Y-m format, defaults to current month}';
    protected $description = 'Deduct monthly platform fee from vendor wallets (subscribed & unsubscribed)';

    public function handle()
    {
        $monthInput = $this->option('month') ?? Carbon::now()->format('Y-m');
        $this->info("Deducting platform fees for {$monthInput}...");

        $feeSubscribed   = (float) (BusinessSetting::where('key', 'platform_fee_subscribed')->value('value') ?? 0);
        $feeUnsubscribed = (float) (BusinessSetting::where('key', 'platform_fee_unsubscribed')->value('value') ?? 0);

        if ($feeSubscribed <= 0 && $feeUnsubscribed <= 0) {
            $this->info('No platform fees configured. Exiting.');
            return;
        }

        // Iterate all distinct vendor_ids that have wallets
        StoreWallet::chunk(100, function ($wallets) use ($feeSubscribed, $feeUnsubscribed, $monthInput) {
            foreach ($wallets as $wallet) {
                try {
                    $vendorId = $wallet->vendor_id;

                    // Skip if already deducted this month
                    $alreadyDeducted = AccountTransaction::where('from_type', 'store')
                        ->where('from_id', $vendorId)
                        ->where('reason', 'Platform Fee')
                        ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$monthInput])
                        ->exists();

                    if ($alreadyDeducted) {
                        $this->line("⏭ Already deducted for vendor {$vendorId}");
                        continue;
                    }

                    // Check subscription status
                    $isSubscribed = DB::table('vendor_subscriptions')
                        ->where('vendor_id', $vendorId)
                        ->where('plan_expiry', '>', now())
                        ->exists();

                    $fee = $isSubscribed ? $feeSubscribed : $feeUnsubscribed;

                    if ($fee <= 0) {
                        continue;
                    }

                    $currentBalance = $wallet->balance;

                    // Deduct by incrementing total_withdrawn
                    $wallet->increment('total_withdrawn', $fee);

                    // Record transaction
                    AccountTransaction::create([
                        'from_type'       => 'store',
                        'from_id'         => $vendorId,
                        'created_by'      => 'system',
                        'method'          => 'wallet',
                        'type'            => 'debit',
                        'amount'          => $fee,
                        'current_balance' => max(0, $currentBalance - $fee),
                        'reason'          => 'Platform Fee',
                        'ref'             => 'platform_fee_' . $monthInput,
                    ]);

                    $type = $isSubscribed ? 'subscribed' : 'unsubscribed';
                    $this->line("✅ Deducted {$fee} from vendor {$vendorId} ({$type})");
                } catch (\Exception $e) {
                    Log::error("Platform fee deduction failed for vendor {$wallet->vendor_id}: " . $e->getMessage());
                    $this->error("Error for vendor {$wallet->vendor_id}: " . $e->getMessage());
                }
            }
        });

        $this->info('✅ Platform fee deduction complete.');
    }
}
