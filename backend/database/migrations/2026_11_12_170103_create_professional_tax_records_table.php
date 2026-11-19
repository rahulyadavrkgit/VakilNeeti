<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_tax_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('firm_profile_id')
                ->constrained('firm_profiles')
                ->restrictOnDelete();

            $table->string('state', 120);
            $table->enum('registration_type', [
                'PTEC',
                'PTRC',
                'Both',
                'Other'
            ])->default('PTEC');

            $table->string('registration_number', 100)->nullable();
            $table->string('enrolment_number', 100)->nullable();

            $table->enum('period_type', [
                'monthly',
                'quarterly',
                'annual',
                'one_time'
            ])->default('monthly');

            $table->string('tax_period', 50);
            $table->date('due_date')->nullable();
            $table->decimal('amount', 12, 2)->default(0);

            $table->enum('payment_status', [
                'pending',
                'paid'
            ])->default('pending');

            $table->date('payment_date')->nullable();
            $table->string('challan_number', 120)->nullable();
            $table->string('payment_reference', 150)->nullable();

            $table->enum('payment_method', [
                'online',
                'bank',
                'other'
            ])->nullable();

            $table->enum('return_status', [
                'not_required',
                'pending',
                'filed'
            ])->default('pending');

            $table->date('return_filing_date')->nullable();
            $table->string('return_acknowledgement_number', 150)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['firm_profile_id', 'state']);
            $table->index(['payment_status', 'due_date']);
            $table->index(['tax_period', 'period_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_tax_records');
    }
};