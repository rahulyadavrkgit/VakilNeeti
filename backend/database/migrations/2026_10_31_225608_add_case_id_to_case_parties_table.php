<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_parties', function (Blueprint $table) {
            $table->foreignId('case_id')
                ->nullable()
                ->after('id')
                ->constrained('legal_cases')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('case_parties', function (Blueprint $table) {
            $table->dropConstrainedForeignId('case_id');
        });
    }
};