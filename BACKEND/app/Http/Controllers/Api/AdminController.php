<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\LiveSession;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(private WalletService $walletService)
    {
        if (request()->user() && request()->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * GET /api/admin/stats
     * Statistik ringkasan untuk dashboard Admin.
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_users'         => User::count(),
            'total_live_sessions' => LiveSession::count(),
            'total_products'      => Product::count(),
            'total_orders'        => Order::count(),
            'total_revenue'       => Order::where('status', 'completed')->sum('total_price'),

            // Wallet & Payment Stats
            'platform_fee_earned'    => Order::where('status', 'completed')->sum('platform_fee_amount'),
            'pending_payment_verify' => Order::where('status', 'checking_admin')->count(),
            'pending_withdrawals'    => Withdrawal::where('status', 'pending')->count(),
        ];

        return response()->json($stats);
    }

    /**
     * GET /api/admin/users
     */
    public function users(): JsonResponse
    {
        $users = User::orderBy('created_at', 'desc')->get();
        return response()->json($users);
    }

    /**
     * GET /api/admin/transactions
     */
    public function transactions(): JsonResponse
    {
        $orders = Order::with([
            'buyer:id,name,username',
            'seller:id,name,username,store_name',
            'items.product:id,name',
            'liveSession:id,title',
        ])->orderBy('created_at', 'desc')->get();

        return response()->json($orders);
    }

    /**
     * GET /api/admin/activity-logs
     */
    public function activityLogs(): JsonResponse
    {
        $logs = ActivityLog::with('user:id,name,username,role')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json($logs);
    }

    // ─────────────────────────────────────────────────────────
    //  PAYMENT VERIFICATION
    // ─────────────────────────────────────────────────────────

    /**
     * GET /api/admin/orders/pending-payments
     * List order yang perlu diverifikasi bukti bayarnya (status: checking_admin).
     */
    public function pendingPayments(): JsonResponse
    {
        $orders = Order::with([
            'buyer:id,name,email,username',
            'seller:id,name,store_name',
            'items.product:id,name',
        ])
        ->where('status', 'checking_admin')
        ->orderBy('payment_proof_uploaded_at', 'asc') // Terlama pertama
        ->get()
        ->map(function ($order) {
            $order->payment_proof_url = $order->payment_proof
                ? asset('storage/' . $order->payment_proof)
                : null;
            return $order;
        });

        return response()->json([
            'count'  => $orders->count(),
            'orders' => $orders,
        ]);
    }

    /**
     * PUT /api/admin/orders/{id}/verify-payment
     * Admin memverifikasi bukti bayar → success atau fail.
     *
     * Body: { "action": "success"|"fail", "admin_note": "..." }
     */
    public function verifyPayment(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'action'     => 'required|in:success,fail',
            'admin_note' => 'nullable|string|max:500',
        ]);

        $order = Order::findOrFail($id);

        if ($order->status !== 'checking_admin') {
            return response()->json([
                'message' => 'Order tidak dalam status "checking_admin".',
            ], 422);
        }

        $order->update([
            'status'               => $request->action,  // 'success' atau 'fail'
            'admin_payment_note'   => $request->admin_note,
            'payment_verified_by'  => $request->user()->id,
            'payment_verified_at'  => now(),
        ]);

        $msg = $request->action === 'success'
            ? '✅ Pembayaran dikonfirmasi valid. Seller dapat mulai memproses pesanan.'
            : '❌ Bukti pembayaran ditolak. Order ditandai gagal.';

        return response()->json([
            'message' => $msg,
            'order'   => $order->fresh()->load('buyer:id,name,email', 'seller:id,name'),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    //  WITHDRAWAL MANAGEMENT
    // ─────────────────────────────────────────────────────────

    /**
     * GET /api/admin/withdrawals
     * List semua pengajuan penarikan dana.
     */
    public function withdrawals(Request $request): JsonResponse
    {
        $query = Withdrawal::with('seller:id,name,email,store_name')
            ->orderBy('created_at', 'desc');

        // Filter by status (opsional): ?status=pending
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $withdrawals = $query->paginate(20);

        return response()->json($withdrawals);
    }

    /**
     * PUT /api/admin/withdrawals/{id}
     * Admin approve atau reject pengajuan withdrawal Seller.
     *
     * Body: { "action": "approve"|"reject", "admin_note": "..." }
     */
    public function processWithdrawal(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'action'     => 'required|in:approve,reject',
            'admin_note' => 'nullable|string|max:500',
        ]);

        $withdrawal = Withdrawal::findOrFail($id);

        try {
            $withdrawal = $this->walletService->processWithdrawal(
                withdrawal: $withdrawal,
                action:     $request->action,
                adminId:    $request->user()->id,
                adminNote:  $request->admin_note,
            );
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $msg = $request->action === 'approve'
            ? '✅ Withdrawal disetujui. Saldo Seller berhasil ditandai cair.'
            : '❌ Withdrawal ditolak. Saldo dikembalikan ke wallet Seller.';

        return response()->json([
            'message'    => $msg,
            'withdrawal' => $withdrawal,
        ]);
    }
}
