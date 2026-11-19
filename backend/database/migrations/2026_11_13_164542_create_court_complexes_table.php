<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_complexes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('states')->cascadeOnDelete();
            $table->foreignId('high_court_id')->nullable()->constrained('high_courts')->nullOnDelete();
            $table->string('judicial_district_name'); // Distinct from police districts
            $table->string('complex_name'); // e.g., Patiala House Court Complex, Tis Hazari Court Complex
            $table->string('complex_type')->default('District & Sessions Court'); // District & Sessions Court, Family Court, Commercial Court, Magistrate Complex
            $table->string('ecourts_complex_code')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('pincode')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('official_url')->nullable();
            $table->string('ecourts_url')->nullable();

            // Verification Metadata
            $table->string('source_name')->nullable();
            $table->string('source_url')->nullable();
            $table->string('source_status')->default('verified');
            $table->timestamp('last_verified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_complexes');
    }
};