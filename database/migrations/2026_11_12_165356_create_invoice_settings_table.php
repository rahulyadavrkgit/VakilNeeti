<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('firm_profile_id')
                ->unique()
                ->constrained('firm_profiles')
                ->cascadeOnDelete();

            $table->boolean('gst_enabled')->default(false);
            $table->string('gst_registration_type', 30)->default('regular');
            $table->string('default_tax_rate', 10)->default('18.00');
            $table->string('default_sac_code', 30)->nullable();
            $table->string('default_tax_mode', 20)->default('exclusive');
            $table->boolean('reverse_charge_applicable')->default(false);

            $table->string('invoice_prefix', 30)->default('INV');
            $table->string('invoice_number_format', 50)->default('{PREFIX}/{YEAR}/{NUMBER}');
            $table->unsignedInteger('default_payment_due_days')->default(15);

            $table->string('bank_name', 150)->nullable();
            $table->string('account_holder_name', 150)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('ifsc_code', 20)->nullable();
            $table->string('branch_name', 150)->nullable();
            $table->string('upi_id', 150)->nullable();

            $table->text('default_invoice_terms')->nullable();
            $table->text('default_invoice_notes')->nullable();
            $table->string('invoice_footer', 500)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_settings');
    }
};