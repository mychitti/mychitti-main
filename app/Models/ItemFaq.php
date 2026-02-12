<?php 

// app/Models/ItemFaq.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemFaq extends Model
{
    protected $table = 'item_faqs';

    protected $fillable = [
        'item_id',
        'question',
        'answer',
        'sort_order',
        'status'
    ];
}
