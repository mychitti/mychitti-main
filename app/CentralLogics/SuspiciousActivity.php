<?php

namespace App\CentralLogics;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SuspiciousActivity
{
    // Thresholds
    const OTP_LIMIT      = 5;    // OTP requests within window
    const OTP_WINDOW     = 600;  // seconds (10 min)
    const ENQUIRY_LIMIT  = 3;    // same service within window
    const ENQUIRY_WINDOW = 3600; // seconds (1 hour)

    public static function checkOtp(string $phone, ?int $userId = null, ?string $ip = null): void
    {
        try {
            $since = now()->subSeconds(self::OTP_WINDOW);

            $otpHits = DB::table('phone_verifications')
                ->where('phone', $phone)
                ->where('updated_at', '>=', $since)
                ->value('otp_hit_count') ?? 0;

            $logCount = DB::table('suspicious_activity_logs')
                ->where('phone', $phone)
                ->where('type', 'otp_abuse')
                ->where('updated_at', '>=', $since)
                ->count();

            $total = $otpHits + $logCount + 1;

            if ($total >= self::OTP_LIMIT) {
                self::upsert('otp_abuse', $phone, $userId, 'Excessive OTP requests', $total, $ip);
            }
        } catch (\Throwable $e) {
            Log::warning('SuspiciousActivity::checkOtp failed: ' . $e->getMessage());
        }
    }

    public static function checkEnquiry(int $userId, int $itemId, string $itemName, ?string $ip = null): void
    {
        try {
            $since = now()->subSeconds(self::ENQUIRY_WINDOW);

            $count = DB::table('service_requests')
                ->where('user_id', $userId)
                ->where('item_id', $itemId)
                ->where('created_at', '>=', $since)
                ->count();

            if ($count >= self::ENQUIRY_LIMIT) {
                self::upsert(
                    'enquiry_abuse',
                    null,
                    $userId,
                    ($itemName ?: 'item_id:' . $itemId),
                    $count,
                    $ip
                );
            }
        } catch (\Throwable $e) {
            Log::warning('SuspiciousActivity::checkEnquiry failed: ' . $e->getMessage());
        }
    }

    private static function upsert(
        string $type,
        ?string $phone,
        ?int $userId,
        string $detail,
        int $count,
        ?string $ip
    ): void {
        $existing = DB::table('suspicious_activity_logs')
            ->where('type', $type)
            ->where('status', 'open')
            ->when($userId,              fn($q) => $q->where('user_id', $userId))
            ->when(!$userId && $phone,   fn($q) => $q->where('phone', $phone))
            ->whereDate('created_at', today())
            ->first();

        if ($existing) {
            DB::table('suspicious_activity_logs')
                ->where('id', $existing->id)
                ->update(['count' => $count, 'updated_at' => now()]);
        } else {
            DB::table('suspicious_activity_logs')->insert([
                'user_id'    => $userId,
                'phone'      => $phone,
                'type'       => $type,
                'detail'     => $detail,
                'count'      => $count,
                'ip'         => $ip,
                'status'     => 'open',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
