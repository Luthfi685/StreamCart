<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SupportRoom;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SupportChatController extends Controller
{
    // ====== VIEWS (Blade) ======

    public function sellerView(Request $request)
    {
        // Mock Auth for Demo, if not logged in
        $sellerId = $request->query('seller_id', 2); // Default to user ID 2 as seller
        $seller = User::find($sellerId);
        
        if (!$seller) {
            // Create dummy user if missing
            $seller = User::firstOrCreate(
                ['email' => 'seller_mock@example.com'],
                ['name' => 'Seller Mock', 'password' => bcrypt('password'), 'role' => 'seller', 'is_verified' => true]
            );
            $sellerId = $seller->id;
        }

        // Get or create room
        $room = SupportRoom::firstOrCreate(
            ['seller_id' => $sellerId],
            ['status' => 'open']
        );

        return view('seller.support', compact('room', 'seller'));
    }

    public function adminView(Request $request)
    {
        // Admin Mock
        $adminId = $request->query('admin_id', 1);
        $admin = User::find($adminId);

        if (!$admin) {
             $admin = User::firstOrCreate(
                ['email' => 'admin_mock@example.com'],
                ['name' => 'Admin Mock', 'password' => bcrypt('password'), 'role' => 'admin', 'is_verified' => true]
            );
        }

        return view('admin.support', compact('admin'));
    }

    // ====== API / AJAX ======

    public function getRooms()
    {
        $rooms = SupportRoom::with('seller')
            ->withCount(['messages as unread_count' => function ($query) {
                $query->where('is_read', false)->where('sender_role', 'seller');
            }])
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json(['rooms' => $rooms]);
    }

    public function getMessages($roomId, Request $request)
    {
        $role = $request->query('role'); // 'seller' or 'admin'
        
        $messages = SupportMessage::where('support_room_id', $roomId)
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark as read based on role
        if ($role === 'admin') {
            SupportMessage::where('support_room_id', $roomId)
                ->where('sender_role', 'seller')
                ->where('is_read', false)
                ->update(['is_read' => true]);
        } elseif ($role === 'seller') {
            SupportMessage::where('support_room_id', $roomId)
                ->where('sender_role', 'admin')
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        return response()->json(['messages' => $messages]);
    }

    public function sendMessage(Request $request, $roomId)
    {
        $request->validate([
            'sender_id' => 'required|exists:users,id',
            'sender_role' => 'required|in:seller,admin',
            'message' => 'required|string',
        ]);

        $message = SupportMessage::create([
            'support_room_id' => $roomId,
            'sender_id' => $request->sender_id,
            'sender_role' => $request->sender_role,
            'message' => $request->message,
            'is_read' => false,
        ]);

        // Touch room updated_at
        $message->room->touch();

        return response()->json(['message' => $message->load('sender')]);
    }
}
