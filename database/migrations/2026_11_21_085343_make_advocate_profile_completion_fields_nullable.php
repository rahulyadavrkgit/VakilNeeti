<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advocate_profiles', function (Blueprint $table): void {
            $table->string('gender', 20)->nullable()->change();
            $table->unsignedTinyInteger('years_experience')->nullable()->change();
            $table->unsignedSmallInteger('graduation_year')->nullable()->change();
            $table->json('languages_spoken')->nullable()->change();
            $table->json('specializations')->nullable()->change();
            $table->string('city', 100)->nullable()->change();
            $table->string('region', 120)->nullable()->change();
            $table->string('pincode', 10)->nullable()->change();
            $table->string('court', 200)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Intentionally retain nullable columns.
        // Advocate profiles may legitimately be completed after registration.
        // Tightening these constraints could break existing accounts.
    }
};
