<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_institution_category', function (Blueprint $table) {
            $table->id();

            $table->foreignId('court_id')
                ->constrained('courts')
                ->cascadeOnDelete();

            $table->foreignId('institution_category_id')
                ->constrained('institution_categories')
                ->cascadeOnDelete();

            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(
                ['court_id', 'institution_category_id'],
                'court_institution_category_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_institution_category');
    }
};
