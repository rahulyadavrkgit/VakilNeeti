<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_profile_id')
                ->unique()
                ->constrained('firm_profiles')
                ->cascadeOnDelete();

            $table->string('full_name', 150);
            $table->string('professional_title', 100)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('alternate_phone', 20)->nullable();

            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('state', 120)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('country', 100)->default('India');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_profiles');
    }
};