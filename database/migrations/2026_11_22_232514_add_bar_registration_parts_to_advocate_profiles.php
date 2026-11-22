<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store the Bar Council enrolment as its parts (State, State code, number, year),
     * the same way eCourts asks for an advocate's bar code.
     */
    public function up(): void
    {
        Schema::table('advocate_profiles', function (Blueprint $table) {
            $table->string('bar_state', 100)->nullable()->after('bar_council_registration_number');
            $table->string('bar_state_code', 10)->nullable()->after('bar_state');
            $table->string('bar_number', 20)->nullable()->after('bar_state_code');
        });

        // Split existing numbers written like "UP/1234/2017" or "MAH-1234-2017".
        DB::table('advocate_profiles')
            ->whereNotNull('bar_council_registration_number')
            ->where('bar_council_registration_number', '!=', '')
            ->orderBy('id')
            ->get(['id', 'bar_council_registration_number', 'bar_registration_year'])
            ->each(function (object $profile): void {
                if (! preg_match('/^\s*([A-Za-z.]{1,10})\s*[\/\-\s]\s*([0-9A-Za-z]{1,20})\s*[\/\-\s]\s*((?:19|20)\d{2})\s*$/', $profile->bar_council_registration_number, $parts)) {
                    return;
                }

                DB::table('advocate_profiles')->where('id', $profile->id)->update([
                    'bar_state_code' => strtoupper(str_replace('.', '', $parts[1])),
                    'bar_number' => strtoupper($parts[2]),
                    'bar_registration_year' => $profile->bar_registration_year ?: (int) $parts[3],
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('advocate_profiles', function (Blueprint $table) {
            $table->dropColumn(['bar_state', 'bar_state_code', 'bar_number']);
        });
    }
};
