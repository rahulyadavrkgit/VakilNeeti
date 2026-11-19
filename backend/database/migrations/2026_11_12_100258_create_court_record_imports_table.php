<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_record_imports', function (Blueprint $table) {
            $table->id();
            $table->string('source_name');
            $table->string('source_url', 2048)->nullable();
            $table->longText('raw_content');
            $table->json('parsed_data')->nullable();
            $table->string('import_status')->default('preview');
            $table->foreignId('legal_case_id')
                ->nullable()
                ->constrained('legal_cases')
                ->nullOnDelete();
            $table->timestamp('retrieved_at')->nullable();
            $table->timestamps();

            $table->index('import_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_record_imports');
    }
};