<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cities and towns of India (urban local bodies from the Local Government Directory),
 * linked to their State and, where it can be determined, their district.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('states')->cascadeOnDelete();
            $table->foreignId('judicial_district_id')->nullable()->constrained('judicial_districts')->nullOnDelete();
            $table->string('name');
            $table->unsignedInteger('lgd_code')->nullable()->unique();
            $table->string('lgd_name')->nullable();
            $table->string('body_type', 60)->nullable();
            $table->string('pincode', 6)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['state_id', 'name']);
            $table->index(['judicial_district_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
