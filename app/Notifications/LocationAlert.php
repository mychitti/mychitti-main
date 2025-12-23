<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class LocationAlert extends Notification
{
    public function via($notifiable)
    {
        return ['broadcast', 'database'];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'title' => 'Location Updated',
            'body' => 'User location has been updated.',
        ]);
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Location Updated',
            'body' => 'User location has been updated.',
        ];
    }
}
