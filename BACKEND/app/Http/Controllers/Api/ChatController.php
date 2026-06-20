<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LiveSession;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    public function __construct(private ChatService $chatService) {}

    public function store(Request $request, int $sessionId): JsonResponse
    {
        $data = $request->validate([
            'message' => 'required|string|max:500',
            'type'    => 'nullable|string|in:text,emoji,system',
        ]);

        $session = LiveSession::findOrFail($sessionId);
        
        $chat = $this->chatService->sendMessage(
            $session, 
            $request->user(), 
            $data['message'], 
            $data['type'] ?? 'text'
        );

        return response()->json($chat, 201);
    }

    public function index(int $sessionId): JsonResponse
    {
        $session = LiveSession::findOrFail($sessionId);
        $messages = $this->chatService->getMessages($session);

        return response()->json($messages);
    }
}
