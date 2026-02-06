<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use DateTimeInterface;

class BaseModel extends Model
{
    protected function serializeDate(DateTimeInterface $date)
    {
        return Carbon::instance($date)
            ->timezone(config('app.timezone'))
            ->format('Y-m-d H:i:s');
    }
}
