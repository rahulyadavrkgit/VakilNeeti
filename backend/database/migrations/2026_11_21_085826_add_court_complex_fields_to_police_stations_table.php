<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('police_stations')) {
            return;
        }

        if (!Schema::hasColumn('police_stations', 'court_complex_id')) {
            Schema::table('police_stations', function (Blueprint $table) {
                $table->foreignId('court_complex_id')
                    ->nullable()
                    ->constrained('court_complexes')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('police_stations', 'remand_court_notes')) {
            Schema::table('police_stations', function (Blueprint $table) {
                $table->string('remand_court_notes')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('police_stations') &&
            Schema::hasColumn('police_stations', 'court_complex_id')
        ) {
            Schema::table('police_stations', function (Blueprint $table) {
                $table->dropConstrainedForeignId('court_complex_id');
            });
        }

        if (
            Schema::hasTable('police_stations') &&
            Schema::hasColumn('police_stations', 'remand_court_notes')
        ) {
            Schema::table('police_stations', function (Blueprint $table) {
                $table->dropColumn('remand_court_notes');
            });
        }
    }
};