<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\LiveSession;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Instant purchase — locks stock row to prevent race condition
     */
    public function placeOrder(User $buyer, array $data): Order
    {
        return DB::transaction(function () use ($buyer, $data) {
            $totalOrderPrice = 0;
            $sellerId = null;
            $orderItemsData = [];

            // Verify and lock all products
            foreach ($data['items'] as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                
                if ($sellerId === null) {
                    $sellerId = $product->seller_id;
                } elseif ($sellerId !== $product->seller_id) {
                    abort(422, 'Semua produk dalam satu pesanan harus dari toko (seller) yang sama.');
                }

                if ($product->stock < $item['quantity']) {
                    abort(422, "Stok {$product->name} tidak mencukupi.");
                }
                if (! $product->is_active) {
                    abort(422, "Produk {$product->name} tidak tersedia.");
                }

                $subtotal = $product->price * $item['quantity'];
                $totalOrderPrice += $subtotal;

                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal'   => $subtotal,
                ];

                $product->decrement('stock', $item['quantity']);
            }

            $shippingFee = $data['shipping_fee'] ?? 0;

            $order = Order::create([
                'buyer_id'         => $buyer->id,
                'seller_id'        => $sellerId,
                'live_session_id'  => $data['live_session_id'] ?? null,
                'total_price'      => $totalOrderPrice + $shippingFee,
                'shipping_fee'     => $shippingFee,
                'status'           => 'pending',
                'shipping_address' => $data['shipping_address'] ?? null,
                'shipping_province'=> $data['shipping_province'] ?? null,
                'shipping_city'    => $data['shipping_city']    ?? null,
                'shipping_district'=> $data['shipping_district']?? null,
                'notes'            => $data['notes']            ?? null,
            ]);

            // Create items
            $order->items()->createMany($orderItemsData);

            ActivityLog::create([
                'user_id'     => $buyer->id,
                'action'      => 'place_order',
                'description' => "Buyer {$buyer->username} membuat pesanan #{$order->id} dengan " . count($orderItemsData) . " macam produk.",
            ]);

            $order->load(['items.product', 'seller', 'liveSession', 'buyer']);

            try {
                \Illuminate\Support\Facades\Mail::to($order->seller->email)->send(new \App\Mail\NewOrderMail($order));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed sending NewOrderMail: " . $e->getMessage());
            }

            return $order;
        });
    }

    public function getOrdersForSeller(User $seller, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Order::with(['buyer:id,name,username', 'items.product:id,name,image_url', 'liveSession:id,title'])
            ->where('seller_id', $seller->id)
            ->orderBy('created_at', 'desc');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get();
    }

    public function getHistoryForBuyer(User $buyer): \Illuminate\Database\Eloquent\Collection
    {
        return Order::with(['items.product:id,name,image_url,price', 'seller:id,name,username,store_name', 'liveSession:id,title', 'reviews:id,order_id,product_id'])
            ->where('buyer_id', $buyer->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function updateStatus(Order $order, string $status, User $actor): Order
    {
        $allowed = ['pending', 'checking_admin', 'success', 'fail', 'processed', 'completed', 'cancelled'];
        if (! in_array($status, $allowed)) {
            abort(422, 'Status tidak valid.');
        }

        // Only seller/admin can update
        if ($actor->role === 'buyer') {
            abort(403, 'Buyer tidak dapat mengubah status pesanan.');
        }

        // Validasi khusus: Hanya Admin yang boleh konfirmasi pembayaran (success / fail)
        if (in_array($status, ['success', 'fail']) && $actor->role !== 'admin') {
            abort(403, 'Hanya Admin yang dapat mengkonfirmasi bukti pembayaran.');
        }

        if ($actor->role === 'seller' && $order->seller_id !== $actor->id) {
            abort(403, 'Anda tidak berhak mengubah pesanan ini.');
        }

        $order->update(['status' => $status]);

        ActivityLog::create([
            'user_id'     => $actor->id,
            'action'      => 'update_order_status',
            'description' => "Order #{$order->id} diubah statusnya ke '{$status}' oleh {$actor->username}.",
        ]);

        return $order->fresh(['items.product', 'buyer', 'seller']);
    }

    public function getAllTransactions(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Order::with(['buyer:id,name,username', 'seller:id,name,username,store_name', 'items.product:id,name,image_url', 'liveSession:id,title'])
            ->orderBy('created_at', 'desc');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get();
    }
}
