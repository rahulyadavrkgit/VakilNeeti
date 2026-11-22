<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::create('case_tag', function (Blueprint $table) {
            $table->foreignId('case_tag_id')->constrained('case_tags')->cascadeOnDelete();
            $table->foreignId('legal_case_id')->constrained('legal_cases')->cascadeOnDelete();

            $table->primary(['case_tag_id', 'legal_case_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_tag');
        Schema::dropIfExists('case_tags');
    }
};
