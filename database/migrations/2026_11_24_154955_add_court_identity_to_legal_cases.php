<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * Which court and state each case belongs to, so cases from Jalaun, Noida, Delhi HC and the Supreme Court
 * are told apart everywhere, and one matter can be followed across courts (matter_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_cases', function (Blueprint $table) {
            // "UP", "DL"… (eCourts / ISO state letters)
            $table->string('state_code', 4)->nullable()->after('court_complex');
            // supreme | high | district | tribunal
            $table->string('court_level', 12)->nullable()->after('state_code');
            // First 6 letters of the CNR ("UPJL01" = UP · Jalaun · court complex 01); "SC" for the Supreme Court.
            $table->string('court_code', 12)->nullable()->after('court_level');
            $table->string('district', 100)->nullable()->after('court_code');
            // Supreme Court diary number ("47715/2026"); the Supreme Court has no CNR.
            $table->string('diary_number', 30)->nullable()->after('cnr_number');
            // One matter across courts (District → High Court → Supreme Court), e.g. "VN-0042".
            $table->string('matter_id', 20)->nullable()->after('diary_number');

            $table->index(['user_id', 'state_code']);
            $table->index(['user_id', 'court_code']);
            $table->index(['user_id', 'matter_id']);
        });

        // What each CNR court prefix stands for, learned from imported cases ("UPJL" = Jalaun, Uttar Pradesh).
        Schema::create('court_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 4)->unique();
            $table->string('state_code', 4);
            $table->string('court_level', 12);
            $table->string('district', 100)->nullable();
            $table->timestamps();
        });

        // Fill the new fields for the cases already saved.
        Artisan::call('cases:court-identity');
    }

    public function down(): void
    {
        Schema::dropIfExists('court_codes');

        Schema::table('legal_cases', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'state_code']);
            $table->dropIndex(['user_id', 'court_code']);
            $table->dropIndex(['user_id', 'matter_id']);
            $table->dropColumn(['state_code', 'court_level', 'court_code', 'district', 'diary_number', 'matter_id']);
        });
    }
};
