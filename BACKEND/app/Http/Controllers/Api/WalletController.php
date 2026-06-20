<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private WalletService $walletService) {}

    /**
     * GET /api/wallet
     * Lihat saldo wallet milik Seller yang sedang login.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'seller') {
            return response()->json(['message' => 'Hanya Seller yang memiliki wallet.'], 403);
        }

        $wallet = Wallet::forUser($user->id);

        return response()->json([
            'wallet' => [
                'balance'          => $wallet->balance,
                'total_earned'     => $wallet->total_earned,
                'total_withdrawn'  => $wallet->total_withdrawn,
                'updated_at'       => $wallet->updated_at,
            ],
        ]);
    }

    /**
     * GET /api/wallet/transactions
     * Riwayat mutasi saldo (debit & kredit).
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'seller') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $wallet = Wallet::forUser($user->id);

        $transactions = $wallet->transactions()
            ->with('order:id,status,total_price')
            ->paginate(20);

        return response()->json($transactions);
    }

    /**
     * GET /api/wallet/withdrawals
     * Riwayat pengajuan penarikan dana milik Seller.
     */
    public function withdrawalHistory(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'seller') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $withdrawals = Withdrawal::forSeller($user->id)
            ->latest()
            ->paginate(20);

        return response()->json($withdrawals);
    }

    /**
     * POST /api/wallet/withdraw
     * Seller mengajukan penarikan dana.
     */
    public function withdraw(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'seller') {
            return response()->json(['message' => 'Hanya Seller yang bisa melakukan penarikan dana.'], 403);
        }

        $minWd = (float) config('wallet.minimum_withdrawal', 50000);

        $request->validate([
            'amount'              => "required|numeric|min:{$minWd}",
            'bank_name'           => 'required|string|max:50',
            'bank_account_number' => 'required|string|max:30',
            'bank_account_name'   => 'required|string|max:100',
            'seller_note'         => 'nullable|string|max:500',
        ]);

        try {
            $withdrawal = $this->walletService->requestWithdrawal(
                sellerId:          $user->id,
                amount:            (float) $request->amount,
                bankName:          $request->bank_name,
                bankAccountNumber: $request->bank_account_number,
                bankAccountName:   $request->bank_account_name,
                sellerNote:        $request->seller_note,
            );
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message'    => 'Pengajuan penarikan dana berhasil! Menunggu persetujuan Admin.',
            'withdrawal' => $withdrawal,
        ], 201);
    }
}
