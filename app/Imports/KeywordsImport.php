<?php

namespace App\Imports;

use App\Models\ServiceKeyword;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class KeywordsImport implements ToCollection
{
    protected $serviceId;

    public function __construct($serviceId)
    {
        $this->serviceId = $serviceId;
    }


    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            ServiceKeyword::create([
                'service_id' => $this->serviceId,
                'keyword' => $row[0],
            ]);
        }
    }
}
