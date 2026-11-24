<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * People sign in with their email now, so a junior or clerk can be invited by email as well as by mobile.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_invites', function (Blueprint $table) {
            $table->string('email')->nullable()->after('owner_id');
            $table->string('mobile', 20)->nullable()->change();
            $table->unique(['owner_id', 'email']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::table('team_invites', function (Blueprint $table) {
            $table->dropUnique(['owner_id', 'email']);
            $table->dropIndex(['email']);
            $table->dropColumn('email');
        });
    }
};
