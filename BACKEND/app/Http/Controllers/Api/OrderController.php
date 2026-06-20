<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\NotificationService;
use App\Services\OrderService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService        $orderService,
        private WalletService       $walletService,
        private NotificationService $notificationService,
    ) {}

    /**
     * POST /api/orders
     * Buyer membuat pesanan baru.
     * Response menyertakan info rekening Admin untuk transfer.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items'             => 'required|array|min:1',
            'items.*.product_id'=> 'required|integer|exists:products,id',
            'items.*.quantity'  => 'required|integer|min:1',
            'live_session_id'   => 'nullable|integer|exists:live_sessions,id',
            'shipping_address'  => 'nullable|string',
            'shipping_fee'      => 'nullable|numeric|min:0',
            'shipping_province' => 'nullable|string',
            'shipping_city'     => 'nullable|string',
            'shipping_district' => 'nullable|string',
            'notes'             => 'nullable|string',
        ]);

        $order = $this->orderService->placeOrder($request->user(), $data);

        // Info rekening Admin untuk ditampilkan ke Buyer
        $bankInfo = config('wallet.admin_bank');

        return response()->json([
            'message' => 'Pesanan berhasil dibuat! Silakan lakukan transfer dan upload bukti pembayaran.',
            'order'   => $order,
            'payment_instruction' => [
                'bank_name'    => $bankInfo['name'],
                'bank_account' => $bankInfo['account'],
                'account_name' => $bankInfo['account_name'],
                'amount'       => $order->total_price,
                'amount_label' => 'Rp ' . number_format($order->total_price, 0, ',', '.'),
                'note'         => "Harap transfer tepat sesuai jumlah dan upload bukti bayar.",
                'upload_url'   => "/api/orders/{$order->id}/payment-proof",
            ],
        ], 201);
    }

    /**
     * GET /api/orders
     * List pesanan (kontekstual berdasarkan role).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'seller') {
            $orders = $this->orderService->getOrdersForSeller($user, $request->only(['status']));
        } else {
            $orders = $this->orderService->getHistoryForBuyer($user);
        }

        return response()->json($orders);
    }

    /**
     * GET /api/transactions/history
     * Riwayat pembelian Buyer.
     */
    public function history(Request $request): JsonResponse
    {
        $orders = $this->orderService->getHistoryForBuyer($request->user());
        return response()->json($orders);
    }

    /**
     * POST /api/orders/{id}/payment-proof
     * Buyer upload bukti transfer. Trigger email notifikasi ke Admin.
     */
    public function uploadPaymentProof(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            // Max 5MB, format: gambar atau PDF
        ]);

        $order = Order::findOrFail($id);

        // Guard: hanya Buyer pemilik order
        if ($order->buyer_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Guard: hanya bisa upload saat status pending
        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Bukti pembayaran hanya bisa diupload saat status pesanan masih "pending".',
            ], 422);
        }

        // Simpan file ke storage/app/public/payment-proofs/
        $path = $request->file('payment_proof')->store('payment-proofs', 'public');

        // Snapshot info rekening Admin ke order (histori permanen)
        $bankInfo = config('wallet.admin_bank');

        $order->update([
            'status'                    => 'checking_admin',
            'payment_proof'             => $path,
            'payment_proof_uploaded_at' => now(),
            'payment_bank_name'         => $bankInfo['name'],
            'payment_bank_account'      => $bankInfo['account'],
            'payment_bank_account_name' => $bankInfo['account_name'],
        ]);

        // Kirim email notifikasi ke Admin
        $this->notificationService->notifyAdminPaymentProof($order, $request->user());

        return response()->json([
            'message'      => 'Bukti pembayaran berhasil dikirim! Menunggu konfirmasi Admin (1×24 jam).',
            'status'       => 'checking_admin',
            'proof_url'    => asset('storage/' . $path),
        ]);
    }

    /**
     * PUT /api/orders/{id}/status
     * Seller mengubah status order (processed, dll).
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|string|in:pending,checking_admin,success,fail,processed,completed,cancelled',
        ]);

        $order       = Order::findOrFail($id);
        $updatedOrder = $this->orderService->updateStatus($order, $data['status'], $request->user());

        return response()->json(['message' => 'Status pesanan diperbarui.', 'order' => $updatedOrder]);
    }

    /**
     * POST /api/orders/{id}/confirm
     * Buyer konfirmasi pesanan sudah diterima → trigger escrow release ke Seller.
     */
    public function confirmComplete(Request $request, int $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        // Guard: hanya Buyer pemilik order
        if ($order->buyer_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Guard: harus status shipped (Seller sudah kirim barang)
        if ($order->status !== 'shipped') {
            return response()->json([
                'message' => 'Pesanan belum dikirim oleh Seller, tidak bisa dikonfirmasi selesai.',
            ], 422);
        }

        try {
            $order = $this->walletService->releaseEscrowToSeller($order);
            
            // Notify seller
            $message = "Pesanan (TRX-{$order->id}) telah diselesaikan oleh Buyer. Dana Escrow sebesar Rp " . number_format($order->seller_net_amount, 0, ',', '.') . " telah ditambahkan ke saldo Anda.";
            $order->seller->notify(new \App\Notifications\OrderStatusNotification($order, $message));
            
            // Notify buyer
            $buyerMsg = "Pesanan Anda (TRX-{$order->id}) telah berhasil diselesaikan.";
            $order->buyer->notify(new \App\Notifications\OrderStatusNotification($order, $buyerMsg));
            
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message'         => '✅ Pesanan selesai! Dana telah diteruskan ke Seller.',
            'order_status'    => 'completed',
            'seller_received' => 'Rp ' . number_format($order->seller_net_amount, 0, ',', '.'),
            'platform_fee'    => 'Rp ' . number_format($order->platform_fee_amount, 0, ',', '.'),
        ]);
    }

    /**
     * POST /api/orders/{id}/request-cancel
     * Buyer request cancellation.
     */
    public function requestCancel(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'reason_category' => 'required|string',
            'reason_detail'   => 'nullable|string',
        ]);

        $order = Order::findOrFail($id);

        if ($order->buyer_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!in_array($order->status, ['pending', 'checking_admin', 'processed'])) {
            return response()->json([
                'message' => 'Pembatalan tidak bisa dilakukan pada status ini.',
            ], 422);
        }

        $reason = $data['reason_category'];
        if (!empty($data['reason_detail'])) {
            $reason .= ' - ' . $data['reason_detail'];
        }

        if (in_array($order->status, ['pending', 'checking_admin'])) {
            $oldStatus = $order->status;
            $order->update([
                'status' => 'cancelled',
                'cancel_reason' => $reason,
                'cancel_requested_by' => 'buyer',
            ]);

            if ($oldStatus === 'checking_admin' || $order->payment_proof) {
                app(\App\Services\NotificationService::class)->notifyAdminRefundRequired($order);
            }

            return response()->json([
                'message' => 'Pesanan berhasil dibatalkan.',
                'order' => $order
            ]);
        }

        $order->update([
            'status' => 'pending_cancel',
            'cancel_reason' => $reason,
            'cancel_requested_by' => 'buyer',
        ]);

        return response()->json([
            'message' => 'Pengajuan pembatalan berhasil dikirim ke Penjual.',
            'order' => $order
        ]);
    }

    /**
     * POST /api/orders/{id}/respond-cancel
     * Seller respond to cancellation.
     */
    public function respondCancel(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'action' => 'required|in:approve,reject',
        ]);

        $order = Order::findOrFail($id);

        if ($order->seller_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($order->status !== 'pending_cancel') {
            return response()->json([
                'message' => 'Pesanan ini tidak sedang dalam pengajuan pembatalan.',
            ], 422);
        }

        if ($data['action'] === 'approve') {
            $order->update([
                'status' => 'cancelled',
            ]);
            // Notify Admin
            app(\App\Services\NotificationService::class)->notifyAdminRefundRequired($order);
            // Notify Buyer
            $order->buyer->notify(new \App\Notifications\OrderStatusNotification($order, "Pesanan Anda (TRX-{$order->id}) telah DIBATALKAN oleh Penjual."));
            $message = 'Pembatalan disetujui.';
        } else {
            $order->update([
                'status' => 'processed',
                'cancel_reason' => null,
                'cancel_requested_by' => null,
            ]);
            // Notify Buyer
            $order->buyer->notify(new \App\Notifications\OrderStatusNotification($order, "Pengajuan pembatalan pesanan Anda (TRX-{$order->id}) DITOLAK oleh Penjual. Pesanan sedang diproses."));
            $message = 'Pembatalan ditolak. Pesanan kembali dilanjutkan (Sedang Dikemas).';
        }

        return response()->json([
            'message' => $message,
            'order' => $order
        ]);
    }

    /**
     * POST /api/orders/{id}/refund-info
     * Buyer submits bank account info for refund after cancellation.
     */
    public function submitRefundInfo(Request $request, int $id): JsonResponse
    {
        if ($request->has('refund_bank_account')) {
            $request->merge([
                'refund_bank_account' => (string) $request->input('refund_bank_account')
            ]);
        }

        $data = $request->validate([
            'refund_bank_name' => 'required|string|max:100',
            'refund_bank_account' => 'required|string|max:100',
            'refund_bank_account_name' => 'required|string|max:100',
        ]);

        $order = Order::findOrFail($id);

        if ($order->buyer_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($order->status !== 'cancelled') {
            return response()->json([
                'message' => 'Hanya pesanan yang dibatalkan yang dapat mengajukan pengembalian dana.',
            ], 422);
        }

        if ($order->is_refunded) {
            return response()->json([
                'message' => 'Dana pesanan ini sudah dikembalikan.',
            ], 422);
        }

        if (!$order->payment_verified_at) {
            return response()->json([
                'message' => 'Anda belum melakukan pembayaran yang terverifikasi untuk pesanan ini.',
            ], 422);
        }

        $order->update($data);

        // Notify Admin
        app(\App\Services\NotificationService::class)->notifyAdminRefundRequired($order);

        return response()->json([
            'message' => 'Informasi rekening berhasil disimpan. Admin akan segera memproses pengembalian dana.',
            'order' => $order
        ]);
    }
}
