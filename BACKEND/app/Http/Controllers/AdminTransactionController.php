<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Withdrawal;
use Carbon\Carbon;

class AdminTransactionController extends Controller
{
    // ─── TRANSACTIONS ─────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = Order::with(['buyer:id,name,username', 'seller:id,name,store_name'])
                      ->orderBy('created_at', 'desc');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $transactions = $query->paginate(15)->appends($request->all());

        $totalEscrow   = Order::whereIn('status', ['checking_admin'])->sum('total_price');
        $totalCompleted= Order::where('status', 'completed')->count();

        return view('admin.transactions.index', compact('transactions', 'totalEscrow', 'totalCompleted'));
    }

    /**
     * GET /admin/api/transactions — JSON untuk AJAX polling
     */
    public function realtimeTransactions(Request $request)
    {
        $query = Order::with(['buyer:id,name,username', 'seller:id,name,store_name'])
                             ->orderBy('created_at', 'desc');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $transactions = $query->take(20)
                             ->get()
                             ->map(fn ($o) => [
                                 'id'           => $o->id,
                                 'code'         => 'TRX-' . str_pad($o->id, 5, '0', STR_PAD_LEFT),
                                 'buyer_name'   => $o->buyer?->name ?? '—',
                                 'seller_name'  => $o->seller?->store_name ?? $o->seller?->name ?? '—',
                                 'amount'       => $o->total_price,
                                 'amount_label' => 'Rp ' . number_format($o->total_price, 0, ',', '.'),
                                 'status'       => $o->status,
                                 'created_at'   => Carbon::parse($o->created_at)->diffForHumans(),
                                 'proof_url'    => $o->payment_proof_url,
                             ]);

        return response()->json($transactions);
    }

    /**
     * PATCH /admin/transactions/{id}/approve
     * Admin konfirmasi pembayaran → status menjadi 'success'
     */
    public function approve(int $id)
    {
        $order = Order::findOrFail($id);

        if ($order->status !== 'checking_admin') {
            return back()->withErrors(['approve' => 'Pesanan tidak dalam status menunggu konfirmasi.']);
        }

        $order->update([
            'status'              => 'success',
            'payment_verified_by' => auth()->id(),
            'payment_verified_at' => now(),
        ]);

        // Notifikasi ke Buyer
        $order->buyer?->notify(new \App\Notifications\OrderStatusNotification(
            $order, 
            "Hore! Pembayaran untuk pesanan #" . $order->id . " berhasil dikonfirmasi. Penjual akan segera memproses pesananmu."
        ));

        // Notifikasi ke Seller
        $order->seller?->notify(new \App\Notifications\OrderStatusNotification(
            $order, 
            "Pesanan baru masuk! Pembayaran untuk pesanan #" . $order->id . " telah diverifikasi. Silakan segera proses pengiriman."
        ));

        return back()->with('success', "Pembayaran TRX-{$id} berhasil dikonfirmasi!");
    }

    /**
     * PATCH /admin/transactions/{id}/reject
     * Admin menolak pembayaran palsu/tidak valid → status menjadi 'fail'
     */
    public function reject(int $id)
    {
        $order = Order::findOrFail($id);

        if ($order->status !== 'checking_admin') {
            return back()->withErrors(['reject' => 'Pesanan tidak dalam status menunggu konfirmasi.']);
        }

        $order->update([
            'status'              => 'fail',
            'admin_payment_note'  => 'Bukti transfer ditolak (Palsu/Tidak Valid)',
            'payment_verified_by' => auth()->id(),
            'payment_verified_at' => now(),
        ]);

        // Notifikasi ke Buyer
        $order->buyer?->notify(new \App\Notifications\OrderStatusNotification(
            $order, 
            "Mohon maaf, bukti pembayaran pesanan #" . $order->id . " ditolak karena palsu atau tidak valid."
        ));

        return back()->with('success', "Pembayaran TRX-{$id} berhasil ditolak!");
    }

    // ─── WITHDRAWALS ──────────────────────────────────────────────────────────

    public function withdrawals()
    {
        $withdrawals  = Withdrawal::with('seller:id,name,store_name,bank_name,bank_account,bank_account_name')
                                   ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected', 'completed')")
                                   ->orderBy('created_at', 'desc')
                                   ->paginate(15);

        $pendingTotal = Withdrawal::where('status', 'pending')->sum('amount');

        return view('admin.withdrawals.index', compact('withdrawals', 'pendingTotal'));
    }

    /**
     * GET /admin/api/withdrawals — JSON untuk AJAX polling
     */
    public function realtimeWithdrawals()
    {
        $withdrawals = Withdrawal::with('seller:id,name,store_name,bank_name,bank_account,bank_account_name')
                                  ->orderBy('created_at', 'desc')
                                  ->take(20)
                                  ->get()
                                  ->map(fn ($w) => [
                                      'id'               => $w->id,
                                      'seller_name'      => $w->seller?->store_name ?? $w->seller?->name ?? '—',
                                      'bank_name'        => $w->bank_name,
                                      'bank_account'     => $w->bank_account_number,
                                      'bank_account_name'=> $w->bank_account_name,
                                      'amount'           => $w->amount,
                                      'amount_label'     => 'Rp ' . number_format($w->amount, 0, ',', '.'),
                                      'status'           => $w->status,
                                      'created_at'       => Carbon::parse($w->created_at)->diffForHumans(),
                                  ]);

        return response()->json($withdrawals);
    }

    /**
     * PATCH /admin/withdrawals/{id}/process
     * Admin setujui penarikan dana → status menjadi 'approved'
     */
    public function processWithdrawal(int $id)
    {
        $withdrawal = Withdrawal::findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return back()->withErrors(['process' => 'Pengajuan penarikan ini sudah diproses.']);
        }

        $withdrawal->update([
            'status'       => 'approved',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return back()->with('success', "Penarikan dana dari {$withdrawal->seller?->name} berhasil disetujui!");
    }

    // ─── REFUNDS ──────────────────────────────────────────────────────────────

    public function refunds()
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

    public function processRefund(Request $request, int $id)
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

        return back()->with('success', "Refund untuk TRX-{$id} berhasil diproses!");
    }
}
