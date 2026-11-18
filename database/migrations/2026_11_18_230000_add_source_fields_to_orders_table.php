<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a WhatsApp order came from: a reference to the conversation and the original
 * message, so staff can check it later (and so the phase-2 AI assistant can fill them).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('source_reference', 100)->nullable()->after('channel');
            $table->text('source_message')->nullable()->after('source_reference');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['source_reference', 'source_message']);
        });
    }
};
