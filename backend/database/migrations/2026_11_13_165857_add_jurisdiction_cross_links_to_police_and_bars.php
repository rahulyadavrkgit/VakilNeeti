<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('police_stations', function (Blueprint $table) {
            $table->foreignId('court_complex_id')
                  ->nullable()
                  ->after('police_sub_division_id')
                  ->constrained('court_complexes')
                  ->nullOnDelete();
            $table->string('remand_court_notes')->nullable()->after('jurisdiction_area');
        });

        Schema::table('bar_associations', function (Blueprint $table) {
            $table->foreignId('court_complex_id')
                  ->nullable()
                  ->after('judicial_district_id')
                  ->constrained('court_complexes')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('police_stations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('court_complex_id');
            $table->dropColumn('remand_court_notes');
        });

        Schema::table('bar_associations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('court_complex_id');
        });
    }
};