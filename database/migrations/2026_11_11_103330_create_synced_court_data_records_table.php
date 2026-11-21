<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_data_records', function (Blueprint $table) {
            $table->id();

            // Provider identification
            $table->string('provider_key');

            $table->foreign('provider_key')
                ->references('provider_key')
                ->on('court_data_sources')
                ->restrictOnDelete();

            // Record identification
            $table->string('entity_type');
            $table->string('external_id');
            $table->string('external_parent_id')->nullable();

            // Search and display information
            $table->string('display_name')->nullable();

            // Normalised data received from the provider
            $table->json('attributes');

            // Provenance and synchronisation
            $table->text('source_url')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->string('payload_hash', 64)->nullable();

            // Record lifecycle
            $table->string('record_status')->default('active');

            $table->timestamps();

            // One external record per provider and entity type
            $table->unique(
                ['provider_key', 'entity_type', 'external_id'],
                'court_data_record_external_unique'
            );

            $table->index(['entity_type', 'record_status']);
            $table->index(['provider_key', 'entity_type']);
            $table->index(['entity_type', 'external_parent_id']);
            $table->index('display_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_data_records');
    }
};
