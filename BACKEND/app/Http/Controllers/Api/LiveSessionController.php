<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LiveSession;
use App\Services\LiveSessionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LiveSessionController extends Controller
{
    public function __construct(private LiveSessionService $liveSessionService) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'seller_id']);
        $sessions = $this->liveSessionService->getAll($filters);

        return response()->json($sessions);
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'seller') {
            return response()->json(['message' => 'Hanya Seller yang dapat membuat sesi live.'], 403);
        }

        $data = $request->validate([
            'title'        => 'required|string|max:200',
            'description'  => 'nullable|string',
            'thumbnail'    => 'nullable|string',
            'stream_url'   => 'nullable|string|url',
            'scheduled_at' => 'nullable|date',
        ]);

        $session = $this->liveSessionService->create($request->user(), $data);

        return response()->json($session, 201);
    }

    public function show(int $id): JsonResponse
    {
        $session = $this->liveSessionService->getById($id);
        return response()->json($session);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|string|in:scheduled,live,finished',
        ]);

        $session = LiveSession::findOrFail($id);
        $updatedSession = $this->liveSessionService->updateStatus($session, $data['status'], $request->user());

        return response()->json(['message' => 'Status berhasil diubah.', 'session' => $updatedSession]);
    }

    public function bindProducts(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'product_ids'   => 'required|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $session = LiveSession::findOrFail($id);
        $this->liveSessionService->bindProducts($session, $data['product_ids'], $request->user());

        return response()->json(['message' => 'Produk berhasil dihubungkan ke sesi live.']);
    }

    public function getProducts(int $id): JsonResponse
    {
        $session = LiveSession::findOrFail($id);
        $products = $this->liveSessionService->getSessionProducts($session);

        return response()->json($products);
    }

    public function pinProduct(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $session = LiveSession::findOrFail($id);
        
        // Simpan status produk yang di-pin ke database
        \Illuminate\Support\Facades\DB::table('live_session_products')
            ->updateOrInsert(
                ['live_session_id' => $session->id, 'product_id' => $data['product_id']],
                ['is_pinned' => true]
            );

        // Ambil data produk
        $product = \App\Models\Product::find($data['product_id']);

        // Picu event
        event(new \App\Events\ProductPinnedEvent($session->id, $product));

        return response()->json(['message' => 'Produk disematkan.']);
    }

    public function unpinProduct(Request $request, int $id): JsonResponse
    {
        $session = LiveSession::findOrFail($id);
        
        // Hapus status pin dari database (set is_pinned = false atau hapus)
        \Illuminate\Support\Facades\DB::table('live_session_products')
            ->where('live_session_id', $session->id)
            ->update(['is_pinned' => false]);

        // Picu event unpin
        event(new \App\Events\ProductUnpinnedEvent($session->id));

        return response()->json(['message' => 'Produk dilepas dari sematan.']);
    }

    public function like(int $id): JsonResponse
    {
        $session = LiveSession::findOrFail($id);
        $likesCount = $this->liveSessionService->incrementLike($session);
        
        // event(new \App\Events\LiveStreamLiked($session->id));

        return response()->json(['likes_count' => $likesCount]);
    }

    /**
     * MAIN POLLING ENDPOINT (Dipanggil tiap 3 detik oleh frontend)
     */
    public function getLiveStatus(int $id): JsonResponse
    {
        $session = LiveSession::findOrFail($id);
        $statusData = $this->liveSessionService->getLiveStatus($session);

        return response()->json($statusData);
    }

    public function join(int $id): JsonResponse
    {
        $session = LiveSession::findOrFail($id);
        $session->increment('viewer_count');
        return response()->json(['message' => 'Joined successfully']);
    }

    public function leave(int $id): JsonResponse
    {
        $session = LiveSession::findOrFail($id);
        if ($session->viewer_count > 0) {
            $session->decrement('viewer_count');
        }
        return response()->json(['message' => 'Left successfully']);
    }
}
