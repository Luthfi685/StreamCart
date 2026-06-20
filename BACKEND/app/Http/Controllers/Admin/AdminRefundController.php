<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class AdminRefundController extends Controller
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    public function index()
    {
        // Get all cancelled orders that require refund
        $refunds = Order::where('status', 'cancelled')
            ->whereNotNull('payment_verified_at')
            ->whereNotNull('refund_bank_account')
            ->orderBy('is_refunded', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.refunds.index', compact('refunds'));
    }

    public function process(Request $request, int $id)
    {
        $request->validate([
            'refund_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $order = Order::findOrFail($id);

        if ($order->status !== 'cancelled' || !$order->payment_verified_at) {
            return back()->with('error', 'Pesanan ini tidak memenuhi syarat untuk refund.');
        }

        if ($order->is_refunded) {
            return back()->with('error', 'Dana pesanan ini sudah dikembalikan.');
        }

        $path = $request->file('refund_proof')->store('refund-proofs', 'public');

        $order->update([
            'is_refunded' => true,
            'refund_proof' => $path,
            'refund_processed_at' => now(),
        ]);

        // Notify Buyer
        $message = "Dana pesanan Anda (TRX-{$order->id}) sebesar Rp " . number_format($order->total_price, 0, ',', '.') . " telah berhasil dikembalikan ke rekening Anda.";
        $order->buyer->notify(new \App\Notifications\OrderStatusNotification($order, $message));

        return back()->with('success', 'Refund berhasil diproses.');
    }
}
