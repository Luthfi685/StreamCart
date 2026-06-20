<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Lepaskan escrow ke Seller setelah order selesai (completed).
     * Dipanggil saat Buyer konfirmasi terima barang.
     *
     * Rumus:
     *   platform_fee_amount = total_price × (fee_percent / 100)
     *   seller_net_amount   = total_price - platform_fee_amount
     */
    public function releaseEscrowToSeller(Order $order): Order
    {
        if (!in_array($order->status, ['processed', 'shipped'])) {
            throw new \Exception('Order harus dalam status "processed" atau "shipped" sebelum bisa diselesaikan.');
        }

        DB::transaction(function () use ($order) {
            // 1. Hitung komisi
            $feePercent      = (float) config('wallet.platform_fee_percent', 5.0);
            $feeAmount       = round($order->total_price * ($feePercent / 100), 2);
            $sellerNetAmount = round($order->total_price - $feeAmount, 2);

            // 2. Update order → completed + simpan data komisi
            $order->update([
                'status'               => 'completed',
                'platform_fee_percent' => $feePercent,
                'platform_fee_amount'  => $feeAmount,
                'seller_net_amount'    => $sellerNetAmount,
                'completed_at'         => now(),
            ]);

            // 3. Kredit ke Seller Wallet
            $wallet        = Wallet::forUser($order->seller_id);
            $balanceBefore = (float) $wallet->balance;

            $wallet->increment('balance', $sellerNetAmount);
            $wallet->increment('total_earned', $sellerNetAmount);

            // 4. Catat mutasi wallet (audit trail)
            WalletTransaction::create([
                'wallet_id'      => $wallet->id,
                'order_id'       => $order->id,
                'type'           => 'credit',
                'amount'         => $sellerNetAmount,
                'balance_before' => $balanceBefore,
                'balance_after'  => round($balanceBefore + $sellerNetAmount, 2),
                'description'    => "Pendapatan Order #{$order->id} "
                                    . "(komisi {$feePercent}% = Rp "
                                    . number_format($feeAmount, 0, ',', '.') . " dipotong)",
                'reference_type' => 'order',
                'reference_id'   => $order->id,
            ]);
        });

        return $order->fresh();
    }

    /**
     * Seller mengajukan penarikan dana.
     * Saldo langsung dikurangi dan withdrawal record dibuat dengan status pending.
     */
    public function requestWithdrawal(
        int    $sellerId,
        float  $amount,
        string $bankName,
        string $bankAccountNumber,
        string $bankAccountName,
        ?string $sellerNote = null
    ): Withdrawal {
        $wallet = Wallet::forUser($sellerId);

        if (!$wallet->hasSufficientBalance($amount)) {
            throw new \Exception('Saldo tidak mencukupi.');
        }

        $minWd = (float) config('wallet.minimum_withdrawal', 50000);
        if ($amount < $minWd) {
            throw new \Exception("Minimal penarikan adalah Rp " . number_format($minWd, 0, ',', '.'));
        }

        $withdrawal = null;

        DB::transaction(function () use (
            $wallet, $sellerId, $amount,
            $bankName, $bankAccountNumber, $bankAccountName,
            $sellerNote, &$withdrawal
        ) {
            $balanceBefore = (float) $wallet->balance;

            // 1. Kurangi saldo (ditahan sampai Admin approve/reject)
            $wallet->decrement('balance', $amount);

            // 2. Buat record withdrawal
            $withdrawal = Withdrawal::create([
                'seller_id'           => $sellerId,
                'amount'              => $amount,
                'bank_name'           => $bankName,
                'bank_account_number' => $bankAccountNumber,
                'bank_account_name'   => $bankAccountName,
                'seller_note'         => $sellerNote,
                'status'              => 'pending',
            ]);

            // 3. Catat mutasi debit
            WalletTransaction::create([
                'wallet_id'      => $wallet->id,
                'type'           => 'debit',
                'amount'         => $amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => round($balanceBefore - $amount, 2),
                'description'    => "Pengajuan Penarikan Dana #" . $withdrawal->id,
                'reference_type' => 'withdrawal',
                'reference_id'   => $withdrawal->id,
            ]);
        });

        return $withdrawal;
    }

    /**
     * Admin approve withdrawal → completed.
     * Admin reject withdrawal  → saldo dikembalikan ke Seller.
     */
    public function processWithdrawal(
        Withdrawal $withdrawal,
        string     $action,        // 'approve' atau 'reject'
        int        $adminId,
        ?string    $adminNote = null
    ): Withdrawal {
        if (!$withdrawal->isPending()) {
            throw new \Exception('Withdrawal sudah diproses sebelumnya.');
        }

        DB::transaction(function () use ($withdrawal, $action, $adminId, $adminNote) {
            if ($action === 'approve') {
                $withdrawal->update([
                    'status'       => 'completed',
                    'admin_note'   => $adminNote,
                    'processed_by' => $adminId,
                    'processed_at' => now(),
                ]);

                // Catat total withdrawn
                $wallet = Wallet::forUser($withdrawal->seller_id);
                $wallet->increment('total_withdrawn', $withdrawal->amount);

            } elseif ($action === 'reject') {
                // Kembalikan saldo ke Seller
                $wallet        = Wallet::forUser($withdrawal->seller_id);
                $balanceBefore = (float) $wallet->balance;

                $wallet->increment('balance', $withdrawal->amount);

                // Catat kredit balik
                WalletTransaction::create([
                    'wallet_id'      => $wallet->id,
                    'type'           => 'credit',
                    'amount'         => $withdrawal->amount,
                    'balance_before' => $balanceBefore,
                    'balance_after'  => round($balanceBefore + $withdrawal->amount, 2),
                    'description'    => "Pengembalian Saldo — Penarikan #" . $withdrawal->id . " ditolak",
                    'reference_type' => 'withdrawal',
                    'reference_id'   => $withdrawal->id,
                ]);

                $withdrawal->update([
                    'status'       => 'rejected',
                    'admin_note'   => $adminNote,
                    'processed_by' => $adminId,
                    'processed_at' => now(),
                ]);
            }
        });

        return $withdrawal->fresh();
    }
}
