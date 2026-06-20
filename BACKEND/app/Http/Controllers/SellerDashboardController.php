<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\LiveSession;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Carbon\Carbon;

class SellerDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user   = $request->user();
        $wallet = Wallet::forUser($user->id);

        // Real data
        $totalProducts = Product::where('seller_id', $user->id)->count();
        $products      = Product::where('seller_id', $user->id)->latest()->take(10)->get();
        $activeLives   = LiveSession::where('seller_id', $user->id)->where('status', 'live')->count();

        // Revenue = seller_net_amount dari order success/completed
        $totalRevenue  = Order::where('seller_id', $user->id)
                              ->whereIn('status', ['success', 'completed'])
                              ->sum('seller_net_amount');

        $totalProductsSold = OrderItem::whereHas('order', function($q) use($user) {
                                  $q->where('seller_id', $user->id)
                                    ->whereIn('status', ['success', 'completed', 'processed']);
                              })->sum('quantity');

        $totalOrdersIn = Order::where('seller_id', $user->id)
                              ->whereIn('status', ['checking_admin', 'success', 'processed', 'completed'])
                              ->count();

        $recentOrders = Order::where('seller_id', $user->id)
                             ->with('buyer:id,name,username')
                             ->latest()
                             ->take(5)
                             ->get();

        // Weekly chart data (last 7 days, count orders per day)
        $chartLabels = [];
        $chartOrders = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $chartLabels[] = $day->format('D');
            $chartOrders[] = Order::where('seller_id', $user->id)
                                  ->whereDate('created_at', $day)
                                  ->count();
        }

        $chartData = [
            'labels' => $chartLabels,
            'orders' => $chartOrders,
        ];

        return view('seller.dashboard', compact(
            'user', 'totalProducts', 'products', 'activeLives',
            'totalRevenue', 'totalProductsSold', 'totalOrdersIn',
            'recentOrders', 'chartData', 'wallet'
        ));
    }

    /**
     * GET /seller/api/dashboard-stats
     * Real-time JSON — polling setiap 5 detik dari dashboard
     */
    public function realtimeStats(Request $request)
    {
        $user   = $request->user();
        $wallet = Wallet::forUser($user->id);

        $totalRevenue = Order::where('seller_id', $user->id)
                             ->whereIn('status', ['success', 'completed'])
                             ->sum('seller_net_amount');

        $totalProductsSold = OrderItem::whereHas('order', function($q) use($user) {
                                  $q->where('seller_id', $user->id)
                                    ->whereIn('status', ['success', 'completed', 'processed']);
                              })->sum('quantity');

        $totalOrdersIn = Order::where('seller_id', $user->id)
                              ->whereIn('status', ['checking_admin', 'success', 'processed', 'completed'])
                              ->count();

        $activeLives = LiveSession::where('seller_id', $user->id)
                                  ->where('status', 'live')
                                  ->count();

        // Grafik 7 hari
        $chartOrders = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $chartOrders[] = Order::where('seller_id', $user->id)
                                  ->whereDate('created_at', $day)
                                  ->count();
        }

        // 5 pesanan terbaru
        $recentOrders = Order::where('seller_id', $user->id)
                             ->with('buyer:id,name,username')
                             ->latest()
                             ->take(5)
                             ->get()
                             ->map(fn($o) => [
                                 'id'          => $o->id,
                                 'code'        => 'ORD-' . str_pad($o->id, 3, '0', STR_PAD_LEFT),
                                 'buyer_name'  => $o->buyer?->name ?? '—',
                                 'total_price' => $o->total_price,
                                 'amount_label'=> 'Rp ' . number_format($o->total_price, 0, ',', '.'),
                                 'status'      => $o->status,
                                 'time_ago'    => Carbon::parse($o->created_at)->diffForHumans(),
                             ]);

        return response()->json([
            'total_revenue'       => $totalRevenue,
            'total_revenue_label' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
            'total_products_sold' => (int) $totalProductsSold,
            'total_orders_in'     => $totalOrdersIn,
            'active_lives'        => $activeLives,
            'wallet_balance'      => $wallet->balance,
            'wallet_balance_label'=> 'Rp ' . number_format($wallet->balance, 0, ',', '.'),
            'chart_orders'        => $chartOrders,
            'recent_orders'       => $recentOrders,
        ]);
    }
}
