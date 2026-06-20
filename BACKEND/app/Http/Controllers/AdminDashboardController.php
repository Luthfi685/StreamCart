<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LiveSession;
use App\Models\Order;
use App\Models\Withdrawal;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $totalUsers              = User::count();
        $activeLives             = LiveSession::where('status', 'live')->count();
        $totalEscrowTransactions = Order::whereIn('status', ['checking_admin', 'success', 'processed', 'completed'])->count();
        $totalPlatformRevenue    = Order::whereIn('status', ['success', 'processed', 'completed'])
                                        ->sum('platform_fee_amount');

        $users        = User::orderBy('created_at', 'desc')->take(10)->get();
        $transactions = Order::with(['buyer:id,name,username', 'seller:id,name,store_name'])
                             ->latest()
                             ->take(10)
                             ->get();
        $liveSessions = LiveSession::with('seller:id,name,store_name')
                                   ->where('status', 'live')
                                   ->latest()
                                   ->take(5)
                                   ->get();
        $withdrawals  = Withdrawal::with('seller:id,name,store_name')
                                  ->where('status', 'pending')
                                  ->latest()
                                  ->take(5)
                                  ->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'activeLives',
            'totalEscrowTransactions',
            'totalPlatformRevenue',
            'transactions',
            'users',
            'liveSessions',
            'withdrawals'
        ));
    }

    /**
     * GET /admin/api/realtime-stats
     * JSON endpoint — di-poll oleh AJAX di dashboard setiap 10 detik.
     */
    public function realtimeStats()
    {
        $today = Carbon::today();

        $activeLives = LiveSession::where('status', 'live')->count();

        $todayTransactions = Order::whereIn('status', ['checking_admin', 'success', 'processed', 'completed'])
                                   ->whereDate('created_at', $today)
                                   ->count();

        // 5% komisi dari semua transaksi yang sudah sukses/diproses hari ini
        $todayCommission = Order::whereIn('status', ['success', 'processed', 'completed'])
                                 ->whereDate('created_at', $today)
                                 ->sum('platform_fee_amount');

        // Jika platform_fee_amount belum diisi, hitung manual 5%
        if ($todayCommission == 0) {
            $todayRevenue    = Order::whereIn('status', ['success', 'processed', 'completed'])
                                     ->whereDate('created_at', $today)
                                     ->sum('total_price');
            $todayCommission = $todayRevenue * 0.05;
        }

        // Data tambahan untuk widget list di dashboard
        $liveSessions = LiveSession::with('seller:id,name,store_name')
                                   ->where('status', 'live')
                                   ->latest()
                                   ->take(5)
                                   ->get()
                                   ->map(function($ls) {
                                       return [
                                           'title' => $ls->title,
                                           'seller_name' => $ls->seller?->store_name ?? $ls->seller?->name ?? '—',
                                           'viewers' => number_format($ls->current_viewers ?? 0),
                                           'status' => $ls->status,
                                       ];
                                   });

        $withdrawals = Withdrawal::with('seller:id,name,store_name')
                                 ->where('status', 'pending')
                                 ->latest()
                                 ->take(5)
                                 ->get()
                                 ->map(function($wd) {
                                     return [
                                         'id' => $wd->id,
                                         'seller_name' => $wd->seller?->store_name ?? $wd->seller?->name ?? '—',
                                         'bank_info' => $wd->bank_name . ' - ' . $wd->bank_account_number,
                                         'amount_label' => 'Rp ' . number_format($wd->amount, 0, ',', '.'),
                                         'status' => $wd->status,
                                     ];
                                 });

        return response()->json([
            'active_lives'       => $activeLives,
            'today_transactions' => $todayTransactions,
            'platform_commission'=> $todayCommission,
            'platform_commission_label' => 'Rp ' . number_format($todayCommission, 0, ',', '.'),
            'live_sessions'      => $liveSessions,
            'withdrawals'        => $withdrawals,
        ]);
    }

    public function approveTransaction($id)
    {
        $order = Order::findOrFail($id);
        $order->update(['status' => 'success']);

        return redirect()->back()->with('success', 'Pembayaran dikonfirmasi sebagai berhasil.');
    }
}
