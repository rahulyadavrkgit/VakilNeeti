<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_cases', function (Blueprint $table) {
            $table->string('file_number', 100)->nullable()->after('case_number');
            $table->string('stage')->nullable()->after('case_status');
            $table->date('disposed_on')->nullable()->after('filing_date');
            $table->string('petitioner_advocate')->nullable();
            $table->string('respondent_advocate')->nullable();
            $table->timestamp('closed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('legal_cases', function (Blueprint $table) {
            $table->dropColumn(['file_number', 'stage', 'disposed_on', 'petitioner_advocate', 'respondent_advocate', 'closed_at']);
        });
    }
};
