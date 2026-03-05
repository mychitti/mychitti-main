<?php

namespace App\Jobs;

use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessTokenPostSave implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $storeId;
    protected $tokenTotal;
    protected $paymentStatus;
    protected $lowStockItems;
    protected $shouldCreateAccount; 

    public function __construct($storeId, $tokenTotal, $paymentStatus, $lowStockItems, $shouldCreateAccount)
    {
        $this->storeId = $storeId;
        $this->tokenTotal = $tokenTotal;
        $this->paymentStatus = $paymentStatus;
        $this->lowStockItems = $lowStockItems;
        $this->shouldCreateAccount = $shouldCreateAccount;
    }

    public function handle()
    {
        // Account entry
        if ($this->paymentStatus == 'paid' && $this->shouldCreateAccount) {
            $account = new Account();
            $account->store_id = $this->storeId;
            $account->account_type = 'vendor';
            $account->user_type_id = $this->storeId;
            $account->user_type = 'vendor';
            $account->date = date('Y-m-d');
            $account->type = 'income';
            $account->status = 'completed';
            $account->payment_mode = 'cash';
            $account->category = 'POS Token';
            $account->description = 'POS Token Sales';
            $account->purpose = 'Sales';
            $account->gst_amount = 0;
            $account->ledger_account_type = 9;
            $account->amount = $this->tokenTotal;
            $account->save();
        }

        // Low stock notifications
        foreach ($this->lowStockItems as $stockItem) {
            _notifLowStock((object) $stockItem['item'], $stockItem['qty_left']);
        }
    }
}
