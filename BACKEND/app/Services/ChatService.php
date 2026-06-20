<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\LiveSession;
use App\Models\User;

class ChatService
{
    public function sendMessage(LiveSession $session, User $user, string $message, string $type = 'text'): ChatMessage
    {
        if ($session->status !== 'live') {
            abort(422, 'Tidak bisa mengirim pesan — sesi tidak sedang live.');
        }

        $chat = ChatMessage::create([
            'live_session_id' => $session->id,
            'user_id'         => $user->id,
            'message'         => $message,
            'type'            => $type,
        ]);

        $chat->load('user:id,username,name,avatar,role');

        // Broadcast ke WebSocket public channel 'live-chat.{id}'
        // event(new \App\Events\ChatMessageSentEvent($session->id, $chat));

        return $chat;
    }

    public function getMessages(LiveSession $session, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return $session->chatMessages()
            ->with('user:id,username,avatar,role')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }
}
