<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_settings', function (Blueprint $table) {
            $table->boolean('tds_enabled')->default(false);
            $table->string('tds_section', 30)->nullable();
            $table->decimal('tds_default_rate', 7, 3)->nullable();
            $table->string('tan_number', 20)->nullable();

            $table->boolean('professional_tax_applicable')->default(false);
            $table->string('professional_tax_state', 120)->nullable();
            $table->string('professional_tax_registration_number', 100)->nullable();
            $table->string('professional_tax_enrolment_number', 100)->nullable();
            $table->string('professional_tax_frequency', 30)->default('monthly');
            $table->text('professional_tax_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_settings', function (Blueprint $table) {
            $table->dropColumn([
                'tds_enabled',
                'tds_section',
                'tds_default_rate',
                'tan_number',
                'professional_tax_applicable',
                'professional_tax_state',
                'professional_tax_registration_number',
                'professional_tax_enrolment_number',
                'professional_tax_frequency',
                'professional_tax_notes',
            ]);
        });
    }
};