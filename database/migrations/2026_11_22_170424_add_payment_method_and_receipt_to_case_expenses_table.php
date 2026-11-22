<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_expenses', function (Blueprint $table) {
            $table->string('payment_method', 30)->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('receipt_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('case_expenses', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'receipt_path', 'receipt_name']);
        });
    }
};
