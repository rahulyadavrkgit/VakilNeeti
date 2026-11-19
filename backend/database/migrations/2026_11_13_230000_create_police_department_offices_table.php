<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('police_department_offices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('state_id')
                ->constrained('states')
                ->restrictOnDelete();

            $table->foreignId('police_district_id')
                ->nullable()
                ->constrained('police_districts')
                ->nullOnDelete();

            $table->string('office_name');
            $table->string('office_type', 80);
            $table->string('designation')->nullable();

            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('official_url')->nullable();

            $table->string('source_name')->nullable();
            $table->text('source_url')->nullable();
            $table->string('source_status', 40)->default('pending_verification');
            $table->timestamp('last_verified_at')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['state_id', 'office_type']);
            $table->index(['police_district_id', 'office_type']);
            $table->index(['source_status', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('police_department_offices');
    }
};