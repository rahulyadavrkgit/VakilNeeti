<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Group (Division Bench, Single Bench — Criminal…), where the court sits, and the
     * categories of matters the bench hears under the court's roster.
     */
    public function up(): void
    {
        Schema::table('court_benches', function (Blueprint $table) {
            $table->string('section', 60)->nullable()->after('court_key');
            $table->string('location', 255)->nullable()->after('bench');
            $table->json('roster')->nullable()->after('judges');
        });
    }

    public function down(): void
    {
        Schema::table('court_benches', function (Blueprint $table) {
            $table->dropColumn(['section', 'location', 'roster']);
        });
    }
};
