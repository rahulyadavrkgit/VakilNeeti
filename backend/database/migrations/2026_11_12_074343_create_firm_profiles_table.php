<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firm_profiles', function (Blueprint $table) {
            $table->id();

            $table->string('firm_name');
            $table->string('proprietor_name')->nullable();

            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('state')->nullable();
            $table->string('state_code', 2)->nullable();
            $table->string('pincode', 10)->nullable();

            $table->string('gstin', 15)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();

            $table->string('logo_path')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firm_profiles');
    }
};