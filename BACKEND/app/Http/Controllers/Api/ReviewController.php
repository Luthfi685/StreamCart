<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * POST /api/orders/{id}/reviews
     * Submit multiple reviews for products in a completed order.
     */
    public function submitReviews(Request $request, int $id)
    {
        $request->validate([
            'reviews'              => 'required|array|min:1',
            'reviews.*.product_id' => 'required|exists:products,id',
            'reviews.*.rating'     => 'required|integer|min:1|max:5',
            'reviews.*.comment'    => 'nullable|string',
        ]);

        $order = Order::with('items')->findOrFail($id);

        if ($order->buyer_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($order->status !== 'completed') {
            return response()->json([
                'message' => 'Pesanan harus selesai untuk diulas.'
            ], 422);
        }

        // Validate products belong to order
        $orderedProductIds = $order->items->pluck('product_id')->toArray();

        DB::beginTransaction();
        try {
            foreach ($request->reviews as $reviewData) {
                // Pastikan produk yang diulas benar-benar dibeli di order ini
                if (!in_array($reviewData['product_id'], $orderedProductIds)) {
                    continue;
                }

                // Insert or ignore jika sudah ada (unique constraint di database)
                Review::firstOrCreate(
                    [
                        'order_id'   => $order->id,
                        'product_id' => $reviewData['product_id'],
                        'user_id'    => $request->user()->id,
                    ],
                    [
                        'rating'  => $reviewData['rating'],
                        'comment' => $reviewData['comment'] ?? null,
                    ]
                );
            }
            DB::commit();

            return response()->json([
                'message' => 'Ulasan berhasil disimpan! Terima kasih atas feedback Anda.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyimpan ulasan: ' . $e->getMessage()], 500);
        }
    }
}
