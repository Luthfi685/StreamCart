<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get user's notifications.
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        
        $notifications = $user->notifications()->paginate(20);
        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'status' => 'success',
            'data' => $notifications->items(),
            'unread_count' => $unreadCount,
            'current_page' => $notifications->currentPage(),
            'last_page' => $notifications->lastPage()
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();
            return response()->json(['status' => 'success', 'message' => 'Notifikasi ditandai sudah dibaca']);
        }

        return response()->json(['status' => 'error', 'message' => 'Notifikasi tidak ditemukan'], 404);
    }
    
    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['status' => 'success', 'message' => 'Semua notifikasi ditandai sudah dibaca']);
    }
}
