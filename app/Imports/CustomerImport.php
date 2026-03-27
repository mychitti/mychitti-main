<?php

namespace App\Imports;

use App\CentralLogics\Helpers;
use App\Models\StoreCustomer;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class CustomerImport implements ToCollection
{
    public $failedRows = false;
    protected $vendorId;

    public function __construct($vendorId = null) // Optional
    {
        $this->vendorId = $vendorId;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {

            if ($index === 0 || $row[2] == '') {
                continue; // Skip the first row (header)
            }
            $phone = _cleanPhoneNumber($row[2]);
            $existingUser = StoreCustomer::where('phone', $phone)->where('store_id', $this->vendorId ?? Helpers::get_store_id())->where('user_type', $row[0])->first();
            if ($existingUser) {
                // prx($existingUser);
                $this->failedRows = true;
                continue; // Skip if user already exists
            }
            $email = $row[3];
            $id_number = $row[4];
            $gst = $row[5];
            $address = $row[6] ?? '';
            $pin_code = $row[7] ?? '';

            // Check for uniqueness

            $userData = [
                'store_id' => $this->vendorId ?? Helpers::get_store_id(),
                'user_type'  => $row[0],
                'f_name'  => $row[1],
                'phone'   => $phone,
                'email'   => $email,
                'id_number'  => $id_number,
                'gst'  => $gst,
                'address'  => $address,
                'pin_code'  => $pin_code,
            ];

            $user = StoreCustomer::create($userData);
            $user->save();
        }
    }
}
