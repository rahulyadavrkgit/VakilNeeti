<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per advocate and court: which side (facing the judge) the petitioner's counsel stands.
     * Practice differs between courts, so the advocate records it once per court.
     */
    public function up(): void
    {
        Schema::create('court_stand_sides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('court_name');
            $table->string('petitioner_side', 5);
            $table->timestamps();

            $table->unique(['user_id', 'court_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_stand_sides');
    }
};
