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
        // Block 1: Renames
        Schema::table('verifications', function (Blueprint $table) {
            if (Schema::hasColumn('verifications', 'first_name')) {
                $table->renameColumn('first_name', 'firstname');
            }
            if (Schema::hasColumn('verifications', 'middle_name')) {
                $table->renameColumn('middle_name', 'middlename');
            }
            if (Schema::hasColumn('verifications', 'last_name')) {
                $table->renameColumn('last_name', 'surname');
            }
            if (Schema::hasColumn('verifications', 'phoneno')) {
                $table->renameColumn('phoneno', 'telephoneno');
            }
            if (Schema::hasColumn('verifications', 'photo')) {
                $table->renameColumn('photo', 'photo_path');
            }
            if (Schema::hasColumn('verifications', 'signature')) {
                $table->renameColumn('signature', 'signature_path');
            }
            if (Schema::hasColumn('verifications', 'dob')) {
                $table->renameColumn('dob', 'birthdate');
            }
        });

        // Block 1.5: Make renamed/existing columns nullable
        Schema::table('verifications', function (Blueprint $table) {
            $columnsToMakeNullable = [
                'firstname', 'middlename', 'surname', 'telephoneno', 
                'photo_path', 'signature_path', 'birthdate'
            ];
            foreach ($columnsToMakeNullable as $col) {
                if (Schema::hasColumn('verifications', $col)) {
                    $table->string($col)->nullable()->change();
                }
            }
        });

        // Block 2: Additions and Modifications
        Schema::table('verifications', function (Blueprint $table) {
            // Modify birthdate type if it was renamed/exists
            if (Schema::hasColumn('verifications', 'birthdate')) {
                $table->string('birthdate')->nullable()->change();
            }

            // Add missing relationship/reference columns if they don't exist
            if (!Schema::hasColumn('verifications', 'reference')) {
                $table->string('reference')->after('id')->unique()->nullable();
            }
            if (!Schema::hasColumn('verifications', 'user_id')) {
                $table->foreignId('user_id')->after('reference')->nullable()->constrained();
            }
            if (!Schema::hasColumn('verifications', 'service_field_id')) {
                $table->foreignId('service_field_id')->after('user_id')->nullable()->constrained('service_fields');
            }
            if (!Schema::hasColumn('verifications', 'service_id')) {
                $table->foreignId('service_id')->after('service_field_id')->nullable()->constrained('services1');
            }
             if (!Schema::hasColumn('verifications', 'transaction_id')) {
                $table->foreignId('transaction_id')->after('service_id')->nullable()->constrained();
            }

            // Add all other missing fields from the requested schema
            if (!Schema::hasColumn('verifications', 'field_code')) {
                $table->string('field_code')->nullable()->after('service_id');
            }
            if (!Schema::hasColumn('verifications', 'field_name')) {
                $table->string('field_name')->nullable()->after('field_code');
            }
            if (!Schema::hasColumn('verifications', 'service_name')) {
                $table->string('service_name')->nullable()->after('field_name');
            }
            if (!Schema::hasColumn('verifications', 'service_type')) {
                $table->string('service_type')->nullable()->after('service_name');
            }
            if (!Schema::hasColumn('verifications', 'description')) {
                $table->string('description')->nullable()->after('service_type');
            }
            if (!Schema::hasColumn('verifications', 'amount')) {
                $table->decimal('amount', 12, 2)->default(0.00)->nullable()->after('description');
            }

            if (!Schema::hasColumn('verifications', 'birthstate')) {
                $table->string('birthstate')->nullable()->after('birthdate');
            }
            if (!Schema::hasColumn('verifications', 'birthlga')) {
                $table->string('birthlga')->nullable()->after('birthstate');
            }
            if (!Schema::hasColumn('verifications', 'birthcountry')) {
                $table->string('birthcountry')->nullable()->after('birthlga');
            }
            if (!Schema::hasColumn('verifications', 'maritalstatus')) {
                $table->string('maritalstatus')->nullable()->after('birthcountry');
            }
            
            if (!Schema::hasColumn('verifications', 'residence_address')) {
                $table->string('residence_address')->nullable()->after('telephoneno');
            }
            
            if (!Schema::hasColumn('verifications', 'religion')) {
                $table->string('religion')->nullable()->after('residence_town');
            }
            if (!Schema::hasColumn('verifications', 'employmentstatus')) {
                $table->string('employmentstatus')->nullable()->after('religion');
            }
            if (!Schema::hasColumn('verifications', 'educationallevel')) {
                $table->string('educationallevel')->nullable()->after('employmentstatus');
            }
            if (!Schema::hasColumn('verifications', 'profession')) {
                $table->string('profession')->nullable()->after('educationallevel');
            }
            if (!Schema::hasColumn('verifications', 'height')) {
                $table->string('height')->nullable()->after('profession');
            }
            
            if (!Schema::hasColumn('verifications', 'number_nin')) {
                $table->string('number_nin')->nullable()->after('nin');
            }
            if (!Schema::hasColumn('verifications', 'vnin')) {
                $table->string('vnin')->nullable()->after('idno');
            }
            
            if (!Schema::hasColumn('verifications', 'userid')) {
                $table->string('userid')->nullable()->after('trackingId');
            }
            if (!Schema::hasColumn('verifications', 'performed_by')) {
                $table->string('performed_by', 150)->nullable()->after('userid');
            }
            if (!Schema::hasColumn('verifications', 'approved_by')) {
                $table->string('approved_by', 150)->nullable()->after('performed_by');
            }
            if (!Schema::hasColumn('verifications', 'tax_id')) {
                $table->string('tax_id')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('verifications', 'comment')) {
                $table->text('comment')->nullable()->after('tax_id');
            }
            if (!Schema::hasColumn('verifications', 'response_data')) {
                $table->json('response_data')->nullable()->after('comment');
            }
            if (!Schema::hasColumn('verifications', 'modification_data')) {
                $table->json('modification_data')->nullable()->after('response_data');
            }
            
            // Next of Kin fields
            $table->string('nok_firstname')->nullable();
            $table->string('nok_middlename')->nullable();
            $table->string('nok_surname')->nullable();
            $table->string('nok_address1')->nullable();
            $table->string('nok_address2')->nullable();
            $table->string('nok_lga')->nullable();
            $table->string('nok_state')->nullable();
            $table->string('nok_town')->nullable();
            $table->string('nok_postalcode')->nullable();

            // Self Origin fields
            $table->string('self_origin_state')->nullable();
            $table->string('self_origin_lga')->nullable();
            $table->string('self_origin_place')->nullable();

            if (!Schema::hasColumn('verifications', 'status')) {
                $table->string('status')->default('pending')->nullable()->after('transaction_id');
            }
            if (!Schema::hasColumn('verifications', 'submission_date')) {
                $table->timestamp('submission_date')->useCurrent()->nullable()->after('status');
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
