<?php

namespace App\Imports;

use App\Models\InventoryItem;
use App\Models\TempInvItemImage;
use App\Models\TempItemImage;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class InvItemImport implements ToCollection
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
                $tempGroup = trim($row[0]);

                // First row of group (main item)
                if (!isset($groupedItems[$tempGroup])) {

                    // Validate required fields for main product
                    if (empty($row[1])) {
                        $this->failedRows[] = ['row' => $index + 1, 'reason' => 'Missing Name'];
                        continue;
                    }
                    if (empty($row[2])) {
                        $this->failedRows[] = ['row' => $index + 1, 'reason' => 'Missing SKU ID'];
                        continue;
                    }
                    if (empty($row[5])) {
                        $this->failedRows[] = ['row' => $index + 1, 'reason' => 'Missing MRP'];
                        continue;
                    }

                    // --- GST Type ---
                    $gst_type = match (strtolower(trim($row[4]))) {
                        'cgst + sgst', 'cgst+sgst' => 'cgst_sgst',
                        'igst' => 'igst',
                        default => 'no_gst',
                    };

                    // --- Storage Unit ---
                    $storage_unit_id = null;
                    if (!empty($row[8])) {
                        if (is_numeric($row[8])) {
                            $unit = \App\Models\Unit::find($row[8]);
                        } else {
                            $unit = \App\Models\Unit::whereRaw('LOWER(unit) = ?', [strtolower(trim($row[8]))])->first();
                        }
                        $storage_unit_id = $unit ? $unit->id : null;
                    }

                    // --- Category ---
                    $category_id = null;
                    if (!empty($row[18])) {
                        if (is_numeric($row[18])) {
                            $category = \App\Models\Category::find($row[18]);
                        } else {
                            $category = \App\Models\Category::whereRaw('LOWER(name) = ?', [strtolower(trim($row[18]))])->first();
                        }
                        $category_id = $category ? $category->id : null;
                    }
                    // attributes and choice options 
                    // Attributes column (e.g. "30,91")
                    $attributesRaw = $row[9] ?? null; // adjust index
                    $attributeIds = [];
                    if (!empty($attributesRaw)) {
                        $attributeIds = array_map('trim', explode(',', $attributesRaw));
                    } // Choices JSON column
                    $rawChoices = $row[10] ?? null; // adjust index
                    $choice_options = [];

                    if (!empty($rawChoices)) {
                        $decoded = json_decode($rawChoices, true);

                        if (json_last_error() === JSON_ERROR_NONE) {
                            foreach ($attributeIds as $attrId) {
                                $choiceKey = "choice_" . $attrId;

                                if (isset($decoded[$choiceKey])) {
                                    $item = [];
                                    $item['name'] = $choiceKey;     
                                    $item['title'] = "Attribute " . $attrId; 
                                    $item['options'] = $decoded[$choiceKey];

                                    $choice_options[] = $item;
                                }
                            }
                        } else {
                            $this->failedRows[] = [
                                'row'    => $index + 1,
                                'reason' => 'Invalid JSON in choices column',
                            ];
                        }
                    }

                    // --- Create Item ---
                    $item = InventoryItem::create([
                        'store_id'        => $this->storeId,
                        'item_name'       => $row[1],
                        'sku_id'          => $row[2],
                        'gst_rate'        => $row[3] ?? 0,
                        'gst_type'        => $gst_type,
                        'mrp'             => $row[5] ?? 0,
                        'selling_price'   => $row[6] ?? 0,
                        'stock'           => $row[7] ?? 0,
                        'storage_unit_id' => $storage_unit_id,
                        'attributes'      => json_encode($attributeIds),
                        'choice_options'  => json_encode($choice_options),
                        'model_number'    => $row[16] ?? null,
                        'brand'           => $row[17] ?? null,
                        'category_id'     => $category_id,
                        'hsn'             => $row[19] ?? null,
                        'module_id'       => 6,
                    ]);


                    $groupedItems[$tempGroup] = $item;
                } else {
                    // Use already created item for this group
                    $item = $groupedItems[$tempGroup];
                }

                // --- Variation Validation ---
                $variationName          = $row[11] ?? null;
                $variationMrp           = $row[12] ?? null;
                $variationSellingPrice  = $row[13] ?? null;
                $variationPurchasePrice = $row[14] ?? null;
                $variationStock         = $row[15] ?? null;

                if (!empty($variationName)) {
                    // Validate variation price fields if needed
                    if (empty($variationMrp)) {
                        $this->failedRows[] = ['row' => $index + 1, 'reason' => 'Missing Variation MRP'];
                        continue;
                    }
                    // if (empty($variationSellingPrice)) {
                    //     $this->failedRows[] = ['row' => $index + 1, 'reason' => 'Missing Variation Selling Price'];
                    //     continue;
                    // }

                    // Save variation
                    $variations = $item->variations ? json_decode($item->variations, true) : [];
                    $variations[] = [
                        'type'          => $variationName,
                        'mrpprice'      => $variationMrp,
                        'price'   => $variationSellingPrice,
                        'purchaseprice' => $variationPurchasePrice,
                        'stock'         => $variationStock,
                    ];
                    $item->variations = json_encode($variations);
                    $item->save();
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
