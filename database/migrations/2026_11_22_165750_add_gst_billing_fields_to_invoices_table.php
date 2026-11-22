<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('legal_case_id')->nullable()->after('client_id')->constrained('legal_cases')->nullOnDelete();
            $table->string('payment_terms', 20)->default('due_on_receipt')->after('due_date');
            $table->string('client_pan', 10)->nullable();
            $table->string('client_gstin', 15)->nullable();
            $table->string('place_of_supply', 120)->nullable();
            $table->text('billing_address')->nullable();
            $table->timestamp('written_off_at')->nullable();
            $table->string('write_off_reason', 255)->nullable();
        });

        foreach (['case_expenses', 'case_time_entries'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['case_expenses', 'case_time_entries'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('invoice_id');
            });
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('legal_case_id');
            $table->dropColumn(['payment_terms', 'client_pan', 'client_gstin', 'place_of_supply', 'billing_address', 'written_off_at', 'write_off_reason']);
        });
    }
};
