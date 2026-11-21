<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bar_associations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('states')->cascadeOnDelete();
            $table->unsignedBigInteger('judicial_district_id')->nullable(); // Can link to judicial districts if applicable
            $table->string('association_name'); // e.g. New Delhi Bar Association, Supreme Court Bar Association
            $table->string('court_type'); // Supreme Court, High Court, District Court, Tribunal
            $table->text('office_address')->nullable();
            $table->string('president_name')->nullable();
            $table->string('secretary_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('email')->nullable();
            $table->string('official_url')->nullable();
            
            // Source & Verification Tracking
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
        Schema::dropIfExists('bar_associations');
    }
};