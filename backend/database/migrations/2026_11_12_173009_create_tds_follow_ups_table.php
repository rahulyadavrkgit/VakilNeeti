<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tds_follow_ups', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tds_deduction_id')
                ->constrained('tds_deductions')
                ->cascadeOnDelete();

            $table->date('contact_date');
            $table->string('contact_method', 30)->default('email');

            $table->string('follow_up_status', 30)->default('completed');

            $table->text('outcome')->nullable();
            $table->text('notes')->nullable();

            $table->date('next_follow_up_date')->nullable();

            $table->timestamps();

            $table->index(['tds_deduction_id', 'contact_date']);
            $table->index(['follow_up_status', 'next_follow_up_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tds_follow_ups');
    }
};