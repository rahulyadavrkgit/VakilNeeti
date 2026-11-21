<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('police_districts', function (Blueprint $table) {
            if (!Schema::hasColumn('police_districts', 'police_range_id')) {
                $table->foreignId('police_range_id')->nullable()->after('state_id')->constrained('police_ranges')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('police_districts', function (Blueprint $table) {
            $table->dropForeign(['police_range_id']);
            $table->dropColumn('police_range_id');
        });
    }
};