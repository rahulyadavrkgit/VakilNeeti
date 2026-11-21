<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_catalogue_change_logs', function (Blueprint $table) {
            $table->id();

            $table->string('provider_key', 100);
            $table->string('entity_type', 100);

            $table->string('local_table', 100)->nullable();
            $table->unsignedBigInteger('local_record_id')->nullable();

            $table->string('external_id', 255)->nullable();

            $table->string('field_name', 150);

            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();

            $table->text('source_url')->nullable();
            $table->string('source_name', 190)->nullable();

            $table->foreignId('api_sync_run_id')
                ->nullable()
                ->constrained('api_sync_runs')
                ->nullOnDelete();

            $table->timestamp('changed_at');

            $table->timestamps();

            $table->index(
                ['provider_key', 'entity_type', 'changed_at'],
                'catalogue_changes_provider_entity_date'
            );

            $table->index(
                ['local_table', 'local_record_id'],
                'catalogue_changes_local_record'
            );

            $table->index('external_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_catalogue_change_logs');
    }
};