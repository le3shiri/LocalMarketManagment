<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->nullable()->unique()->comment('Correlative public code, e.g. LM-000231');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 20)->default('web')->index();
            $table->string('status', 20)->default('pending')->index();
            $table->string('fulfillment', 20);
            $table->string('contact_name');
            $table->string('contact_phone', 20);
            $table->string('delivery_address')->nullable();
            $table->date('slot_date');
            $table->string('slot', 11);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_fee', 8, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['slot_date', 'slot']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            // Snapshot of the product at purchase time: later catalog edits never change past orders.
            $table->string('product_name', 120);
            $table->string('sku', 40);
            $table->string('unit_type', 10);
            $table->decimal('unit_price', 8, 2);
            $table->decimal('quantity', 10, 3);
            $table->decimal('line_total', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
