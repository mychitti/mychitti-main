<?php

namespace App\Imports;

use App\Models\Branch;
use App\Models\BranchInventoryItem;
use App\Models\InventoryItem;
use App\Models\InvItemVariationDetail;
use App\Models\TempInvItemImage;
use App\Models\TempItemImage;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class POSItemImport implements ToCollection
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

        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue; // Skip header row
            }

            try {

                // new branch if not exists 
                // if (is_numeric($branch)) {
                    $branchId = $row[4];
                // } else {
                //     $existingBranch = Branch::where('name', $branch)
                //         ->where('store_id', $this->storeId)
                //         ->first();

                //     if ($existingBranch) {
                //         $branchId = $existingBranch->id;
                //     } else {
                //         $newBranch = Branch::create([
                //             'name'     => $branch,
                //             'store_id' => $this->storeId
                //         ]);
                //         $branchId = $newBranch->id;
                //     }
                // }
                // prx( $submittedItems);


                    $price = $row[1] ?? 0;
                    $qty = $row[2] ?? 0;
                    $gst_percent = $row[3] ?? 0;
                    // prx($price, $qty);
                    $invItem = BranchInventoryItem::where([
                        'branch_id'          => $branchId,
                        'inventory_item_id'  => $row[0]
                    ])->first();

                    if ($invItem) {
                        BranchInventoryItem::where([
                            'branch_id'          => $branchId,
                            'inventory_item_id'  => $row[0],
                            'store_id'  => $this->storeId
                        ])->update([
                            'gst_percent' => $gst_percent,
                            'qty'       => $invItem->qty + $qty,
                            'qty_left'  => $invItem->qty_left + $qty,
                            'price'     => $price,
                        ]);
                    } else {
                        BranchInventoryItem::create([
                            'gst_percent'                => $gst_percent,
                            'branch_id'          => $branchId,
                            'inventory_item_id'  => $row[0],
                            'qty'                => $qty,
                            'qty_left'           => $qty,
                            'price'              => $price,
                            'store_id'           => $this->storeId
                        ]);
                    }
            } catch (\Exception $e) {
                $this->failedRows[] = [
                    'row'    => $index + 1,
                    'reason' => $e->getMessage(),
                ];
            }
        }
    }
}
