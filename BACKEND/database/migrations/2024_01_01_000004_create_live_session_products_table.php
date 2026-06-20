<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_session_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();

            $table->unique(['live_session_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_session_products');
    }
};
