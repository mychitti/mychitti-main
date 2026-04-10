<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Traits\NotificationTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendScheduledNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, NotificationTrait;

    public function handle(): void
    {
        Notification::where('is_scheduled', 1)
            ->whereNotNull('schedule_time')
            ->where('schedule_time', '<=', now()) 
            ->whereNull('sent_time')
            ->chunk(20, function ($notifications) {
                foreach ($notifications as $notification) {
                        Log::info("Processing notification ID: " . $notification->id);

                    try {
                        $topic = match ($notification->tergat) {
                            'deliveryman' => 'deliveryman',
                            default => 'general',
                        };

                        $data = [
                            'title' => $notification->title,
                            'description' => $notification->description,
                            'image' => $notification->image ? asset('storage/app/public/notification/' . $notification->image) : '',
                        ];

                        $this->sendPushNotificationToTopic($data, $topic, 'general');

                        $notification->update(['sent_time' => now()]);
        Log::info("Notification {$notification->id} sent");

                        Log::info("Scheduled notification {$notification->id} sent to {$topic}");
                    } catch (\Throwable $e) {
                                Log::error("Notification {$notification->id} failed: " . $e->getMessage());

                        Log::error("Scheduled notification {$notification->id} failed: " . $e->getMessage());
                    }
                }
            });
    }
}

