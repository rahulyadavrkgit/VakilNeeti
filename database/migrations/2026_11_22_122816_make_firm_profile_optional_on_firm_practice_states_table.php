<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Independent practitioners have no firm profile, so practice jurisdictions are owned by
 * the user (user_id) and only linked to a firm when one exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firm_practice_states', function (Blueprint $table) {
            $table->unsignedBigInteger('firm_profile_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('firm_practice_states', function (Blueprint $table) {
            $table->unsignedBigInteger('firm_profile_id')->nullable(false)->change();
        });
    }
};
