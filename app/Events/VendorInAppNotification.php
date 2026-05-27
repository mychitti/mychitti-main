<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class VendorInAppNotification implements ShouldBroadcastNow
{
    use SerializesModels;

    public int $storeId;
    public string $title;
    public string $message;
    public ?string $url;

    public function __construct(int $storeId, string $title, string $message, ?string $url = null)
    {
        $this->storeId = $storeId;
        $this->title   = $title;
        $this->message = $message;
        $this->url     = $url;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('vendor-notifications.' . $this->storeId);
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
        ];
    }
}
