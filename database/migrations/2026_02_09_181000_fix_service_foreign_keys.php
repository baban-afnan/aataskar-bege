<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix service_fields table if not already pointing to services1
        Schema::table('service_fields', function (Blueprint $table) {
            // Check current constraint
            $createTable = DB::select("SHOW CREATE TABLE service_fields")[0]->{'Create Table'};
            
            if (strpos($createTable, 'REFERENCES `services`') !== false) {
                if (strpos($createTable, 'CONSTRAINT `service_fields_service_id_foreign`') !== false) {
                    $table->dropForeign('service_fields_service_id_foreign');
                }
                $table->foreign('service_id')->references('id')->on('services1')->onDelete('cascade');
            }
        });

        // Fix service_prices table
        Schema::table('service_prices', function (Blueprint $table) {
            $createTable = DB::select("SHOW CREATE TABLE service_prices")[0]->{'Create Table'};

            if (strpos($createTable, 'REFERENCES `services`') !== false) {
                 if (strpos($createTable, 'CONSTRAINT `service_prices_service_id_foreign`') !== false) {
                    $table->dropForeign('service_prices_service_id_foreign');
                }
                // Use a NEW name to avoid any potential stale lock on the old one
                $table->foreign('service_id', 'service_prices_service_id_new_fk')->references('id')->on('services1')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
