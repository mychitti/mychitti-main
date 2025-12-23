<?php

namespace App\Imports;

use App\CentralLogics\Helpers;
use App\Models\AccountOption;
use App\Models\InventoryItem;
use App\Models\MonthlyMaintanance;
use App\Models\StoreAccount;
use App\Models\StoreLedgerEntry;
use App\Models\StoreVoucher;
use App\Models\TempInvItemImage;
use App\Models\TempItemImage;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MaintananceImport implements ToCollection, WithHeadingRow
{
    public $failedRows = [];
    protected $storeId;

    public function __construct($storeId = null)
    {
        $this->storeId = $storeId;
    }

    public function collection(Collection $rows)
    {
        $groupedItems = [];
        $mmItems = [];

        foreach ($rows as $index => $row) {

            if ($index == 0 && $row['type'] == 'Type') {
                continue;
            }
            try {
                if (empty($row['type']) || empty($row['title']) || empty($row['amount'])) {
                    $this->failedRows[] = ['row' => $index + 1, 'reason' => 'Type, Title and Amount are required'];
                    continue;
                }
                array_push($mmItems, $row['title'] . '- Rs.'. $row['amount']);

                $store_id = Helpers::get_store_id();

                // save new options
                $expenseTypeExists = AccountOption::where('type', 'expense_type')->where('name', $row['type'])->where('store_id', $store_id)->exists();
                if (!$expenseTypeExists) {
                    $account_option = new AccountOption();
                    $account_option->store_id = $store_id;
                    $account_option->name = $row['type'];
                    $account_option->type = 'expense_type';
                    $account_option->save();
                }

                $maintenance = new MonthlyMaintanance();
                $maintenance->store_id = $store_id;
                $maintenance->expense_type = $row['type'];
                $maintenance->title = $row['title'];
                $maintenance->amount = $row['amount'];
                $maintenance->notes = $row['notes'];
                $maintenance->payment_day = $row['payment_day'];
                $maintenance->master = 1;
                $maintenance->save();
                
            } catch (\Exception $e) {
                $this->failedRows[] = [
                    'row'    => $index + 1,
                    'reason' => $e->getMessage(),
                ];
            }
        }
        _auditLogs('Imported Monthly Maintanance : ' . implode(',', $mmItems));
    }
}
