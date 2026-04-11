<?php

namespace App\Jobs;

use App\Mail\ScheduledNotificationMail;
use App\Models\DeliveryMan;
use App\Models\ScheduledEmail;
use App\Models\Store;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendScheduledEmailBroadcast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $scheduledEmailId) {}

    public function handle(): void
    {
        $scheduled = ScheduledEmail::find($this->scheduledEmailId);

        if (!$scheduled || $scheduled->status !== 'pending') {
            return;
        }

        try {
            $emails = $this->getRecipientEmails($scheduled->send_to);

            foreach (array_chunk($emails, 50) as $chunk) {
                foreach ($chunk as $email) {
                    Mail::to($email)->queue(new ScheduledNotificationMail(
                        notifTitle: $scheduled->subject,
                        notifMessage: $scheduled->message,
                    ));
                }
            }

            $scheduled->update(['status' => 'sent', 'sent_at' => now()]);
            Log::info("ScheduledEmail #{$this->scheduledEmailId} sent to " . count($emails) . " recipients.");
        } catch (\Throwable $e) {
            $scheduled->update(['status' => 'failed', 'error' => $e->getMessage()]);
            Log::error("ScheduledEmail #{$this->scheduledEmailId} failed: " . $e->getMessage());
        }
    }

    private function getRecipientEmails(string $sendTo): array
    {
        return match ($sendTo) {
            'all_vendors'       => Store::withoutGlobalScopes()->whereNotNull('email')->pluck('email')->filter()->unique()->values()->toArray(),
            'all_delivery_men'  => DeliveryMan::whereNotNull('email')->pluck('email')->filter()->unique()->values()->toArray(),
            default             => User::whereNotNull('email')->pluck('email')->filter()->unique()->values()->toArray(),
        };
    }
}
