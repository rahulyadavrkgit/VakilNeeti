<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_relationships', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parent_court_id')
                ->constrained('courts')
                ->cascadeOnDelete();

            $table->foreignId('related_court_id')
                ->constrained('courts')
                ->cascadeOnDelete();

            $table->string('relationship_type', 60);
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->timestamps();

            $table->unique(
                ['parent_court_id', 'related_court_id', 'relationship_type'],
                'institution_relationship_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_relationships');
    }
};
