<?php

namespace App\Services;

use App\Mail\PaymentProofMail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Kirim email ke Admin ketika Buyer upload bukti pembayaran.
     */
    public function notifyAdminPaymentProof(Order $order, User $buyer): void
    {
        $adminEmail = config('mail.admin_email', env('ADMIN_EMAIL', 'admin@goingproject.com'));

        try {
            Mail::to($adminEmail)->send(new PaymentProofMail($order, $buyer));
            Log::info("[Notif] Email bukti bayar Order #{$order->id} dikirim ke {$adminEmail}");
        } catch (\Exception $e) {
            Log::error("[Notif] Gagal kirim email Order #{$order->id}: " . $e->getMessage());
        }
    }

    /**
     * Kirim email ke Admin ketika pesanan dibatalkan dan butuh refund.
     */
    public function notifyAdminRefundRequired(Order $order): void
    {
        $adminEmail = config('mail.admin_email', env('ADMIN_EMAIL', 'admin@goingproject.com'));

        try {
            Mail::to($adminEmail)->send(new \App\Mail\RefundRequiredMail($order));
            Log::info("[Notif] Email Refund Required Order #{$order->id} dikirim ke {$adminEmail}");
        } catch (\Exception $e) {
            Log::error("[Notif] Gagal kirim email Refund Required Order #{$order->id}: " . $e->getMessage());
        }
    }
}
