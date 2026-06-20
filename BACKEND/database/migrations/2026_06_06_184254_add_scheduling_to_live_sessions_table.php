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
        // 1. Temporarily expand ENUM to include both old and new
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE live_sessions MODIFY COLUMN status ENUM('draft', 'scheduled', 'live', 'ended', 'finished') NOT NULL DEFAULT 'live'");
        
        // 2. Migrate existing 'ended' data to 'finished'
        \Illuminate\Support\Facades\DB::statement("UPDATE live_sessions SET status = 'finished' WHERE status = 'ended'");

        // 3. Finalize ENUM without 'ended'
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE live_sessions MODIFY COLUMN status ENUM('draft', 'scheduled', 'live', 'finished') NOT NULL DEFAULT 'live'");
        
        if (!Schema::hasColumn('live_sessions', 'scheduled_at')) {
            Schema::table('live_sessions', function (Blueprint $table) {
                $table->dateTime('scheduled_at')->nullable()->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('live_sessions', function (Blueprint $table) {
            $table->dropColumn(['status', 'scheduled_at']);
        });
    }
};
