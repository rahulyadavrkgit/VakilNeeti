<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tds_deductions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')
                ->constrained('invoices')
                ->restrictOnDelete();

            $table->foreignId('client_id')
                ->constrained('clients')
                ->restrictOnDelete();

            $table->string('deduction_reference')->unique();
            $table->date('deduction_date');

            $table->string('tds_section', 30)->nullable();
            $table->decimal('deduction_rate', 7, 3)->nullable();
            $table->decimal('deduction_base_amount', 12, 2)->nullable();
            $table->decimal('amount', 12, 2);

            $table->string('certificate_number', 100)->nullable();
            $table->date('certificate_date')->nullable();
            $table->string('financial_year', 10)->nullable();

            $table->enum('certificate_status', [
                'awaited',
                'received',
                'verified'
            ])->default('awaited');

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'deduction_date']);
            $table->index(['client_id', 'deduction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tds_deductions');
    }
};