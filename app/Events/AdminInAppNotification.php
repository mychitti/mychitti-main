<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class AdminInAppNotification implements ShouldBroadcastNow
{
    use SerializesModels; 

    public string $title;
    public string $message;
    public ?string $url;
    public ?string $type;

    public function __construct(string $title, string $message, ?string $url = null, ?string $type = null)
    {
        $this->title   = $title;
        $this->message = $message;
        $this->url     = $url;
        $this->type    = $type;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('admin-notifications');
    }

    public function broadcastAs(): string
    {
        return 'new-notification';
    }

    public function broadcastWith(): array
    {
        return [
            'title'   => $this->title,
            'message' => $this->message,
            'url'     => $this->url,
            'type'    => $this->type,
        ];
    }
}
