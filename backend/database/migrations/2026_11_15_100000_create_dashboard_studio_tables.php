<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_dashboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('visibility')->default('private');
            $table->timestamps();
        });

        Schema::create('dashboard_data_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained('custom_dashboards')->cascadeOnDelete();
            $table->string('name');
            $table->string('provider')->default('data.gov.in');
            $table->string('resource_id', 100);
            $table->string('dataset_title')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_tested_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['dashboard_id', 'is_active']);
        });

        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained('custom_dashboards')->cascadeOnDelete();
            $table->foreignId('data_source_id')->nullable()->constrained('dashboard_data_sources')->nullOnDelete();
            $table->string('title');
            $table->string('type')->default('table');
            $table->json('configuration')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('dashboard_data_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_source_id')->constrained('dashboard_data_sources')->cascadeOnDelete();
            $table->json('payload');
            $table->unsignedInteger('row_count')->default(0);
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->index(['data_source_id', 'fetched_at']);
        });

        Schema::create('dashboard_refresh_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_source_id')->constrained('dashboard_data_sources')->cascadeOnDelete();
            $table->string('status', 30);
            $table->text('message')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_refresh_logs');
        Schema::dropIfExists('dashboard_data_snapshots');
        Schema::dropIfExists('dashboard_widgets');
        Schema::dropIfExists('dashboard_data_sources');
        Schema::dropIfExists('custom_dashboards');
    }
};