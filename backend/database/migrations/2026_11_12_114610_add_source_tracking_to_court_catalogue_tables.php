<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['courts', 'judicial_districts', 'court_establishments'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->text('source_url')->nullable();
                $table->string('source_name', 190)->nullable();
                $table->string('source_status', 40)
                    ->default('pending_verification');
                $table->timestamp('last_verified_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['courts', 'judicial_districts', 'court_establishments'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn([
                    'source_url',
                    'source_name',
                    'source_status',
                    'last_verified_at',
                ]);
            });
        }
    }
};
