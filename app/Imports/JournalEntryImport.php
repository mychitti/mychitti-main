<?php

namespace App\Imports;

use App\CentralLogics\Helpers;
use App\Models\InventoryItem;
use App\Models\StoreAccount;
use App\Models\StoreLedgerEntry;
use App\Models\StoreVoucher;
use App\Models\TempInvItemImage;
use App\Models\TempItemImage;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class JournalEntryImport implements ToCollection, WithHeadingRow
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

         $voucherIds = [];

        foreach ($rows as $index => $row) {

            if ($index == 0 && $row['date'] == 'Date') {
                continue;
            }
            try {
                $entry_date = Helpers::excelToCarbon($row['date']);
                $entry_date = $entry_date ? $entry_date->format('Y-m-d') : $row['date'];

                if (empty($row['credit_account_id'])) {
                    $this->failedRows[] = ['row' => $index + 1, 'reason' => 'Missing Credit Account Id'];
                    continue;
                } else {
                    $accountExists = StoreAccount::where('store_id', $this->storeId)
                        ->where('id', $row['credit_account_id'])
                        ->exists();
                    if (!$accountExists) {
                        $this->failedRows[] = ['row' => $index + 1, 'reason' => 'Invalid Credit Account Id'];
                        continue;
                    }
                }
                if (empty($row['debit_account_id'])) {
                    $this->failedRows[] = ['row' => $index + 1, 'reason' => 'Missing Debit Account Id'];
                    continue;
                } else {
                    $accountExists = StoreAccount::where('store_id', $this->storeId)
                        ->where('id', $row['debit_account_id'])
                        ->exists();
                    if (!$accountExists) {
                        $this->failedRows[] = ['row' => $index + 1, 'reason' => 'Invalid Debit Account Id'];
                        continue;
                    }
                }
                if (empty($row['amount'])) {
                    $this->failedRows[] = ['row' => $index + 1, 'reason' => 'Missing Amount'];
                    continue;
                }

                $voucherNo = Helpers::_generateVoucherNumber($this->storeId);
                $voucher = StoreVoucher::create([
                    'store_id'       => $this->storeId,
                    'voucher_number' => $voucherNo,
                    'voucher_type'   => $row['voucher_type'] ?? 'Payment',
                    'voucher_date'   => $entry_date ?? now(),
                    'total_amount'   => $row['amount'],
                    'narration'      => $row['description'] ?? null,
                    'status'         => 'approved',
                    'completed_at'   => $entry_date ?? now(),
                ]);
                array_push($voucherIds, $voucherNo);

                // credit entry
                $entry = StoreLedgerEntry::create([
                    'store_id'        => $this->storeId,
                    'status'          =>  'approved',
                    'voucher_type'    => $row['voucher_type'] ?? 'Payment',
                    'voucher_id'      => $voucher->id,
                    'entry_date'      => $entry_date ?? now(),
                    'account_id'      => $row['credit_account_id'],
                    'debit'           => 0,
                    'credit'          => $row['amount'],
                    'gst_amount'      => null,
                    'narration'       => $row['description'] ?? null,
                    'payment_mode'    => null,
                    'note'            => null,
                    'completed_at'    => $entry_date ?? now(),
                ]);
                // debit entry
                $entry = StoreLedgerEntry::create([
                    'store_id'        => $this->storeId,
                    'status'          => 'approved',
                    'voucher_type'    => $row['voucher_type'] ?? 'Payment',
                    'voucher_id'      => $voucher->id,
                    'entry_date'      => $entry_date ?? now(),
                    'account_id'      => $row['debit_account_id'],
                    'debit'           => $row['amount'] ,
                    'credit'          => 0,
                    'gst_amount'      => null,
                    'narration'       => $row['description'] ?? null,
                    'payment_mode'    => null,
                    'note'            => null,
                    'completed_at'    => $entry_date ?? now(),
                ]);

            } catch (\Exception $e) {
                $this->failedRows[] = [
                    'row'    => $index + 1,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        _auditLogs('Imported Journal entries. Vouchers : ' . implode(',',$voucherIds));
    
    }
}
