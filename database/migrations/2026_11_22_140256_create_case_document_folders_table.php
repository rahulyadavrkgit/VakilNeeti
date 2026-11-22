<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Folders organise case documents. They are logical only: files stay in private storage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_document_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('legal_cases')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('case_document_folders')->cascadeOnDelete();
            $table->string('name', 120);
            $table->timestamps();

            $table->index(['case_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_document_folders');
    }
};
