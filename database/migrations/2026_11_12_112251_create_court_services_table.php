<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_services', function (Blueprint $table) {
            $table->id();

            $table->foreignId('court_id')
                ->nullable()
                ->constrained('courts')
                ->nullOnDelete();

            $table->foreignId('establishment_id')
                ->nullable()
                ->constrained('court_establishments')
                ->nullOnDelete();

            $table->string('scope_key', 190)->unique();
            $table->string('service_key', 100);
            $table->string('service_name', 190);
            $table->string('service_category', 100);

            $table->text('description')->nullable();
            $table->text('official_url')->nullable();
            $table->text('source_url')->nullable();
            $table->string('source_name', 190)->nullable();

            $table->string('link_status', 40)
                ->default('pending_verification');

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamp('last_verified_at')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['court_id', 'is_active']);
            $table->index(['establishment_id', 'is_active']);
            $table->index(['service_category', 'is_active']);
            $table->index(['service_key', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_services');
    }
};
