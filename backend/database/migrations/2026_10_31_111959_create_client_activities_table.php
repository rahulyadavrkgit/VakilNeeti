<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('activity_type');
            $table->string('subject');
            $table->text('description')->nullable();

            $table->dateTime('activity_at');
            $table->dateTime('follow_up_at')->nullable();

            $table->enum('status', [
                'pending',
                'completed',
                'cancelled',
            ])->default('completed');

            $table->timestamps();

            $table->index(['client_id', 'activity_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_activities');
    }
};