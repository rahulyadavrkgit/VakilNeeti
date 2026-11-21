<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('police_ranges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('states')->cascadeOnDelete();
            $table->string('range_name');
            $table->string('range_code')->nullable();
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
        Schema::dropIfExists('police_ranges');
    }
};