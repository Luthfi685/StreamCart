<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();

            $table->decimal('amount', 15, 2);
            // Jumlah yang ingin ditarik

            // ─── Snapshot rekening tujuan saat pengajuan ─────────────────────────
            $table->string('bank_name');
            $table->string('bank_account_number');
            $table->string('bank_account_name');

            // ─── Status ───────────────────────────────────────────────────────────
            // pending   = menunggu review Admin
            // approved  = Admin setuju, sedang proses transfer
            // rejected  = Admin tolak
            // completed = Transfer berhasil
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])
                  ->default('pending');

            $table->text('seller_note')->nullable();
            // Catatan dari Seller (opsional)

            $table->text('admin_note')->nullable();
            // Catatan Admin saat approve/reject

            $table->foreignId('processed_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            // Admin yang memproses

            $table->timestamp('processed_at')->nullable();
            // Waktu Admin memproses

            $table->timestamps();

            $table->index(['seller_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
