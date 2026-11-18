<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only ledger of every stock change: who, what, how much, on which channel and why.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->index();
            $table->decimal('store_delta', 10, 3)->default(0);
            $table->decimal('online_delta', 10, 3)->default(0);
            $table->decimal('reserved_delta', 10, 3)->default(0);
            $table->decimal('on_hand_after', 10, 3);
            $table->string('reference', 60)->nullable();
            $table->string('reason')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
