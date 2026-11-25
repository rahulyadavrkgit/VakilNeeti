<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which court and state each case belongs to, so cases from Jalaun, Noida, Delhi HC and the Supreme Court
 * are told apart everywhere, and one matter can be followed across courts (matter_id).
 *
 * Safe to run again after a partial run (MySQL does not undo table changes when a migration fails).
 * Saved cases are filled afterwards with `php artisan cases:court-identity` (it needs later migrations).
 */
return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            // "UP", "DL"… (eCourts / ISO state letters)
            'state_code' => fn (Blueprint $table) => $table->string('state_code', 4)->nullable()->after('court_complex'),
            // supreme | high | district | tribunal
            'court_level' => fn (Blueprint $table) => $table->string('court_level', 12)->nullable()->after('state_code'),
            // First 6 letters of the CNR ("UPJL01" = UP · Jalaun · court complex 01); "SC" for the Supreme Court.
            'court_code' => fn (Blueprint $table) => $table->string('court_code', 12)->nullable()->after('court_level'),
            'district' => fn (Blueprint $table) => $table->string('district', 100)->nullable()->after('court_code'),
            // Supreme Court diary number ("47715/2026"); the Supreme Court has no CNR.
            'diary_number' => fn (Blueprint $table) => $table->string('diary_number', 30)->nullable()->after('cnr_number'),
            // One matter across courts (District → High Court → Supreme Court), e.g. "VN-0042".
            'matter_id' => fn (Blueprint $table) => $table->string('matter_id', 20)->nullable()->after('diary_number'),
        ];

        foreach ($columns as $column => $add) {
            if (! Schema::hasColumn('legal_cases', $column)) {
                Schema::table('legal_cases', $add);
            }
        }

        foreach (['state_code', 'court_code', 'matter_id'] as $column) {
            if (! Schema::hasIndex('legal_cases', ['user_id', $column])) {
                Schema::table('legal_cases', fn (Blueprint $table) => $table->index(['user_id', $column]));
            }
        }

        // What each CNR court prefix stands for, learned from imported cases ("UPJL" = Jalaun, Uttar Pradesh).
        if (! Schema::hasTable('court_codes')) {
            Schema::create('court_codes', function (Blueprint $table) {
                $table->id();
                $table->string('code', 4)->unique();
                $table->string('state_code', 4);
                $table->string('court_level', 12);
                $table->string('district', 100)->nullable();
                $table->timestamps();
            });
        }
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
