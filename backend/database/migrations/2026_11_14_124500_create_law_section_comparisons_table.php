<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('law_section_comparisons')) {
            return;
        }

        Schema::create('law_section_comparisons', function (Blueprint $table) {
            $table->id();

            $table->string('pair', 30);
            $table->string('direction', 30);
            $table->unsignedInteger('row_order');

            $table->longText('left_text')->nullable();
            $table->longText('right_text')->nullable();

            $table->string('status', 30)->default('corresponding');

            $table->string('source_url', 1000);
            $table->timestamp('imported_at')->nullable();

            $table->timestamps();

            $table->index(['pair', 'direction', 'status'], 'law_comparison_search_idx');
            $table->unique(['pair', 'direction', 'row_order'], 'law_comparison_row_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('law_section_comparisons');
    }
};