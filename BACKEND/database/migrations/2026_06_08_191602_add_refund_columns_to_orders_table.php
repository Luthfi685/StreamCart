<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('refund_bank_name')->nullable()->after('cancel_requested_by');
            $table->string('refund_bank_account')->nullable()->after('refund_bank_name');
            $table->string('refund_bank_account_name')->nullable()->after('refund_bank_account');
            $table->string('refund_proof')->nullable()->after('refund_bank_account_name');
            $table->timestamp('refund_processed_at')->nullable()->after('refund_proof');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'refund_bank_name',
                'refund_bank_account',
                'refund_bank_account_name',
                'refund_proof',
                'refund_processed_at'
            ]);
        });
    }
};
