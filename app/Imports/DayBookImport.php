<?php

namespace App\Imports;

use App\CentralLogics\Helpers;
use App\Models\DayBook;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class DayBookImport implements ToCollection
{
    public $failedRows = false;
    protected $storeId;

    public function __construct($storeId = null) // Optional
    {
        $this->storeId = $storeId;
    }

    public function collection(Collection $rows)
    {
        
        foreach ($rows as $index => $row) {

            if ($index === 0 || $row[0] == '' || $row[1] == '') {
                continue; // Skip the first row (header) or empty particulars or date
            }

            $date = $row[0];
            $particulars = $row[1];
            $type = $row[2] != '' ? 'credit' : 'debit'; 
            $amount = $row[2] != '' ? $row[2] : $row[3]; 

            $dayBookData = [
                'store_id' => $this->storeId ?? Helpers::get_store_id(),
                'created_at' => $date,
                'particular' => $particulars,
                'type' => $type,
                'amount' => $amount,
            ];

            DayBook::create($dayBookData);
        }
    }
}
