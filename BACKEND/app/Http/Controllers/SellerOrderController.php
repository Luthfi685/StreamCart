<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;

class SellerOrderController extends Controller
{
    public function index(Request $request)
    {
        $user   = $request->user();
        $orders = Order::where('seller_id', $user->id)
                       ->with(['buyer:id,name,username', 'items.product:id,name'])
                       ->orderBy('created_at', 'desc')
                       ->paginate(15);

        // Latest order id — used by JS to detect new orders
        $latestOrderId = Order::where('seller_id', $user->id)->max('id') ?? 0;

        return view('seller.orders.index', compact('user', 'orders', 'latestOrderId'));
    }

    /**
     * GET /seller/api/orders?after={id}
     * Returns orders newer than the given ID (for new-order notification polling)
     */
    public function realtimeOrders(Request $request)
    {
        $user    = $request->user();
        $afterId = (int) $request->query('after', 0);

        $orders = Order::where('seller_id', $user->id)
                       ->where('id', '>', $afterId)
                       ->with(['buyer:id,name,username', 'items.product:id,name'])
                       ->orderBy('created_at', 'desc')
                       ->take(20)
                       ->get()
                       ->map(fn($o) => [
                           'id'           => $o->id,
                           'code'         => 'TRX-' . str_pad($o->id, 5, '0', STR_PAD_LEFT),
                           'buyer_name'   => $o->buyer?->name ?? '—',
                           'product_name' => $o->items->first()?->product?->name ?? '—',
                           'total_price'  => 'Rp ' . number_format($o->total_price, 0, ',', '.'),
                           'status'       => $o->status,
                           'created_at'   => \Carbon\Carbon::parse($o->created_at)->diffForHumans(),
                       ]);

        return response()->json($orders);
    }

    /**
     * PATCH /seller/orders/{id}/status
     * Seller update status: processed → completed, dsb.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:processed,shipped,cancelled',
            'shipping_courier' => 'required_if:status,shipped|nullable|string',
            'shipping_tracking_number' => 'required_if:status,shipped|nullable|string',
        ]);
        
        $order = Order::where('seller_id', auth()->id())->findOrFail($id);
        
        $updateData = ['status' => $request->status];

        if ($request->status === 'shipped') {
            $updateData['shipping_courier'] = $request->shipping_courier;
            $updateData['shipping_tracking_number'] = $request->shipping_tracking_number;
        }

        $order->update($updateData);

        // Notify buyer
        $message = "Pesanan Anda (TRX-{$order->id}) telah diubah statusnya menjadi {$request->status}.";
        if ($request->status === 'shipped') {
            $message = "Pesanan Anda telah dikirim via {$request->shipping_courier}. No. Resi: {$request->shipping_tracking_number}";
        }
        $order->buyer->notify(new \App\Notifications\OrderStatusNotification($order, $message));

        return redirect()->back()->with('success', 'Status pesanan berhasil diperbarui!');
    }

    /**
     * PATCH /seller/orders/{id}/respond-cancel
     * Seller respond to buyer's cancellation request
     */
    public function respondCancel(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
        ]);

        $order = Order::where('seller_id', auth()->id())->findOrFail($id);

        if ($order->status !== 'pending_cancel') {
            return redirect()->back()->with('error', 'Pesanan ini tidak sedang dalam pengajuan pembatalan.');
        }

        if ($request->action === 'approve') {
            $order->update([
                'status' => 'cancelled',
            ]);
            $msg = 'Pengajuan pembatalan disetujui. Pesanan Dibatalkan.';

            // Notify Admin to process refund
            app(\App\Services\NotificationService::class)->notifyAdminRefundRequired($order);
            
            // Notify Buyer
            $order->buyer->notify(new \App\Notifications\OrderStatusNotification($order, "Pesanan Anda (TRX-{$order->id}) telah DIBATALKAN oleh Penjual."));
        } else {
            $order->update([
                'status' => 'processed',
                'cancel_reason' => null,
                'cancel_requested_by' => null,
            ]);
            $msg = 'Pengajuan pembatalan ditolak. Pesanan kembali dilanjutkan (Sedang Dikemas).';
            
            // Notify Buyer
            $order->buyer->notify(new \App\Notifications\OrderStatusNotification($order, "Pengajuan pembatalan pesanan Anda (TRX-{$order->id}) DITOLAK oleh Penjual. Pesanan sedang diproses."));
        }

        return redirect()->back()->with('success', $msg);
    }
}
