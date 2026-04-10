<?php

namespace App\Mail;

use App\Models\BusinessSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ScheduledNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $notifTitle,
        public string $notifMessage,
        public ?string $notifImage = null
    ) {}

    public function build(): static
    {
        $company = BusinessSetting::where('key', 'business_name')->first()?->value ?? 'MyChitti';

        return $this->subject($this->notifTitle)
            ->view('email-templates.scheduled-notification', [
                'company'  => $company,
                'title'    => $this->notifTitle,
                'message'  => $this->notifMessage,
                'image'    => $this->notifImage,
            ]);
    }
}
