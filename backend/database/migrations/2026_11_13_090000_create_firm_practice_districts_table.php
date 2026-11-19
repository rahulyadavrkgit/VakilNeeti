<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firm_practice_districts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('firm_practice_state_id')
                ->constrained('firm_practice_states')
                ->cascadeOnDelete();

            $table->foreignId('judicial_district_id')
                ->constrained('judicial_districts')
                ->restrictOnDelete();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(
                ['firm_practice_state_id', 'judicial_district_id'],
                'firm_practice_district_unique'
            );

            $table->index(['firm_practice_state_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firm_practice_districts');
    }
};