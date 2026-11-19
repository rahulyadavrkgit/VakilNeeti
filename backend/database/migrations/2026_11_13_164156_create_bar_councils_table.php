<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bar_councils', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->string('council_name'); // e.g. Bar Council of Delhi, Bar Council of Uttar Pradesh
            $table->string('jurisdiction_type')->default('state'); // state, national (BCI)
            $table->text('office_address')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('email')->nullable();
            $table->string('official_url')->nullable();
            
            // Source & Verification Tracking
            $table->string('source_name')->nullable();
            $table->string('source_url')->nullable();
            $table->string('source_status')->default('verified'); // verified, official_gazette, portal
            $table->timestamp('last_verified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bar_councils');
    }
};