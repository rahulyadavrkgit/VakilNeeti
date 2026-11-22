<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Each user numbers their own clients (1, 2, 3…), so codes only need to be unique per user.
 * A global unique index made the second user's first client fail to save.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique('clients_client_code_unique');
            $table->unique(['user_id', 'client_code']);
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'client_code']);
            $table->unique('client_code');
        });
    }
};
