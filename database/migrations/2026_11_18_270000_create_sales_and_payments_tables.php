<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Counter sale = one ticket (factura simplificada).
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->nullable()->unique()->comment('Correlative ticket number, e.g. T-000042');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('Cashier');
            $table->decimal('total', 10, 2);
            $table->json('vat_breakdown');
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name', 120);
            $table->string('sku', 40);
            $table->string('unit_type', 10);
            $table->decimal('unit_price', 8, 2);
            $table->unsignedTinyInteger('vat_rate');
            $table->decimal('quantity', 10, 3);
            $table->decimal('line_total', 10, 2);
        });

        // Money received, for counter sales and for orders paid on pickup/delivery.
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->morphs('payable');
            $table->string('method', 10)->index();
            $table->decimal('amount', 10, 2);
            $table->decimal('tendered', 10, 2)->nullable();
            $table->decimal('change', 10, 2)->nullable();
            $table->string('reference', 20)->nullable()->comment('Card terminal authorisation code; never card data');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
