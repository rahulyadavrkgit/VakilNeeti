<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_hearings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')
                ->constrained('legal_cases')
                ->cascadeOnDelete();

            $table->date('hearing_date');
            $table->time('hearing_time')->nullable();
            $table->string('court_room')->nullable();
            $table->string('judge_name')->nullable();
            $table->string('hearing_stage')->nullable();
            $table->text('proceedings')->nullable();
            $table->text('next_action')->nullable();
            $table->date('next_hearing_date')->nullable();
            $table->string('source_type')->default('manual');
            $table->string('source_reference')->nullable();
            $table->timestamps();

            $table->index(['case_id', 'hearing_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_hearings');
    }
};