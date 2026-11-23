<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A video-conference link for one hearing, for courts that send a new link for each date.
     */
    public function up(): void
    {
        Schema::table('court_hearings', function (Blueprint $table) {
            $table->string('vc_link', 500)->nullable()->after('court_room');
        });
    }

    public function down(): void
    {
        Schema::table('court_hearings', function (Blueprint $table) {
            $table->dropColumn('vc_link');
        });
    }
};
