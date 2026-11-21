<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_title');
            $table->string('case_number')->nullable();
            $table->string('cnr_number')->nullable()->index();
            $table->string('case_type')->nullable();
            $table->string('court_name');
            $table->string('court_complex')->nullable();
            $table->string('judge_name')->nullable();
            $table->string('filing_number')->nullable();
            $table->date('filing_date')->nullable();
            $table->string('case_status')->default('pending');
            $table->text('case_description')->nullable();
            $table->string('source_type')->default('manual');
            $table->string('source_reference')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['court_name', 'case_number']);
            $table->index(['case_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_cases');
    }
};