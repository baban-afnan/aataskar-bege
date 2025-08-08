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
        Schema::create('nin_modifications', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->bigInteger('tnx_id');
            $table->string('refno');
            $table->string('nin_number');
            $table->longtext('photo');
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('surname')->nullable();
            $table->string('description')->nullable();
            $table->date('dob')->nullable();
            $table->string('phone_number')->nullable();
            $table->longText('address')->nullable();
            $table->text('reason')->nullable();
            $table->enum('status', ['resolved', 'pending', 'rejected', 'processing'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nin_modifications');
    }
};
