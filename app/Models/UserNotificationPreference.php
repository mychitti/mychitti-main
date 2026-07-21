<?php

namespace App\Models;

use App\Services\WhatsAppService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-customer marketing channel preferences, set from the user dashboard.
 *
 * These govern PROMOTIONAL messages only. Account-security messages (login and
 * password-reset OTP) always send — a customer who opted out of marketing must not be
 * locked out of their own account.
 */
class UserNotificationPreference extends Model
{
    protected $table = 'user_notification_prefs';

    protected $fillable = ['user_id', 'sms', 'whatsapp', 'push'];

    protected $casts = [
        'user_id'  => 'integer',
        'sms'      => 'boolean',
        'whatsapp' => 'boolean',
        'push'     => 'boolean',
    ];

    const CHANNELS = [
        'whatsapp' => [
            'label' => 'WhatsApp',
            'desc'  => 'Offers and updates from MyChitti and the businesses in your area.',
        ],
        'push' => [
            'label' => 'App notifications',
            'desc'  => 'Push notifications on your phone, including order and booking updates.',
        ],
        'sms' => [
            'label' => 'Promotional SMS',
            'desc'  => 'Marketing text messages. Login and security codes are always sent.',
        ],
    ];

    public static function ensureTable(): void
    {
        if (!Schema::hasTable('user_notification_prefs')) {
            DB::statement("CREATE TABLE IF NOT EXISTS user_notification_prefs (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT NOT NULL,
                sms TINYINT(1) NOT NULL DEFAULT 1,
                whatsapp TINYINT(1) NOT NULL DEFAULT 1,
                push TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT NULL,
                updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    /** Everything is on until the customer says otherwise. */
    public static function forUser(int $userId): array
    {
        static::ensureTable();
        $row = static::where('user_id', $userId)->first();

        return [
            'sms'      => $row ? (bool) $row->sms : true,
            'whatsapp' => $row ? (bool) $row->whatsapp : true,
            'push'     => $row ? (bool) $row->push : true,
        ];
    }

    /** True when this customer still accepts $channel. Unknown users are left alone. */
    public static function allows(?int $userId, string $channel): bool
    {
        if (!$userId || !array_key_exists($channel, self::CHANNELS)) {
            return true;
        }
        try {
            return static::forUser($userId)[$channel];
        } catch (\Throwable $e) {
            // Never let a preference lookup break an actual send.
            return true;
        }
    }

    /**
     * Save the customer's choices. The WhatsApp toggle also drives the platform-wide
     * wa_opt_outs list, so vendor bulk sends honour it without a second lookup.
     *
     * Named saveFor() rather than save() — Eloquent\Model::save() is an instance method and
     * PHP will not let a subclass redeclare it as static.
     */
    public static function saveFor(int $userId, array $channels): void
    {
        static::ensureTable();

        static::updateOrCreate(
            ['user_id' => $userId],
            [
                'sms'      => !empty($channels['sms']),
                'whatsapp' => !empty($channels['whatsapp']),
                'push'     => !empty($channels['push']),
            ]
        );

        $phone = DB::table('users')->where('id', $userId)->value('phone');
        if ($phone) {
            if (empty($channels['whatsapp'])) {
                WhatsAppService::recordOptOut(null, $phone, 'dashboard');
            } else {
                WhatsAppService::clearOptOut(null, $phone);
            }
        }
    }
}
