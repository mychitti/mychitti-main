<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;

class GatePass  extends BaseModel
{
    use HasFactory;
public function items()
{
    return $this->hasMany(GatePassItem::class, 'gatepass_id');
}
}
