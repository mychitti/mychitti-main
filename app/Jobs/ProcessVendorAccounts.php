<?php 
namespace App\Jobs;

use App\Models\Account;
use App\Models\PosToken;
use App\Models\Store;
use App\Models\Vendor;
use App\Models\TokenTransaction;
use App\Models\VendorAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessVendorAccounts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $date;

    public function __construct($date)
    {
        $this->date = $date;
    }

    public function handle()
    {
        $stores = Store::withoutGloabalScopes()->all();

        foreach ($stores as $store) {
            $total = PosToken::where('store_id', $store->id)
                        ->whereDate('created_at', $this->date)
                        ->sum('total');

            if ($total > 0) {
                Account::create(
                    [
                        'store_id' => $store->id,
                        'date'      => $this->date,
                    ],
                    [
                        'amount' => $total,
                    ]
                );
            }
        }
    }
}
