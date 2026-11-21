<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('court_hearings', function (Blueprint $table) {
            $table->date('hearing_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Keep the column nullable on rollback so existing records
        // without a previous hearing date are not lost or invalidated.
    }
};