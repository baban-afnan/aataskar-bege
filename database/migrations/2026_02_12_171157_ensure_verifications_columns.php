<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('verifications', function (Blueprint $table) {
            // Foreign Keys
            if (!Schema::hasColumn('verifications', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained();
            }
            if (!Schema::hasColumn('verifications', 'service_field_id')) {
                $table->foreignId('service_field_id')->nullable()->constrained('service_fields');
            }
            if (!Schema::hasColumn('verifications', 'service_id')) {
                $table->foreignId('service_id')->nullable()->constrained('services1');
            }
            if (!Schema::hasColumn('verifications', 'transaction_id')) {
                $table->foreignId('transaction_id')->nullable()->constrained();
            }

            // Identification & Reference
            if (!Schema::hasColumn('verifications', 'reference')) $table->string('reference')->unique()->nullable();
            if (!Schema::hasColumn('verifications', 'field_code')) $table->string('field_code')->nullable();
            if (!Schema::hasColumn('verifications', 'field_name')) $table->string('field_name')->nullable();
            if (!Schema::hasColumn('verifications', 'service_name')) $table->string('service_name')->nullable();
            if (!Schema::hasColumn('verifications', 'service_type')) $table->string('service_type')->nullable();
            if (!Schema::hasColumn('verifications', 'description')) $table->string('description')->nullable();
            if (!Schema::hasColumn('verifications', 'amount')) $table->decimal('amount', 12, 2)->default(0.00)->nullable();
            
            // Personal Information
            if (!Schema::hasColumn('verifications', 'firstname')) $table->string('firstname')->nullable();
            if (!Schema::hasColumn('verifications', 'middlename')) $table->string('middlename')->nullable();
            if (!Schema::hasColumn('verifications', 'surname')) $table->string('surname')->nullable();
            if (!Schema::hasColumn('verifications', 'gender')) $table->string('gender')->nullable();
            if (!Schema::hasColumn('verifications', 'birthdate')) $table->string('birthdate')->nullable();
            if (!Schema::hasColumn('verifications', 'birthstate')) $table->string('birthstate')->nullable();
            if (!Schema::hasColumn('verifications', 'birthlga')) $table->string('birthlga')->nullable();
            if (!Schema::hasColumn('verifications', 'birthcountry')) $table->string('birthcountry')->nullable();
            if (!Schema::hasColumn('verifications', 'maritalstatus')) $table->string('maritalstatus')->nullable();
            if (!Schema::hasColumn('verifications', 'email')) $table->string('email')->nullable();
            if (!Schema::hasColumn('verifications', 'type')) $table->string('type')->nullable();
            if (!Schema::hasColumn('verifications', 'telephoneno')) $table->string('telephoneno')->nullable();

            // Residence
            if (!Schema::hasColumn('verifications', 'residence_address')) $table->string('residence_address')->nullable();
            if (!Schema::hasColumn('verifications', 'residence_state')) $table->string('residence_state')->nullable();
            if (!Schema::hasColumn('verifications', 'residence_lga')) $table->string('residence_lga')->nullable();
            if (!Schema::hasColumn('verifications', 'residence_town')) $table->string('residence_town')->nullable();

            // Additional Personal Details
            if (!Schema::hasColumn('verifications', 'religion')) $table->string('religion')->nullable();
            if (!Schema::hasColumn('verifications', 'employmentstatus')) $table->string('employmentstatus')->nullable();
            if (!Schema::hasColumn('verifications', 'educationallevel')) $table->string('educationallevel')->nullable();
            if (!Schema::hasColumn('verifications', 'profession')) $table->string('profession')->nullable();
            if (!Schema::hasColumn('verifications', 'height')) $table->string('height')->nullable();
            if (!Schema::hasColumn('verifications', 'title')) $table->string('title')->nullable();

            // IDs and Verification Data
            if (!Schema::hasColumn('verifications', 'nin')) $table->string('nin')->nullable();
            if (!Schema::hasColumn('verifications', 'number_nin')) $table->string('number_nin')->nullable();
            if (!Schema::hasColumn('verifications', 'idno')) $table->string('idno')->nullable();
            if (!Schema::hasColumn('verifications', 'vnin')) $table->string('vnin')->nullable();
            if (!Schema::hasColumn('verifications', 'tax_id')) $table->string('tax_id')->nullable();
            
            // Files
            if (!Schema::hasColumn('verifications', 'photo_path')) $table->longText('photo_path')->nullable();
            if (!Schema::hasColumn('verifications', 'signature_path')) $table->longText('signature_path')->nullable();
            
            // Metadata & Processing
            if (!Schema::hasColumn('verifications', 'trackingId')) $table->string('trackingId')->nullable();
            if (!Schema::hasColumn('verifications', 'userid')) $table->string('userid')->nullable();
            if (!Schema::hasColumn('verifications', 'performed_by')) $table->string('performed_by', 150)->nullable();
            if (!Schema::hasColumn('verifications', 'approved_by')) $table->string('approved_by', 150)->nullable();
            if (!Schema::hasColumn('verifications', 'comment')) $table->text('comment')->nullable();
            if (!Schema::hasColumn('verifications', 'response_data')) $table->json('response_data')->nullable();
            if (!Schema::hasColumn('verifications', 'modification_data')) $table->json('modification_data')->nullable();
            
            // Next of Kin
            if (!Schema::hasColumn('verifications', 'nok_firstname')) $table->string('nok_firstname')->nullable();
            if (!Schema::hasColumn('verifications', 'nok_middlename')) $table->string('nok_middlename')->nullable();
            if (!Schema::hasColumn('verifications', 'nok_surname')) $table->string('nok_surname')->nullable();
            if (!Schema::hasColumn('verifications', 'nok_address1')) $table->string('nok_address1')->nullable();
            if (!Schema::hasColumn('verifications', 'nok_address2')) $table->string('nok_address2')->nullable();
            if (!Schema::hasColumn('verifications', 'nok_lga')) $table->string('nok_lga')->nullable();
            if (!Schema::hasColumn('verifications', 'nok_state')) $table->string('nok_state')->nullable();
            if (!Schema::hasColumn('verifications', 'nok_town')) $table->string('nok_town')->nullable();
            if (!Schema::hasColumn('verifications', 'nok_postalcode')) $table->string('nok_postalcode')->nullable();

            // Origin
            if (!Schema::hasColumn('verifications', 'self_origin_state')) $table->string('self_origin_state')->nullable();
            if (!Schema::hasColumn('verifications', 'self_origin_lga')) $table->string('self_origin_lga')->nullable();
            if (!Schema::hasColumn('verifications', 'self_origin_place')) $table->string('self_origin_place')->nullable();
            
            // System Status
            if (!Schema::hasColumn('verifications', 'status')) $table->string('status')->default('pending')->nullable();
            if (!Schema::hasColumn('verifications', 'submission_date')) $table->timestamp('submission_date')->useCurrent()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('verifications', function (Blueprint $table) {
            //
        });
    }
};
