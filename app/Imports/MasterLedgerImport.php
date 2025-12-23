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

class MasterLedgerImport implements ToCollection, WithHeadingRow
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

            if ($index == 0 && $row['tem_voucher_id'] == 'Voucher') {
                continue;
            }
            try {
                $tempGroup = trim($row['tem_voucher_id']);

                $value_date = Helpers::excelToCarbon($row['completed_at']);
                $value_date = $value_date ? $value_date->format('Y-m-d H:i:s') : $row['completed_at'];

                $entry_date = Helpers::excelToCarbon($row['entry_date']);
                $entry_date = $entry_date ? $entry_date->format('Y-m-d') : $row['entry_date'];

                if (empty($tempGroup)) {
                    $this->failedRows[] = ['row' => $index + 1, 'reason' => 'Missing Temp Voucher Number'];
                    continue;
                }
                if (empty($row['ledger_account_id'])) {
                    $this->failedRows[] = ['row' => $index + 1, 'reason' => 'Missing Ledger Account Id'];
                    continue;
                } else {
                    $accountExists = StoreAccount::where('store_id', $this->storeId)
                        ->where('id', $row['ledger_account_id'])
                        ->exists();
                    if (!$accountExists) {
                        $this->failedRows[] = ['row' => $index + 1, 'reason' => 'Invalid Ledger Account Id'];
                        continue;
                    }
                }
                if (empty($row['debit']) && empty($row['credit'])) {
                    $this->failedRows[] = ['row' => $index + 1, 'reason' => 'Missing Amount'];
                    continue;
                }

                // Initialize voucher group tracking
                if (!isset($groupedItems[$tempGroup])) {
                    $groupedItems[$tempGroup] = [
                        'voucher' => null,
                        'total_amount' => 0,
                        'debit_count' => 0,
                        'credit_count' => 0,
                        'rows' => []
                    ];
                }

                // Check debit/credit limits BEFORE creating entries
                $hasDebit = !empty($row['debit']) && $row['debit'] > 0;
                $hasCredit = !empty($row['credit']) && $row['credit'] > 0;

                if ($hasDebit && $groupedItems[$tempGroup]['debit_count'] >= 1) {
                    $this->failedRows[] = ['row' => $index + 1, 'reason' => 'Voucher already has a debit entry'];
                    continue;
                }

                if ($hasCredit && $groupedItems[$tempGroup]['credit_count'] >= 1) {
                    $this->failedRows[] = ['row' => $index + 1, 'reason' => 'Voucher already has a credit entry'];
                    continue;
                }

                // Create voucher on first valid entry
                if ($groupedItems[$tempGroup]['voucher'] === null) {
                    $voucherNo = Helpers::_generateVoucherNumber($this->storeId);
                    $voucher = StoreVoucher::create([
                        'store_id'        => $this->storeId,
                        'voucher_number'  => $voucherNo,
                        'voucher_type'    => 'Payment',
                        'voucher_date'    => $entry_date ?? now(),
                        'total_amount'    => 0, // will update later
                        'narration'       => $row['narration'] ?? null,
                        'status'          => $row['status'] ?? 'pending',
                        'completed_at'    => $value_date,
                    ]);

                    array_push($voucherIds, $voucherNo);

                    $groupedItems[$tempGroup]['voucher'] = $voucher;
                }

                $voucher = $groupedItems[$tempGroup]['voucher'];

                $entry = StoreLedgerEntry::create([
                    'store_id'        => $this->storeId,
                    'status'          => $row['status'] ?? 'pending',
                    'voucher_type'    => $row['voucher_type'] ?? 'Payment',
                    'voucher_id'      => $voucher->id,
                    'entry_date'      => $entry_date,
                    'account_id'      => $row['ledger_account_id'],
                    'debit'           => $row['debit'] ?? 0,
                    'credit'          => $row['credit'] ?? 0,
                    'gst_amount'      => $row['gst_amount'] ?? null,
                    'narration'       => $row['narration'] ?? null,
                    'payment_mode'    => $row['payment_mode'] ?? null,
                    'note'            => $row['note'] ?? null,
                    'completed_at'    => $value_date,
                ]);

                // Update counts
                if ($hasDebit) {
                    $groupedItems[$tempGroup]['debit_count']++;
                }
                if ($hasCredit) {
                    $groupedItems[$tempGroup]['credit_count']++;
                }

                $groupedItems[$tempGroup]['total_amount'] += ($row['debit'] ?? 0) + ($row['credit'] ?? 0);
            } catch (\Exception $e) {
                $this->failedRows[] = [
                    'row'    => $index + 1,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        _auditLogs('Imported Master Ledger entries. Vouchers : ' . implode(',',$voucherIds));

        foreach ($groupedItems as $group => $data) {
            if (isset($data['voucher'])) {
                $data['voucher']->update([
                    'total_amount' => $data['total_amount']
                ]);
            }
        }
    }
}
