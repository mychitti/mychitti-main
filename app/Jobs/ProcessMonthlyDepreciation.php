<?php

namespace App\Jobs;

use App\CentralLogics\Helpers;
use App\Models\StoreAsset;
use App\Models\AssetDepreciation;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMonthlyDepreciation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $today = Carbon::now()->endOfMonth();
        $stores = Store::withoutGlobalScopes()->get();
        // ->where('id', '26')

        foreach ($stores as $store) {
            $storeId = $store->id;

            // Log::info("Running depreciation for store ID: {$storeId}");

            StoreAsset::with('inventoryItem')
                ->where('store_id', $storeId)
                ->chunk(100, function ($assets) use ($today, $storeId) {

                    // Log::info("Found assets count: " . $assets->count(), ['store_id' => $storeId]);


                    foreach ($assets as $asset) {
                        $openingValue = $asset->current_value;

                        if ($openingValue <= 0) {
                            continue;
                        }

                        $totalMonths = match ($asset->useful_life_unit) {
                            'years' => $asset->useful_life_count * 12,
                            'months' => $asset->useful_life_count,
                            'days' => ceil($asset->useful_life_count / 30),
                            default => $asset->useful_life_count * 12,
                        };
                        $totalDepreciationAmount = 0;
                        if ($asset->depreciation_method === 'straight_line') {
                            $perUnitDepreciation = round($asset->cost / $totalMonths, 2);
                            $totalDepreciationAmount = $perUnitDepreciation * $asset->quantity;
                        } elseif ($asset->depreciation_method === 'reducing_balance') {
                            $annualRate = 1 / ($totalMonths / 12);
                            $monthlyRate = $annualRate / 12;
                            $totalDepreciationAmount = round($openingValue * $monthlyRate, 2);
                        }

                        if ($totalDepreciationAmount <= 0) {
                            continue;
                        }

                        $closingValue = max(0, $openingValue - $totalDepreciationAmount);
                        $asset->current_value = $closingValue;
                        $asset->save();

                        \App\Models\AssetDepreciation::create([
                            'store_id' => $storeId,
                            'asset_id' => $asset->id,
                            'depreciation_date' => $today,
                            'opening_value' => $openingValue,
                            'depreciation_amount' => $totalDepreciationAmount,
                            'closing_value' => $closingValue,
                        ]);

                        // Log::info("Depreciation saved for asset", [
                        //     'asset_id' => $asset->id,
                        //     'store_id' => $storeId,
                        //     'amount' => $totalDepreciationAmount
                        // ]);

                        $debit_account = Helpers::ensureDepreciationExpenseAccount();
                        $credit_account = Helpers::ensureAccumulatedDepreciationAccount($asset->inventoryItem?->item_name, $storeId);

                        try {
                            // Log::info('Depreciation entry start', [
                            //     'store_id' => $storeId,
                            //     'item' => $asset->inventoryItem?->item_name,
                            //     'credit_account' => $credit_account,
                            //     'debit_account' => $debit_account,
                            //     'amount' => $totalDepreciationAmount,
                            // ]);

                            $ledgerData = [
                                'date' => $today,
                                'amount' => round($totalDepreciationAmount, 2),
                                'status' => 'approved',
                                'voucher_type' => 'Journal',
                                'description' => "Monthly depreciation for {$asset->quantity} x {$asset->inventoryItem?->item_name}",
                            ];
                            _masterLedgerEntry($ledgerData, $credit_account, $debit_account, 'store', 'asset', null, $storeId);

                            // Log::info('Depreciation entry inserted successfully', [
                            //     'store_id' => $storeId,
                            //     'asset_id' => $asset->id,
                            // ]);
                        } catch (\Throwable $th) {
                            // Log::error('Depreciation error: ' . $th->getMessage());
                        }
                    }
                });
        }
    }
}
