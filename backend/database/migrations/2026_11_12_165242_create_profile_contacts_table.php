<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_profile_id')
                ->constrained('firm_profiles')
                ->cascadeOnDelete();

            $table->string('contact_type', 30);
            $table->string('label', 100)->nullable();
            $table->string('contact_value', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['firm_profile_id', 'contact_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_contacts');
    }
};