<?php

namespace App\Imports;

use App\CentralLogics\Helpers;
use App\Models\InventoryItem;
use App\Models\ItemEntry;
use App\Models\TempInvItemImage;
use App\Models\TempItemImage;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class InvItemEntryImport implements ToCollection
{
    public $failedRows = [];
    protected $storeId;

    public function __construct($storeId = null)
    {
        $this->storeId = $storeId;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue; // Skip header
            }

            try {
                $excelDate = $row[0];
                $dateTime = ExcelDate::excelToDateTimeObject($excelDate);
                $formattedDate = $dateTime->format('Y-m-d');

                if (empty($row[2]) || empty($row[4])) {
                    $this->failedRows[] = [
                        'row'    => $index + 1,
                        'reason' => 'Missing required fields (Item ID or Stock)',
                    ];
                    continue;
                }

                // --- item ---
                $item = InventoryItem::where("id", $row[2])->where('store_id', Helpers::get_store_id())->first();
                if (!$item) {
                    $this->failedRows[] = [
                        'row'    => $index + 1,
                        'reason' => 'Item with id "' . $row[2] . '" Not Found'
                    ];
                    continue;
                }

                // --- Save ---
                $item = ItemEntry::create([
                    'store_id'          => $this->storeId,
                    'date'              => $formattedDate,
                    'bill_number'       => $row[1],
                    'item_id'           => $row[2],
                    'variation_type'    => $row[3],
                    'quantity'          => $row[4],
                    'product_condition' => $row[5],
                    'landing_price'     => $row[6],
                    'price_gst_status'  => $row[7],
                    'selling_price'     => $row[8],
                ]);
            } catch (\Exception $e) {
                $this->failedRows[] = [
                    'row'    => $index + 1,
                    'reason' => $e->getMessage(),
                ];
            }
        }
    }
}
