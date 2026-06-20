<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sessionId;
    public $message;

    public function __construct($sessionId, $message)
    {
        $this->sessionId = $sessionId;
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('live-session.' . $this->sessionId),
        ];
    }

    public function broadcastAs()
    {
        return 'chat.message';
    }
}
