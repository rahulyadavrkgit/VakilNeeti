<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Public court-room directory: who sits in which room, with the room's VC link,
     * meeting ID and email. One row per court room (a bench may have several judges).
     */
    public function up(): void
    {
        Schema::create('court_benches', function (Blueprint $table) {
            $table->id();
            $table->string('court_key', 60);
            $table->string('court_room', 60)->nullable();
            $table->string('bench', 60)->nullable();
            $table->json('judges')->nullable();
            $table->string('vc_link', 500)->nullable();
            $table->string('vc_meeting_id', 160)->nullable();
            $table->string('email', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['court_key', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_benches');
    }
};
