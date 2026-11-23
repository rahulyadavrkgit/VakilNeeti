<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which side the advocate appears for, the usual court room and the court's video-conference link.
     */
    public function up(): void
    {
        Schema::table('legal_cases', function (Blueprint $table) {
            $table->string('appearing_for', 30)->nullable()->after('case_status');
            $table->string('court_room', 100)->nullable()->after('court_complex');
            $table->string('vc_link', 500)->nullable()->after('court_room');
        });
    }

    public function down(): void
    {
        Schema::table('legal_cases', function (Blueprint $table) {
            $table->dropColumn(['appearing_for', 'court_room', 'vc_link']);
        });
    }
};
