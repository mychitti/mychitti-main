<?php

namespace App\Imports;

use App\Models\StoreCustomer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * Import a vendor's own customers into store_customers from an uploaded sheet.
 *
 * Columns (with or without a header row): Name | Phone | Email | GST | Address
 * Scoped to one store and deduped by phone within that store, so a re-upload tops up
 * rather than duplicating. Rows without a name or a valid phone are skipped and counted.
 */
class StoreCustomerImport implements ToCollection
{
    public int $imported = 0;
    public int $skipped  = 0;
    public int $duplicate = 0;

    /** New rows from this sheet, for the opt-in queued WhatsApp welcome. */
    public array $welcomeRecipients = [];

    public function __construct(private int $storeId)
    {
    }

    public function collection(Collection $rows)
    {
        // Existing customers of this store, by last-10-digit phone — one lookup, not per row.
        $existing = StoreCustomer::where('store_id', $this->storeId)
            ->pluck('phone')
            ->map(fn($p) => _cleanPhoneNumber((string) $p))
            ->filter()
            ->flip();

        // Phones seen earlier in this same file, so a sheet with internal duplicates is deduped too.
        $seen = [];

        foreach ($rows as $index => $row) {
            // Skip a header row: first cell looks like a label, not data.
            if ($index === 0 && !ctype_digit(preg_replace('/\D/', '', (string) ($row[1] ?? '')))) {
                $label = strtolower(trim((string) ($row[0] ?? '')));
                if (in_array($label, ['name', 'customer name', 'f_name', 'full name'], true) || $label === '') {
                    continue;
                }
            }

            $name  = trim((string) ($row[0] ?? ''));
            $phone = _cleanPhoneNumber((string) ($row[1] ?? ''));
            $email = trim((string) ($row[2] ?? ''));
            $gst   = trim((string) ($row[3] ?? ''));
            $address = trim((string) ($row[4] ?? ''));

            if ($name === '' || strlen($phone) < 10) {
                $this->skipped++;
                continue;
            }

            if (isset($existing[$phone]) || isset($seen[$phone])) {
                $this->duplicate++;
                continue;
            }
            $seen[$phone] = true;

            StoreCustomer::create([
                'store_id'  => $this->storeId,
                'f_name'    => $name,
                'phone'     => $phone,
                'email'     => $email ?: null,
                'gst'       => $gst ?: null,
                'address'   => $address ?: null,
                'user_type' => 'customer',
            ]);

            $this->imported++;
            $this->welcomeRecipients[] = ['name' => $name, 'phone' => $phone];
        }
    }
}
