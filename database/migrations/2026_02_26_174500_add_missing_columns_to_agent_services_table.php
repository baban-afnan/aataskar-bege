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
        Schema::table('agent_services', function (Blueprint $table) {
            if (!Schema::hasColumn('agent_services', 'modification_data')) {
                $table->json('modification_data')->nullable()->after('description');
            }
            if (!Schema::hasColumn('agent_services', 'company_type')) {
                $table->string('company_type')->nullable()->after('modification_data');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_services', function (Blueprint $table) {
            $table->dropColumn(['modification_data', 'company_type']);
        });
    }
};
