<?php

namespace App\Imports;

use App\CentralLogics\Helpers;
use App\Models\BankTransaction;
use App\Models\StoreBankTransaction;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BankTransactionImport implements ToCollection, WithHeadingRow
{
    protected $bankAccountId;
    protected $fileId;
    public $failedRows = [];

    public function __construct($bankAccountId, $fileId)
    {
        $this->bankAccountId = $bankAccountId;
        $this->fileId = $fileId;
    }

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        $storeId = Helpers::get_store_id();

        foreach ($rows as $index => $row) {
            try {
                if ($index == 0 && $row['date'] == 'Date') {
                    continue;
                }

                if (empty($row['transaction_id'])) {
                    continue;
                }

                if (empty($row['withdrawal_amt']) && empty($row['deposit_amt'])) {
                    continue;
                }

                if (Helpers::get_store_id() != 26) {
                    $exists = StoreBankTransaction::where('store_id', $storeId)
                        ->where('txn_id', $row['transaction_id'])
                        ->exists();
                    if ($exists) {
                        $this->failedRows[] = "Row " . ($index + 1) . " duplicate txn_id: " . $row['transaction_id'];
                        continue; // skip duplicates
                    }
                }

                $deposit  = isset($row['deposit_amt']) ? trim($row['deposit_amt']) : '';
                $withdraw = isset($row['withdrawal_amt']) ? trim($row['withdrawal_amt']) : '';

                if ($deposit !== '' && $deposit !== null) {
                    $type   = 'credit';
                    $amount = (float) $deposit;
                } elseif ($withdraw !== '' && $withdraw !== null) {
                    $type   = 'debit';
                    $amount = (float) $withdraw;
                } else {
                    $type   = null;
                    $amount = 0;
                }
                $carbonDate = Helpers::excelToCarbon($row['date']);
                if ($carbonDate) {
                    $txn_date =  $carbonDate->format('Y-m-d') . "\n";
                } else {
                    $txn_date = $row['date'];
                }
                $value_date = Helpers::excelToCarbon($row['value_date']);
                if ($value_date) {
                    $value_date =  $value_date->format('Y-m-d') . "\n";
                } else {
                    $value_date = $row['value_date'];
                }


                StoreBankTransaction::create([
                    'store_id'       => $storeId,
                    'bank_id'        => $this->bankAccountId == 0 ? $row['bank_account_id'] :  $this->bankAccountId,
                    'file_id'        => $this->fileId,
                    'txn_date'       => $txn_date,
                    'particulars'    => $row['narration'] ?? null,
                    'txn_id'         => $row['transaction_id'] ?? null,
                    'value_date'     => $value_date,
                    'amount'         => $amount,
                    'type'           => $type,
                    'bill_number'    => $row['bill_number'] ?? null,
                    'reference_number'=> 'REF' . str_replace('.', '', microtime(true)),
                    'closing_balance' => (float) ($row['closing_balance'] ?? 0),
                ]);
                // SAVE DAY BOOK ENTRY 
                $particulars  = $row['narration'] ?? null;
                if ($row['bill_number']) {
                    $invoice_id = $row['bill_number'] ?? null;
                    $invoice =  _manualInvoiceByInvoiceId($invoice_id);
                    $invoice_id = $invoice ? $invoice->id : null;
                } else {
                    $invoice_id = null;
                }
                // _saveDayBookEntry($amount, $type, $storeId, $particulars, $invoice_id, null, $value_date);
            } catch (\Exception $e) {
                $this->failedRows[] = "Row " . ($index + 1) . " error: " . $e->getMessage();
            }
        }
    }
}
