<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Real quantity handed to the customer. For weighted products it is the weight on the
 * scale, which can differ from the approximate quantity that was ordered and reserved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('delivered_quantity', 10, 3)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('delivered_quantity');
        });
    }
};
