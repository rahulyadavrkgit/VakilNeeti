<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('police_stations', function (Blueprint $table) {
            $table->string('city')->nullable();
            $table->string('locality')->nullable();
            $table->string('pincode', 10)->nullable();

            $table->index(['state_id', 'city']);
            $table->index(['state_id', 'pincode']);
        });
    }

    public function down(): void
    {
        Schema::table('police_stations', function (Blueprint $table) {
            $table->dropIndex(['state_id', 'city']);
            $table->dropIndex(['state_id', 'pincode']);

            $table->dropColumn(['city', 'locality', 'pincode']);
        });
    }
};