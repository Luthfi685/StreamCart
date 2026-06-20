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
            $table->text('cancel_reason')->nullable()->after('completed_at');
            $table->string('cancel_requested_by')->nullable()->after('cancel_reason');
            // Since status is an enum, modifying it directly via Blueprint across all DBs can be tricky.
            // But since this is a new feature and Laravel >= 10 handles enum modifications natively (requires doctrine/dbal for MySQL < 8 or SQLite), 
            // a safer way for simple strings is just changing to string if it was enum, but we'll assume it's a string column in StreamCart.
            // Let's just add the fields. We will rely on model validation.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['cancel_reason', 'cancel_requested_by']);
        });
    }
};
