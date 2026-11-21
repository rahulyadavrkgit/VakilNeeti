<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('police_sub_divisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('states')->cascadeOnDelete();
            $table->foreignId('police_district_id')->nullable()->constrained('police_districts')->cascadeOnDelete();
            $table->string('sub_division_name');
            $table->string('sub_division_code')->nullable();
            $table->string('official_url')->nullable();
            $table->string('source_name')->default('Official State Police Portal');
            $table->string('source_url')->nullable();
            $table->string('source_status')->default('pending_verification');
            $table->timestamp('last_verified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('police_sub_divisions');
    }
};