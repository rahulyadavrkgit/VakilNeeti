<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Register external data providers and official sources.
        Schema::create('court_data_sources', function (Blueprint $table) {
            $table->id();

            $table->string('provider_key')->unique();
            $table->string('provider_name');
            $table->string('source_type');
            $table->string('base_url')->nullable();
            $table->string('terms_url')->nullable();
            $table->text('attribution_text')->nullable();

            $table->boolean('is_enabled')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['source_type', 'is_enabled']);
        });

        // Record every synchronisation attempt.
        Schema::create('api_sync_runs', function (Blueprint $table) {
            $table->id();

            $table->string('provider_key');
            $table->foreign('provider_key')
                ->references('provider_key')
                ->on('court_data_sources')
                ->restrictOnDelete();

            $table->string('entity_type')->nullable();
            $table->string('sync_mode')->default('full');
            $table->string('status')->default('queued');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->unsignedInteger('records_received')->default(0);
            $table->unsignedInteger('records_created')->default(0);
            $table->unsignedInteger('records_updated')->default(0);
            $table->unsignedInteger('records_skipped')->default(0);

            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['provider_key', 'status']);
            $table->index(['entity_type', 'status']);
        });

        // Link local records to the corresponding external records.
        Schema::create('external_record_mappings', function (Blueprint $table) {
            $table->id();

            $table->string('provider_key');
            $table->foreign('provider_key')
                ->references('provider_key')
                ->on('court_data_sources')
                ->restrictOnDelete();

            $table->string('entity_type');
            $table->string('local_table');
            $table->unsignedBigInteger('local_record_id');

            $table->string('external_id');
            $table->string('external_parent_id')->nullable();

            $table->text('source_url')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->string('payload_hash', 64)->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(
                ['provider_key', 'entity_type', 'external_id'],
                'external_mapping_source_unique'
            );

            $table->unique(
                ['provider_key', 'entity_type', 'local_table', 'local_record_id'],
                'external_mapping_local_unique'
            );

            $table->index(['local_table', 'local_record_id']);
            $table->index(['entity_type', 'external_parent_id']);
        });

        // Record individual errors without losing the sync history.
        Schema::create('api_sync_errors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('api_sync_run_id')
                ->constrained('api_sync_runs')
                ->cascadeOnDelete();

            $table->string('external_id')->nullable();
            $table->string('error_code')->nullable();
            $table->text('message');
            $table->json('context')->nullable();

            $table->timestamps();

            $table->index('error_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_sync_errors');
        Schema::dropIfExists('external_record_mappings');
        Schema::dropIfExists('api_sync_runs');
        Schema::dropIfExists('court_data_sources');
    }
};
