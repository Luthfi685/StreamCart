<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductsPinned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sessionId;
    public $products;

    public function __construct($sessionId, $products)
    {
        $this->sessionId = $sessionId;
        $this->products = $products;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('live-session.' . $this->sessionId),
        ];
    }

    public function broadcastAs()
    {
        return 'products.pinned';
    }
}
