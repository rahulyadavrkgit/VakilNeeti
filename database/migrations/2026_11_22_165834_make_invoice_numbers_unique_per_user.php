<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Each user numbers their own invoices (e.g. INV/2026/0001), so numbers only need to be
 * unique per user, not across every account.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_invoice_number_unique');
            $table->unique(['user_id', 'invoice_number']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'invoice_number']);
            $table->unique('invoice_number');
        });
    }
};
