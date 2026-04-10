<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class AdminCustomerImport implements ToCollection
{
    public int $imported = 0;
    public int $skipped  = 0;

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue; // skip header row
            }

            $name  = trim($row[0] ?? '');
            $phone = _cleanPhoneNumber($row[1] ?? '');
            $email = trim($row[2] ?? '');
            $gst   = trim($row[3] ?? '');
            $address = trim($row[4] ?? '');

            if (!$name || !$phone) {
                $this->skipped++;
                continue;
            }

            // Skip if phone or email already exists
            $query = User::where('phone', $phone);
            if ($email) {
                $query->orWhere('email', $email);
            }
            if ($query->exists()) {
                $this->skipped++;
                continue;
            }

            User::create([
                'f_name'   => $name,
                'phone'    => $phone,
                'email'    => $email ?: null,
                'gst'      => $gst ?: null,
                'address'  => $address ?: null,
                'password' => bcrypt(uniqid()),
            ]);

            $this->imported++;
        }
    }
}
