<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')
                ->constrained('invoices')
                ->cascadeOnDelete();

            $table->foreignId('client_id')
                ->constrained('clients')
                ->cascadeOnDelete();

            $table->string('receipt_number')->unique();

            $table->date('payment_date');
            $table->decimal('amount', 12, 2);

            $table->enum('payment_method', [
                'cash',
                'upi',
                'bank_transfer',
                'cheque',
                'card',
                'other'
            ])->default('other');

            $table->string('transaction_reference')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};