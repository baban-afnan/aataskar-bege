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
        Schema::table('nin_modifications', function (Blueprint $table) {
            $table->longText('origin_address')->nullable();
            $table->longText('full_address')->nullable();
            $table->longText('document')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nin_modifications', function (Blueprint $table) {
            $table->dropColumn(['origin_address', 'full_address', 'document']);
        });
    }
};
