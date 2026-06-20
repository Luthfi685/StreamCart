<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class ChatMessageSentEvent implements ShouldBroadcastNow
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
            new Channel('live-chat.' . $this->sessionId),
        ];
    }

    public function broadcastAs()
    {
        return 'ChatMessageSentEvent';
    }
}
