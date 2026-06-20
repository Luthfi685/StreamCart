<?php

namespace App\Services;

use App\Models\LiveSession;
use App\Models\Product;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LiveSessionService
{
    public function create(User $seller, array $data): LiveSession
    {
        $session = LiveSession::create([
            'seller_id'    => $seller->id,
            'title'        => $data['title'],
            'description'  => $data['description'] ?? null,
            'thumbnail'    => $data['thumbnail']    ?? null,
            'stream_url'   => $data['stream_url']   ?? 'https://www.youtube.com/embed/jfKfPfyJRdk',
            'status'       => 'scheduled',
            'scheduled_at' => $data['scheduled_at'] ?? now(),
        ]);

        ActivityLog::create([
            'user_id'     => $seller->id,
            'action'      => 'create_live_session',
            'description' => "Seller {$seller->username} membuat sesi live: {$session->title}",
        ]);

        return $session->load('seller');
    }

    public function getAll(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = LiveSession::with(['seller', 'pinnedProduct'])
            ->orderByRaw("FIELD(status, 'live', 'scheduled', 'ended')")
            ->orderBy('created_at', 'desc');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->whereIn('status', ['live', 'scheduled']);
        }

        if (isset($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        return $query->get();
    }

    public function getById(int $id): LiveSession
    {
        return LiveSession::with(['seller', 'products', 'pinnedProduct'])->findOrFail($id);
    }

    public function updateStatus(LiveSession $session, string $status, User $user): LiveSession
    {
        $allowed = ['scheduled', 'live', 'ended'];
        if (! in_array($status, $allowed)) {
            throw ValidationException::withMessages(['status' => ['Status tidak valid.']]);
        }

        // Only owner or admin can change status
        if ($user->role !== 'admin' && $session->seller_id !== $user->id) {
            abort(403, 'Anda tidak berhak mengubah status sesi ini.');
        }

        $session->update(['status' => $status]);

        ActivityLog::create([
            'user_id'     => $user->id,
            'action'      => 'update_live_status',
            'description' => "Status live session #{$session->id} diubah ke '{$status}'.",
        ]);

        return $session->fresh(['seller', 'pinnedProduct']);
    }

    public function bindProducts(LiveSession $session, array $productIds, User $seller): void
    {
        if ($session->seller_id !== $seller->id) {
            abort(403, 'Anda tidak berhak mengelola sesi ini.');
        }

        // Validate all products belong to this seller
        $validIds = Product::where('seller_id', $seller->id)
                           ->whereIn('id', $productIds)
                           ->pluck('id')
                           ->toArray();

        // Sync without detaching (add new, keep existing)
        $syncData = [];
        foreach ($validIds as $pid) {
            $syncData[$pid] = ['is_pinned' => false];
        }
        $session->products()->syncWithoutDetaching($syncData);
    }

    public function getSessionProducts(LiveSession $session): \Illuminate\Database\Eloquent\Collection
    {
        return $session->products()->get();
    }

    public function pinProduct(LiveSession $session, ?int $productId, User $seller): LiveSession
    {
        if ($session->seller_id !== $seller->id) {
            abort(403, 'Anda tidak berhak mengelola sesi ini.');
        }

        if ($productId) {
            $isPinned = DB::table('live_session_products')
                ->where('live_session_id', $session->id)
                ->where('product_id', $productId)
                ->value('is_pinned');

            DB::table('live_session_products')
                ->where('live_session_id', $session->id)
                ->where('product_id', $productId)
                ->update(['is_pinned' => !$isPinned]);
        }

        return $session->fresh();
    }

    public function incrementLike(LiveSession $session): int
    {
        $session->increment('likes_count');
        return $session->fresh()->likes_count;
    }

    /**
     * MAIN POLLING ENDPOINT — returns live-status data every 3 seconds
     */
    public function getLiveStatus(LiveSession $session): array
    {
        $pinnedProducts = $session->products()->wherePivot('is_pinned', true)->get();

        $recentChats = $session->chatMessages()
            ->with('user:id,username,avatar,role')
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get()
            ->reverse()
            ->values();

        return [
            'session_id'     => $session->id,
            'title'          => $session->title,
            'status'         => $session->status,
            'stream_url'     => $session->stream_url,
            'viewer_count'   => $session->viewer_count,
            'likes_count'    => $session->likes_count,
            'pinned_products' => $pinnedProducts->map(function ($p) {
                return [
                    'id'        => $p->id,
                    'name'      => $p->name,
                    'price'     => $p->price,
                    'stock'     => $p->stock,
                    'image_url' => $p->image_url,
                ];
            })->toArray(),
            'recent_chats'   => $recentChats,
            'server_time'    => now()->toIso8601String(),
        ];
    }
}
