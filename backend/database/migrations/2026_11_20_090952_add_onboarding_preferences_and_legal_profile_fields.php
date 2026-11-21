<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('account_role', 40)->nullable();
            $table->json('onboarding_features')->nullable();
            $table->text('onboarding_other')->nullable();
        });

        DB::table('users')
            ->whereNull('account_role')
            ->update([
                'account_role' => DB::raw(
                    "CASE WHEN account_type = 'company' THEN 'law_firm' ELSE 'independent_practitioner' END"
                ),
            ]);

        Schema::table('advocate_profiles', function (Blueprint $table): void {
            $table->string('district', 100)->nullable();
            $table->string('state_code', 15)->nullable();
            $table->unsignedSmallInteger('bar_registration_year')->nullable();
            $table->string('photo_path')->nullable()->change();
            $table->string('bar_council_registration_number', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('advocate_profiles')
            ->whereNull('photo_path')
            ->update(['photo_path' => '']);

        DB::table('advocate_profiles')
            ->whereNull('bar_council_registration_number')
            ->update(['bar_council_registration_number' => '']);

        Schema::table('advocate_profiles', function (Blueprint $table): void {
            $table->string('photo_path')->nullable(false)->change();
            $table->string('bar_council_registration_number', 100)->nullable(false)->change();
            $table->dropColumn(['district', 'state_code', 'bar_registration_year']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['account_role', 'onboarding_features', 'onboarding_other']);
        });
    }
};
