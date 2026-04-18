<?php

namespace App\Imports;

use App\Models\LocationKeyword;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class LocationKeywordsImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            if (!empty($row[0])) {
                LocationKeyword::create([
                    'keyword' => ucfirst($row[0]),
                ]);
            }
        }
    }
}
