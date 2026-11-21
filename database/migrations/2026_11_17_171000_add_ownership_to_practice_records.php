<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'firm_practice_states',
        'firm_practice_districts',
        'cause_list_appearances',
    ];

    public function up(): void
    {
        $adminId = DB::table('users')
            ->where('is_admin', true)
            ->value('id');

        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            if (! Schema::hasColumn($tableName, 'user_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->nullable();
                    $table->index('user_id');
                });
            }

            $hasUnownedRecords = DB::table($tableName)
                ->whereNull('user_id')
                ->exists();

            if (! $hasUnownedRecords) {
                continue;
            }

            // Existing records must be assigned to an owner; a fresh install has none to backfill.
            if (! $adminId) {
                throw new RuntimeException('Administrator account not found.');
            }

            DB::table($tableName)
                ->whereNull('user_id')
                ->update(['user_id' => $adminId]);
        }
    }

    public function down(): void
    {
        // Intentionally preserve ownership columns and existing data.
    }
};
