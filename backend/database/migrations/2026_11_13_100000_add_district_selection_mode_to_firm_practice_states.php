<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firm_practice_states', function (Blueprint $table) {
            $table->string('district_selection_mode', 20)
                ->default('all')
                ->after('state_name');
        });
    }

    public function down(): void
    {
        Schema::table('firm_practice_states', function (Blueprint $table) {
            $table->dropColumn('district_selection_mode');
        });
    }
};