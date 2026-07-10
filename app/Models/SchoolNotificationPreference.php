<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SchoolNotificationPreference extends Model
{
    public $timestamps = false;

    protected $table = 'school_notification_preferences';

    protected $fillable = [
        'store_id',
        'action_key',
        'whatsapp',
        'sms',
        'push_notification',
        'updated_at',
    ];

    protected $casts = [
        'store_id'          => 'integer',
        'whatsapp'          => 'boolean',
        'sms'               => 'boolean',
        'push_notification' => 'boolean',
    ];

    const ACTIONS = [
        'student_absence'  => ['label' => 'Student Absence',  'desc' => 'Notify parents when a student is marked absent',           'icon' => 'tio-user-outlined'],
        'notice'           => ['label' => 'Notices',           'desc' => 'Notify when a new notice is published',                    'icon' => 'tio-comment'],
        'homework'         => ['label' => 'Homework',          'desc' => 'Notify when new homework is assigned',                     'icon' => 'tio-document-text'],
        'fee_reminder'     => ['label' => 'Fee Reminder',      'desc' => 'Remind parents about pending fee dues',                    'icon' => 'tio-money'],
        'fee_receipt'      => ['label' => 'Fee Receipt',       'desc' => 'Send fee receipt after payment',                           'icon' => 'tio-receipt-outlined'],
        'exam_result'      => ['label' => 'Exam Results',      'desc' => 'Notify when exam results are published',                   'icon' => 'tio-chart-bar-4'],
        'event'            => ['label' => 'Events',            'desc' => 'Notify about school events and activities',                'icon' => 'tio-calendar'],
        'transport_update' => ['label' => 'Transport Update',  'desc' => 'Notify about transport route or schedule changes',         'icon' => 'tio-car'],
        'leave_status'     => ['label' => 'Leave Status',      'desc' => 'Notify when student leave is approved or rejected',        'icon' => 'tio-checkmark-circle-outlined'],
    ];

    public static function ensureTable(): void
    {
        if (!Schema::hasTable('school_notification_preferences')) {
            DB::statement("CREATE TABLE IF NOT EXISTS school_notification_preferences (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL,
                action_key VARCHAR(50) NOT NULL,
                whatsapp TINYINT(1) NOT NULL DEFAULT 0,
                sms TINYINT(1) NOT NULL DEFAULT 0,
                push_notification TINYINT(1) NOT NULL DEFAULT 0,
                updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_store_action (store_id, action_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public static function getPreferences(int $storeId): array
    {
        self::ensureTable();

        $saved = self::where('store_id', $storeId)->get()->keyBy('action_key');

        $prefs = [];
        foreach (self::ACTIONS as $key => $meta) {
            if ($saved->has($key)) {
                $row = $saved->get($key);
                $prefs[$key] = [
                    'whatsapp'          => (bool) $row->whatsapp,
                    'sms'               => (bool) $row->sms,
                    'push_notification' => (bool) $row->push_notification,
                ];
            } else {
                $prefs[$key] = [
                    'whatsapp'          => false,
                    'sms'               => false,
                    'push_notification' => false,
                ];
            }
        }
        return $prefs;
    }

    public static function isChannelEnabled(int $storeId, string $actionKey, string $channel): bool
    {
        self::ensureTable();
        return (bool) self::where('store_id', $storeId)
            ->where('action_key', $actionKey)
            ->value($channel);
    }
}
