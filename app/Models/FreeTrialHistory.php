<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreeTrialHistory extends Model
{
    use HasFactory;
    protected $fillable = ['store_id'];

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
    public function submodule()
    {
        return $this->belongsTo(SubModule::class);
    }
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'assigned_by');
    }
}
