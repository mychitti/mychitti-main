<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TmpWhatsAppSetup extends Model
{
    protected $table = 'wa_tmp_setup';

    protected $fillable = ['store_id', 'plan', 'account_manager'];
}
