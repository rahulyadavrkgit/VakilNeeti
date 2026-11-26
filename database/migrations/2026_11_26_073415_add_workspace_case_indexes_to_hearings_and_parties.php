<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every query on hearings and parties is limited to one workspace (user_id) and usually to one case.
 * With only a user_id index, a case list checked each case against all of the workspace's hearings,
 * which grew with the square of the number of cases. One index on both columns keeps it direct.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('court_hearings', function (Blueprint $table) {
            $table->index(['user_id', 'case_id']);
        });

        Schema::table('case_parties', function (Blueprint $table) {
            $table->index(['user_id', 'case_id']);
        });
    }

    public function down(): void
    {
        Schema::table('court_hearings', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'case_id']);
        });

        Schema::table('case_parties', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'case_id']);
        });
    }
};
