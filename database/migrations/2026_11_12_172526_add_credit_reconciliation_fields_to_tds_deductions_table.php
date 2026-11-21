<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tds_deductions', function (Blueprint $table) {
            $table->string('credit_status', 30)->default('not_checked');
            $table->date('credit_checked_at')->nullable();
            $table->string('credit_reference', 150)->nullable();
            $table->text('credit_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tds_deductions', function (Blueprint $table) {
            $table->dropColumn([
                'credit_status',
                'credit_checked_at',
                'credit_reference',
                'credit_notes',
            ]);
        });
    }
};