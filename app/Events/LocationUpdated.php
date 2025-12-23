<?php 
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Log;

class LocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $location;
    public $broadcastQueue = null;

    public function __construct($location)
    {
        $this->location = $location;

        // Log the event being fired
       print_r('LocationUpdated Event Fired: ', $location);
    }

    public function broadcastOn()
    {
        return new Channel('locations');
    }

    public function broadcastAs()
    { 
        return 'location-updated';
    }

    public function broadcastWith()
    {
        return $this->location;
    }
}
