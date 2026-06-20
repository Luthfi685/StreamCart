<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductPinned implements ShouldBroadcastNow
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
            new Channel('live-session.' . $this->sessionId),
        ];
    }

    public function broadcastAs()
    {
        return 'product.pinned';
    }
}
