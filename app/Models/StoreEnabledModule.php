<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class StoreEnabledModule extends Model
{
    protected $fillable = ['store_id'];
    use HasFactory;

    public function store()
    { 
        return $this->belongsTo(Store::class);
    }
    public function submodule()
    { 
        return $this->belongsTo(SubModule::class);
    }
}
