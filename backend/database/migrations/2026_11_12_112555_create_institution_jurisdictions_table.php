
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_jurisdictions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('court_id')
                ->constrained('courts')
                ->cascadeOnDelete();

            $table->foreignId('state_id')
                ->nullable()
                ->constrained('states')
                ->nullOnDelete();

            $table->foreignId('judicial_district_id')
                ->nullable()
                ->constrained('judicial_districts')
                ->nullOnDelete();

            $table->string('jurisdiction_type', 60);
            $table->text('description')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->timestamps();

            $table->index(['court_id', 'jurisdiction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_jurisdictions');
    }
};
