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
        Schema::create('advocate_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('gender', 20);
            $table->string('photo_path');
            $table->unsignedTinyInteger('years_experience');
            $table->unsignedSmallInteger('graduation_year');
            $table->json('languages_spoken');
            $table->json('specializations');
            $table->text('practice_areas')->nullable();
            $table->string('bar_council_registration_number', 100);
            $table->string('city', 100);
            $table->string('region', 120);
            $table->string('pincode', 10);
            $table->string('court', 200);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advocate_profiles');
    }
};
