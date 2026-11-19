<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $tables = [
        'clients',
        'legal_cases',
        'case_parties',
        'court_hearings',
        'client_activities',
        'invoices',
        'payments',
        'tasks',
        'firm_profiles',
        'personal_profiles',
        'professional_profiles',
        'profile_contacts',
        'invoice_settings',
        'tds_deductions',
        'tds_follow_ups',
        'professional_tax_records',
    ];

    public function up(): void
    {
        $adminId = DB::table('users')
            ->where('is_admin', true)
            ->value('id');

        if (!$adminId) {
            throw new RuntimeException(
                'Ownership migration stopped: administrator account not found.'
            );
        }

        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            if (!Schema::hasColumn($tableName, 'user_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->nullable();
                    $table->index('user_id');
                });
            }

            DB::table($tableName)
                ->whereNull('user_id')
                ->update(['user_id' => $adminId]);
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            if (
                Schema::hasTable($tableName) &&
                Schema::hasColumn($tableName, 'user_id')
            ) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropIndex(['user_id']);
                    $table->dropColumn('user_id');
                });
            }
        }
    }
};