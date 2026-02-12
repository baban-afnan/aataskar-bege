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
        Schema::table('verifications', function (Blueprint $table) {
            // Modify verified string columns to be nullable
            $columns = [
                'photo_path',
                'signature_path',
                'telephoneno',
                'firstname',
                'middlename',
                'surname',
                'birthdate',
                'gender',
                'birthstate',
                'birthlga',
                'birthcountry',
                'maritalstatus',
                'residence_address',
                'residence_town',
                'religion',
                'employmentstatus',
                'educationallevel',
                'profession',
                'height',
                'title',
                'nin',
                'number_nin',
                'idno',
                'vnin',
                'trackingId',
                'userid',
                'performed_by',
                'approved_by',
                'tax_id',
                'nok_firstname',
                'nok_middlename',
                'nok_surname',
                'nok_address1',
                'nok_address2',
                'nok_lga',
                'nok_state',
                'nok_town',
                'nok_postalcode',
                'self_origin_state',
                'self_origin_lga',
                'self_origin_place'
            ];

            // Handle potentially long text columns first
            if (Schema::hasColumn('verifications', 'photo_path')) {
                $table->longText('photo_path')->nullable()->change();
            }
            if (Schema::hasColumn('verifications', 'signature_path')) {
                $table->longText('signature_path')->nullable()->change();
            }

            foreach ($columns as $column) {
                // Skip photo_path and signature_path as they are handled above
                if (in_array($column, ['photo_path', 'signature_path'])) {
                    continue;
                }

                if (Schema::hasColumn('verifications', $column)) {
                    $table->string($column)->nullable()->change();
                }
            }
            
            // Text columns
            if (Schema::hasColumn('verifications', 'comment')) {
                $table->text('comment')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down implementation needed for making columns nullable
    }
};
