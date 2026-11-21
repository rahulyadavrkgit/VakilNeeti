<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('legal_cases')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160)->nullable();
            $table->text('body');
            $table->timestamps();
            $table->index(['case_id', 'created_at']);
        });

        Schema::create('case_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('legal_cases')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->dateTime('starts_at');
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['case_id', 'starts_at']);
        });

        Schema::create('case_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('legal_cases')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->timestamps();
            $table->index(['case_id', 'expense_date']);
        });

        Schema::create('case_time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('legal_cases')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->date('entry_date');
            $table->unsignedTinyInteger('hours')->default(0);
            $table->unsignedTinyInteger('minutes')->default(0);
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->timestamps();
            $table->index(['case_id', 'entry_date']);
        });

        Schema::create('case_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('legal_cases')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->timestamps();
            $table->index(['case_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_documents');
        Schema::dropIfExists('case_time_entries');
        Schema::dropIfExists('case_expenses');
        Schema::dropIfExists('case_appointments');
        Schema::dropIfExists('case_notes');
    }
};
