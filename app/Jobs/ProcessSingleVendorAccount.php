<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\DayBook;
use App\Models\PosToken;
use App\Models\StoreConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSingleVendorAccount implements ShouldQueue
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
        // DB::reconnect();
        // Sum all token transactions for this store on that date
        $total = PosToken::where('store_id', $this->storeId)
            ->where('payment_status', 'paid')
            ->where(function ($q) {
                $q->whereDate('created_at', $this->date)
                    ->orWhereDate('paid_at', $this->date);
            })
            ->sum('total');

        if ($total > 0) {
            $store_id = $this->storeId;
            $store_config = StoreConfig::where('store_id', $store_id)->first();

            // daybook entry 
            if (!$store_config || $store_config->pos_daybook_entry == 'end_of_day') {
                DayBook::create([
                    'amount'     => $total,
                    'store_id'   => $this->storeId,
                    'type'       => 'credit',
                    'particular' => 'POS Token Sales',
                ]);
            }

            //account entry 
            if (!$store_config || $store_config->pos_account_entry == 'end_of_day') {
                Account::create(
                    [
                        'store_id' => $this->storeId,
                        'date'      => $this->date,
                        'amount' => $total,
                        'user_type' => 'vendor',
                        'user_type_id' => $this->storeId,
                        'type' => 'income',
                        'account_type' => 'vendor',
                        'status' => 'completed',
                        'payment_mode' => 'cash',
                        'category' => 'POS Token',
                        'description' => 'POS Token Sales',
                        'ledger_account_type' => 9,
                        'purpose' => 'Sales',
                        'gst_amount' => 0,
                    ]
                );
            }
        }
    }
}
