<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firm_practice_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_profile_id')
                ->constrained('firm_profiles')
                ->cascadeOnDelete();
            $table->string('state_name', 120);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['firm_profile_id', 'state_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firm_practice_states');
    }
};