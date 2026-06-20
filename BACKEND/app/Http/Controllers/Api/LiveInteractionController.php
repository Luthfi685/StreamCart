<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LiveSession;
use App\Models\Product;
use App\Models\ChatMessage;
use App\Events\ChatMessageSent;
use App\Events\ProductPinned;

class LiveInteractionController extends Controller
{
    public function sendMessage(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string|max:500'
        ]);

        $session = LiveSession::findOrFail($id);
        
        $user = $request->user();
        
        // Simpan pesan ke database
        $chat = ChatMessage::create([
            'live_session_id' => $id,
            'user_id' => $user->id,
            'message' => $request->message
        ]);

        $messageData = [
            'id' => $chat->id,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name)
            ],
            'message' => $chat->message,
            'created_at' => $chat->created_at->toISOString()
        ];

        // Broadcast event
        broadcast(new ChatMessageSent($id, $messageData));

        return response()->json(['success' => true, 'message' => $messageData]);
    }

    public function getPinnedProduct($id)
    {
        $session = LiveSession::findOrFail($id);
        if (!$session->pinned_product_id) {
            return response()->json(['success' => false, 'product' => null]);
        }
        
        $product = Product::find($session->pinned_product_id);
        return response()->json(['success' => true, 'product' => $product]);
    }
}
