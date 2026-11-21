<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bar associations already exist at this point in the migration sequence.
        if (
            Schema::hasTable('bar_associations') &&
            !Schema::hasColumn('bar_associations', 'court_complex_id')
        ) {
            Schema::table('bar_associations', function (Blueprint $table) {
                $table->foreignId('court_complex_id')
                    ->nullable()
                    ->constrained('court_complexes')
                    ->nullOnDelete();
            });
        }

        // Police station fields are added by a separate migration after
        // the police_stations table has been created.
    }

    public function down(): void
    {
        if (
            Schema::hasTable('bar_associations') &&
            Schema::hasColumn('bar_associations', 'court_complex_id')
        ) {
            Schema::table('bar_associations', function (Blueprint $table) {
                $table->dropConstrainedForeignId('court_complex_id');
            });
        }
    }
};
