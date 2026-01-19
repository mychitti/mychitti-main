<?php

namespace App\Imports;

use App\CentralLogics\Helpers;
use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\StoreAccount;
use App\Models\StoreLedgerEntry;
use App\Models\StoreVoucher;
use App\Models\TempInvItemImage;
use App\Models\TempItemImage;
use App\Models\Vendor;
use App\Models\Zone;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;


class StoreImport implements ToCollection, WithHeadingRow
{
    public $failedRows = [];
    protected $storeId;

    public function __construct($storeId = null)
    {
        $this->storeId = $storeId;
    }

    public function collection(Collection $rows)
    {

        $storeIds = [];
        foreach ($rows as $index => $row) {
            // prx($row);

            try {
                $requiredFields = [
                    'store_name' => 'Store Name',
                    'phone' => 'Phone',
                    'owner_first_name' => 'Owner First Name',
                    'owner_last_name' => 'Owner Last Name',
                    'google_verification' => 'Google verification',
                    'address' => 'Address',
                    'business_type' => 'Business Type',
                    'latitude' => 'Latitude',
                    'longitude' => 'Longitude',
                ];

                foreach ($requiredFields as $field => $label) {
                    if (empty($row[$field])) {
                        $this->failedRows[] = [
                            'row' => $index + 1,
                            'reason' => "$label is required "
                        ];
                        continue 2; // skip to next row
                    }
                }
                if (!Helpers::_validatePhoneNumber($row['phone'])) {
                    $this->failedRows[] = [
                        'row' => $index + 1,
                        'reason' => "Phone not valid. Please enter phone number with country code"
                    ];
                    continue; // skip to next row
                }
                //  CHECK IF PHONE ALREADY REGISTERED 
                $phone = trim($row['phone']);
                // ensure phone starts with +
                if ($phone[0] !== '+') {
                    $phone = '+' . $phone;
                }
                $exists = DB::table('vendors')->where('phone', $phone)->exists()
                    || DB::table('stores')->where('phone', $phone)->exists();
                if ($exists) {
                    $this->failedRows[] = [
                        'row' => $index + 1,
                        'reason' => "Phone already registered."
                    ];
                    continue; // skip to next row
                }

                //  CHECK COORDINATES
                if ($row['zone_id']) {
                    $zone = Zone::query()
                        ->whereContains('coordinates', new Point($row['latitude'], $row['longitude'], POINT_SRID))
                        ->where('id', $row['zone_id'])
                        ->first();
                    if (!$zone) {
                        $this->failedRows[] = [
                            'row' => $index + 1,
                            'reason' => "Coordinates out of zone"
                        ];
                        continue; // skip to next row
                    }
                }

                // SAVE VENDOR ==============
                $vendor = new Vendor();
                $vendor->f_name = $row['owner_first_name'];
                $vendor->l_name = $row['owner_last_name'];
                $vendor->email = $row['email'] ?? '';
                $vendor->phone = '+' . $row['phone'];
                $vendor->secondary_phone = $row['secondary_phone'];
                $vendor->password = bcrypt(generatePassword());
                $vendor->created_by = auth('admin')->user()->id;
                if (!Helpers::module_permission_check('store')) {
                    $vendor->status = null;
                }
                $vendor->save();

                // SAVE STORE ==================
                $store = new Store;
                if (!Helpers::module_permission_check('store')) { // not have auto approve option
                    $store->status = 0;
                }
                $store->name = $row['store_name'];
                $store->phone = '+' . $row['phone'];
                $store->email = $row['email'] ?? '';
                $store->pin_code = $row['pin_code']  ?? getPincodeFromCoordinates($row['latitude'],  $row['longitude']);
                $store->secondary_phone = $row['secondary_phone'];
                $store->address = $row['address'];
                $store->gst = $row['gst_number'] ? json_encode(['status' => 1, 'code' => $row['gst_number']]) : json_encode(['status' => 0, 'code' => '']); // gst number and on/off gst
                $store->gst_number = $row['gst_number'];
                $store->id_number = $row['id_number'];
                $store->google_verification = $row['google_verification'];
                $store->business_type = $row['business_type'];
                $store->latitude = $row['latitude'];
                $store->longitude = $row['longitude'];
                $store->vendor_id = $vendor->id;
                $store->zone_id = $row['zone_id'];
                $store->module_id = Config::get('module.current_module_id');
                try {
                    $store->save();
                    array_push($storeIds, $store->id);

                    Helpers::_addWelcomeCouponsIfExist($store);
                    Helpers::_addWelcomeCouponsIfExist($store);
                    $store->module->increment('stores_count');
                } catch (\Exception $ex) {
                    $this->failedRows[] = [
                        'row' => $index + 1,
                        'reason' => $ex->getMessage()
                    ];
                    continue; // skip to next row
                }
            } catch (\Exception $e) {
                $this->failedRows[] = [
                    'row'    => $index + 1,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        _auditLogs('Imported Stores. IDs : ' . implode(',', $storeIds));
    }
}
