<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Indian States and Union Territories
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 15)->unique();
            $table->string('country_code', 5)->default('IN');
            $table->boolean('is_union_territory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['country_code', 'is_active']);
        });

        // Judicial institution categories
        Schema::create('court_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('requires_state')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Connect existing Courts to the new reference masters.
        Schema::table('courts', function (Blueprint $table) {
            $table->foreignId('state_id')
                ->nullable()
                ->constrained('states')
                ->nullOnDelete();

            $table->foreignId('court_type_id')
                ->nullable()
                ->constrained('court_types')
                ->nullOnDelete();
        });

        // Judicial districts are separate from physical complexes.
        Schema::create('judicial_districts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('state_id')
                ->constrained('states')
                ->restrictOnDelete();

            $table->foreignId('court_id')
                ->nullable()
                ->constrained('courts')
                ->nullOnDelete();

            $table->string('district_name');
            $table->string('district_code', 100)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['state_id', 'district_name']);
            $table->index(['court_id', 'is_active']);
        });

        // Many-to-many relationship:
        // A physical establishment can serve multiple judicial districts.
        Schema::create(
            'court_establishment_judicial_district',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('court_establishment_id')
                    ->constrained('court_establishments', indexName: 'cejd_court_establishment_id_foreign')
                    ->cascadeOnDelete();

                $table->foreignId('judicial_district_id')
                    ->constrained('judicial_districts', indexName: 'cejd_judicial_district_id_foreign')
                    ->cascadeOnDelete();

                $table->boolean('is_primary')->default(false);

                $table->timestamps();

                $table->unique(
                    ['court_establishment_id', 'judicial_district_id'],
                    'establishment_judicial_district_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('court_establishment_judicial_district');
        Schema::dropIfExists('judicial_districts');

        Schema::table('courts', function (Blueprint $table) {
            $table->dropForeign(['state_id']);
            $table->dropForeign(['court_type_id']);
            $table->dropColumn(['state_id', 'court_type_id']);
        });

        Schema::dropIfExists('court_types');
        Schema::dropIfExists('states');
    }
};
