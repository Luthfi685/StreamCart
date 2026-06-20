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
            $table->string('shipping_courier')->nullable()->after('shipping_address');
            $table->string('shipping_tracking_number')->nullable()->after('shipping_courier');

            $table->enum('status', [
                'pending',
                'checking_admin',
                'success',
                'fail',
                'processed',
                'shipped',
                'completed',
                'cancelled',
            ])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_courier', 'shipping_tracking_number']);
            // Revert enum back by removing 'shipped'
            $table->enum('status', [
                'pending',
                'checking_admin',
                'success',
                'fail',
                'processed',
                'completed',
                'cancelled',
            ])->default('pending')->change();
        });
    }
};
