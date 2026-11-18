<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batches (lotes) of received goods with their expiry date. quantity_remaining goes down
 * as units leave the shop, first-expiring batch first (FEFO).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('lot_code', 40);
            $table->date('expires_at')->nullable();
            $table->decimal('quantity_received', 10, 3);
            $table->decimal('quantity_remaining', 10, 3);
            $table->string('status', 12)->default('active')->index();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference', 60)->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
