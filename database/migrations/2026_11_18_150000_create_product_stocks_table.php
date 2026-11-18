<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two coordinated stocks under one central inventory (proposal, section 5):
 *   on_hand      real units in the shop (corrected by stock counts)
 *   store_qty    part assigned to counter sales
 *   online_qty   part assigned to the web and WhatsApp
 *   reserved_qty part of online_qty promised to confirmed, undelivered orders
 *   available online = online_qty - reserved_qty
 * Quantities are decimal(10,3) so weighted products can be stored in kg.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('on_hand', 10, 3)->default(0);
            $table->decimal('store_qty', 10, 3)->default(0);
            $table->decimal('online_qty', 10, 3)->default(0);
            $table->decimal('reserved_qty', 10, 3)->default(0);
            $table->decimal('min_alert', 10, 3)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
    }
};
