<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('police_districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('states')->restrictOnDelete();
            $table->string('district_name');
            $table->string('district_code')->nullable();
            $table->string('range_name')->nullable();
            $table->text('official_url')->nullable();
            $table->string('source_name')->nullable();
            $table->text('source_url')->nullable();
            $table->string('source_status')->default('pending_verification');
            $table->timestamp('last_verified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['state_id', 'district_name']);
            $table->index(['source_status', 'is_active']);
        });

        Schema::create('police_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('states')->restrictOnDelete();
            $table->foreignId('police_district_id')->constrained('police_districts')->cascadeOnDelete();
            $table->string('station_name');
            $table->string('station_code')->nullable();
            $table->string('station_type')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('official_url')->nullable();
            $table->string('source_name')->nullable();
            $table->text('source_url')->nullable();
            $table->string('source_status')->default('pending_verification');
            $table->timestamp('last_verified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['state_id', 'police_district_id']);
            $table->index(['station_name']);
            $table->index(['source_status', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('police_stations');
        Schema::dropIfExists('police_districts');
    }
};