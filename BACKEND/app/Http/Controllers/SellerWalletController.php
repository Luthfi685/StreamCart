<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use Carbon\Carbon;

class SellerWalletController extends Controller
{
    public function index(Request $request)
    {
        $user   = $request->user();
        $wallet = Wallet::forUser($user->id);

        $pendingBalance = Withdrawal::where('seller_id', $user->id)
                                    ->where('status', 'pending')
                                    ->sum('amount');

        $transactions = WalletTransaction::where('wallet_id', $wallet->id)
                                         ->latest()
                                         ->take(15)
                                         ->get();

        $withdrawalHistory = Withdrawal::where('seller_id', $user->id)
                                       ->latest()
                                       ->take(10)
                                       ->get();

        return view('seller.wallet.index', compact(
            'user', 'wallet', 'pendingBalance', 'transactions', 'withdrawalHistory'
        ));
    }

    /**
     * GET /seller/api/wallet-stats
     * Real-time saldo polling setiap 5 detik
     */
    public function realtimeStats(Request $request)
    {
        $user   = $request->user();
        $wallet = Wallet::forUser($user->id);

        $pendingBalance = Withdrawal::where('seller_id', $user->id)
                                    ->where('status', 'pending')
                                    ->sum('amount');

        $withdrawals = Withdrawal::where('seller_id', $user->id)
                                 ->latest()
                                 ->take(10)
                                 ->get()
                                 ->map(fn($w) => [
                                     'id'           => $w->id,
                                     'amount_label' => 'Rp ' . number_format($w->amount, 0, ',', '.'),
                                     'bank_name'    => $w->bank_name,
                                     'bank_account' => $w->bank_account_number,
                                     'status'       => $w->status,
                                     'time_ago'     => Carbon::parse($w->created_at)->diffForHumans(),
                                 ]);

        return response()->json([
            'balance'              => $wallet->balance,
            'balance_label'        => 'Rp ' . number_format($wallet->balance, 0, ',', '.'),
            'pending_balance'      => $pendingBalance,
            'pending_balance_label'=> 'Rp ' . number_format($pendingBalance, 0, ',', '.'),
            'withdrawals'          => $withdrawals,
        ]);
    }

    /**
     * POST /seller/wallet/withdraw
     */
    public function withdraw(Request $request)
    {
        $request->validate([
            'amount'             => 'required|numeric|min:50000',
            'bank_name'          => 'required|string|max:100',
            'bank_account_number'=> 'required|string|max:50',
            'bank_account_name'  => 'required|string|max:100',
        ]);

        $user   = $request->user();
        $wallet = Wallet::forUser($user->id);

        if (!$wallet->hasSufficientBalance((float) $request->amount)) {
            return redirect()->back()->withErrors(['amount' => 'Saldo tidak mencukupi untuk penarikan ini.']);
        }

        Withdrawal::create([
            'seller_id'          => $user->id,
            'amount'             => $request->amount,
            'bank_name'          => $request->bank_name,
            'bank_account_number'=> $request->bank_account_number,
            'bank_account_name'  => $request->bank_account_name,
            'seller_note'        => $request->seller_note,
            'status'             => 'pending',
        ]);

        // Freeze saldo (kurangi sementara sampai admin approve)
        $wallet->decrement('balance', $request->amount);
        $wallet->increment('total_withdrawn', $request->amount);

        return redirect()->back()->with('success', 'Pengajuan penarikan dana Rp ' . number_format($request->amount, 0, ',', '.') . ' berhasil dikirim ke Admin!');
    }
}
