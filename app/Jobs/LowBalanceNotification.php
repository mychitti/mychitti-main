<?php

namespace App\Jobs;

use App\Models\BusinessSetting;
use App\Models\Store;
use App\Models\StoreBankAccount;
use App\Models\StoreBankTransaction;
use App\Models\StoreDailyBalance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class LowBalanceNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $storeId;
    protected $date;

    public function __construct($storeId, $date)
    {
        $this->storeId = $storeId;
        $this->date = $date;
    }

    public function handle()
    {
        $today = today();
          $checkType = BusinessSetting::where('key', 'min_balance_notif')->first();
        $checkType = $checkType ? $checkType->value : 'weekly'; 

        $stores = Store::withoutGlobalScopes()->get();

        foreach ($stores as $store) {
            $banks = StoreBankAccount::where('store_id', $store->id)->get();

            foreach ($banks as $bank) {

                $closing = StoreBankTransaction::where('bank_id', $bank->id)
                    ->whereDate('value_date', '<=', $today)
                    ->sum(DB::raw("CASE WHEN type='credit' THEN amount ELSE -amount END"));

                StoreDailyBalance::updateOrCreate(
                    ['store_id' => $store->id, 'bank_id' => $bank->id, 'date' => $today],
                    ['closing_balance' => $closing]
                );
            }
        }

        if ($checkType == 'weekly') {
            $startDate = $today->copy()->subDays(6); // last 7 days including today
        } else {
            $startDate = $today->copy()->startOfMonth(); // from month start to today
        }

        $averages = StoreDailyBalance::select(
            'store_id',
            'bank_id',
            DB::raw("AVG(closing_balance) as avg_balance")
        )
            ->whereBetween('date', [$startDate, $today])
            ->groupBy('store_id', 'bank_id')
            ->get();

        foreach ($averages as $acc) {

            $bank = StoreBankAccount::find($acc->bank_id);
            $store = Store::find($acc->store_id);

            if ($acc->avg_balance < $bank->minimum_balance) {

                $title = 'Low Balance Alert (' . $bank->bank_name . ')';

                $msg = "Your bank account ending with " . $bank->account_number .
                    " has an average balance of Rs." . round($acc->avg_balance, 2) .
                    " which is below the required minimum balance of Rs." . $bank->minimum_balance . ".Please add funds to avoid charges.";

                $url = route('vendor.account.banking.bank-account.index');

                _inAppNotification($title, $msg, null, $store->id, $url, 'vendor');
            }
        }
    }
}
