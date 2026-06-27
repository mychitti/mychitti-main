<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class StoreDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'document_type',
        'file_path',
        'status',
        'doc_type',
        'verified',
        'version_type',
        'verified_by',
        'verified_at',
        'checked',
        'back_side',
        'doc_name',
        'doc_number',
        'show_on_bill',
    ];

}
