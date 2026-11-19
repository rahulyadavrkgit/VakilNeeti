<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_profile_id')
                ->unique()
                ->constrained('firm_profiles')
                ->cascadeOnDelete();

            $table->string('bar_council_name', 200)->nullable();
            $table->string('enrolment_number', 100)->nullable();
            $table->date('enrolment_date')->nullable();
            $table->string('bar_council_status', 100)->nullable();

            $table->string('supreme_court_aor_number', 100)->nullable();
            $table->date('aor_enrolment_date')->nullable();

            $table->text('qualifications')->nullable();
            $table->text('professional_memberships')->nullable();
            $table->text('practice_areas')->nullable();
            $table->text('courts_of_practice')->nullable();
            $table->date('practice_start_date')->nullable();
            $table->text('professional_bio')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_profiles');
    }
};