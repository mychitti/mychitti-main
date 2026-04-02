<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreGallery extends Model
{
    use HasFactory;
      protected function serializeDate(\DateTimeInterface $date)
    {
        return $date
            ->setTimezone(new \DateTimeZone('Asia/Kolkata'))
            ->format('Y-m-d H:i:s');
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
