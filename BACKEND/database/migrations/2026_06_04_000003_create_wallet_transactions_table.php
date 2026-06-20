<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();

            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            // null jika referensinya adalah withdrawal, bukan order

            $table->enum('type', ['credit', 'debit']);
            // credit = uang masuk ke wallet
            // debit  = uang keluar dari wallet (withdraw)

            $table->decimal('amount', 15, 2);
            // Nominal transaksi

            $table->decimal('balance_before', 15, 2);
            // Saldo sebelum transaksi (untuk audit trail)

            $table->decimal('balance_after', 15, 2);
            // Saldo setelah transaksi (untuk audit trail)

            $table->string('description');
            // Contoh: "Pendapatan Order #12", "Withdrawal #3"

            $table->enum('reference_type', ['order', 'withdrawal', 'refund', 'adjustment'])
                  ->default('order');

            $table->unsignedBigInteger('reference_id')->nullable();
            // ID order atau ID withdrawal yang terkait

            $table->timestamps();

            $table->index(['wallet_id', 'type']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
