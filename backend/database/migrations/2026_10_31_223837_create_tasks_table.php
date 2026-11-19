<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->dateTime('due_at')->nullable();

            $table->enum('priority', [
                'low',
                'medium',
                'high',
                'urgent',
            ])->default('medium');

            $table->enum('status', [
                'pending',
                'in_progress',
                'completed',
                'cancelled',
            ])->default('pending');

            $table->dateTime('completed_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'due_at']);
            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};