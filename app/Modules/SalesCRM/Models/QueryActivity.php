<?php

namespace App\Modules\SalesCRM\Models;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;

class QueryActivity extends Model
{
    protected $table = 'sales_query_activities';

    protected $fillable = ['query_id', 'admin_id', 'type', 'description'];

    const TYPES = ['call', 'email', 'meeting', 'note', 'status_change', 'follow_up', 'ticket'];

    const TYPE_ICONS = [
        'call'          => 'tio-call-outlined',
        'email'         => 'tio-email-outlined',
        'meeting'       => 'tio-user-team',
        'note'          => 'tio-document-text-outlined',
        'status_change' => 'tio-exchange-horizontal',
        'follow_up'     => 'tio-calendar-note',
        'ticket'        => 'tio-help-outlined',
    ];

    const TYPE_COLORS = [
        'call'          => '#52c48a',
        'email'         => '#5bc4d8',
        'meeting'       => '#6b9bd4',
        'note'          => '#a0b3bf',
        'status_change' => '#f4a65e',
        'follow_up'     => '#a585d4',
        'ticket'        => '#e87878',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public static function log(int $queryId, string $type, string $description): void
    {
        static::create([
            'query_id'    => $queryId,
            'admin_id'    => auth('admin')->id(),
            'type'        => $type,
            'description' => $description,
        ]);
    }
}
