<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolNotice extends Model
{
    protected $table = 'school_notices';

    protected $fillable = [
        'store_id', 'branch_id', 'title', 'body', 'notice_date',
        'audience', 'school_class_id', 'is_published', 'is_pinned',
        'expires_on', 'created_by',
    ];

    protected $casts = [
        'notice_date'  => 'date',
        'expires_on'   => 'date',
        'is_published' => 'boolean',
        'is_pinned'    => 'boolean',
    ];

    public const AUDIENCES = [
        'all'      => 'Everyone',
        'students' => 'Students',
        'parents'  => 'Parents',
        'staff'    => 'Staff / Teachers',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function audienceLabel(): string
    {
        $label = self::AUDIENCES[$this->audience] ?? ucfirst((string) $this->audience);
        if ($this->school_class_id && $this->schoolClass) {
            $label .= ' · ' . $this->schoolClass->name;
        }
        return $label;
    }
}
