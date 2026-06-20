<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            // unique: 1 user = 1 wallet

            $table->decimal('balance', 15, 2)->default(0.00);
            // Saldo aktif yang bisa di-withdraw

            $table->decimal('total_earned', 15, 2)->default(0.00);
            // Total pendapatan sepanjang masa (historical, tidak berkurang saat WD)

            $table->decimal('total_withdrawn', 15, 2)->default(0.00);
            // Total yang sudah pernah ditarik

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
