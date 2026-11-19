<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

        if (!$adminId) {
            throw new RuntimeException('Administrator account not found.');
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
        // Intentionally preserve ownership columns and existing data.
    }
};