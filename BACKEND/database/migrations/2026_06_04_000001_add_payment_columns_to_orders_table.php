<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // ─── Update enum status ──────────────────────────────────────────────
            // pending          → order baru dibuat
            // checking_admin   → buyer sudah upload bukti bayar, tunggu Admin
            // success          → Admin konfirmasi pembayaran valid (dana di escrow)
            // fail             → Admin tolak bukti bayar (palsu/salah)
            // processed        → Seller sudah kirim barang
            // completed        → Buyer konfirmasi selesai, dana cair ke Seller
            // cancelled        → Dibatalkan
            $table->enum('status', [
                'pending',
                'checking_admin',
                'success',
                'fail',
                'processed',
                'completed',
                'cancelled',
            ])->default('pending')->change();

            // ─── Snapshot rekening Admin (diisi saat checkout) ───────────────────
            $table->string('payment_bank_name')->nullable()->after('total_price');
            $table->string('payment_bank_account')->nullable()->after('payment_bank_name');
            $table->string('payment_bank_account_name')->nullable()->after('payment_bank_account');

            // ─── Bukti Pembayaran ─────────────────────────────────────────────────
            $table->string('payment_proof')->nullable()->after('payment_bank_account_name');
            $table->timestamp('payment_proof_uploaded_at')->nullable()->after('payment_proof');

            // ─── Verifikasi Admin ─────────────────────────────────────────────────
            $table->text('admin_payment_note')->nullable()->after('payment_proof_uploaded_at');
            $table->foreignId('payment_verified_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->after('admin_payment_note');
            $table->timestamp('payment_verified_at')->nullable()->after('payment_verified_by');

            // ─── Komisi & Escrow (diisi saat completed) ──────────────────────────
            $table->decimal('platform_fee_percent', 5, 2)->default(5.00)->after('payment_verified_at');
            $table->decimal('platform_fee_amount', 15, 2)->nullable()->after('platform_fee_percent');
            $table->decimal('seller_net_amount', 15, 2)->nullable()->after('platform_fee_amount');
            $table->timestamp('completed_at')->nullable()->after('seller_net_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['payment_verified_by']);
            $table->dropColumn([
                'payment_bank_name',
                'payment_bank_account',
                'payment_bank_account_name',
                'payment_proof',
                'payment_proof_uploaded_at',
                'admin_payment_note',
                'payment_verified_by',
                'payment_verified_at',
                'platform_fee_percent',
                'platform_fee_amount',
                'seller_net_amount',
                'completed_at',
            ]);
        });
    }
};
