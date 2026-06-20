<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class ProductPinnedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sessionId;
    public $product;

    public function __construct($sessionId, $product)
    {
        $this->sessionId = $sessionId;
        $this->product = $product;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('live-stream.' . $this->sessionId),
        ];
    }

    public function broadcastAs()
    {
        return 'ProductPinnedEvent';
    }
}
