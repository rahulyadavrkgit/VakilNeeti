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
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_type', 20)->default('individual');
        });

        if (Schema::hasTable('firm_profiles') && Schema::hasTable('advocate_profiles')) {
            DB::table('users')
                ->whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('advocate_profiles')
                        ->whereColumn('advocate_profiles.user_id', 'users.id');
                })
                ->whereExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('firm_profiles')
                        ->whereColumn('firm_profiles.user_id', 'users.id');
                })
                ->when(Schema::hasColumn('users', 'is_admin'), fn ($query) => $query->where('is_admin', false))
                ->update(['account_type' => 'company']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('account_type');
        });
    }
};
