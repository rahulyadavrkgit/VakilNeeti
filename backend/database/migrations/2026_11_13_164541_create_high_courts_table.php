<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('high_courts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->string('court_name'); // e.g., Supreme Court of India, High Court of Delhi, High Court of Judicature at Allahabad
            $table->string('court_type')->default('High Court'); // Supreme Court, High Court, High Court Bench
            $table->string('seat_type')->default('Principal Seat'); // Principal Seat, Permanent Bench, Circuit Bench
            $table->string('city')->nullable();
            $table->string('jurisdiction_states')->nullable(); // Comma-separated states/UTs covered
            $table->text('address')->nullable();
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
        Schema::dropIfExists('high_courts');
    }
};