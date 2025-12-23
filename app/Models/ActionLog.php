<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActionLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_type',
        'action',
        'model_type',
        'model_id',
        'description',
    ];
    public function getdataAttribute()
    {
        $id = $this->model_id;

        switch (strtolower($this->model_type)) {
            case 'category':
                return Category::withTrashed()->find($id);

            case 'item':
                return Item::withoutGlobalScopes()->withTrashed()->find($id);

            case 'user':
                return User::withTrashed()->find($id);

                // Add more model types here as needed

            default:
                return null;
        }
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'user_id');
    }
}
