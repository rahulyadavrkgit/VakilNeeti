<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_establishments', function (Blueprint $table) {
            $table->id();

            // Link establishment to its parent court
            $table->foreignId('court_id')
                ->constrained('courts')
                ->cascadeOnDelete();

            // Establishment identification
            $table->string('establishment_name');
            $table->string('establishment_code')->nullable();
            $table->string('establishment_type');

            // Physical location
            $table->string('country')->default('India');
            $table->string('state')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->string('pincode', 10)->nullable();

            // Official contact information
            $table->string('official_website')->nullable();
            $table->string('official_email')->nullable();
            $table->string('official_phone')->nullable();

            // Additional information
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique(
                ['court_id', 'establishment_code'],
                'court_establishment_code_unique'
            );

            $table->index(['state', 'district']);
            $table->index(['court_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_establishments');
    }
};
