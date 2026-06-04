<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EditorDocument extends Model
{
    protected $fillable = [
        'doc_key',
        'title',
        'content',
        'blocks',
    ];
}
