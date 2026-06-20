<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE products MODIFY image_url LONGTEXT');
        DB::statement('ALTER TABLE live_sessions MODIFY thumbnail LONGTEXT');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE products MODIFY image_url VARCHAR(255)');
        DB::statement('ALTER TABLE live_sessions MODIFY thumbnail VARCHAR(255)');
    }
};
