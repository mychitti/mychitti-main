<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class ItemAreaKeyword extends Model
{
    use HasFactory;

     protected $table = 'item_area_keywords';
    protected $primaryKey = 'item_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable =[
        'item_id',
        'keyword',
    ];
}
