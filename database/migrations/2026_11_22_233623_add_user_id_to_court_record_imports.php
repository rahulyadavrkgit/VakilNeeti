<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Give each pasted court record an owner, so people only see their own imports.
     * Older rows take the owner of the case they were saved into; the rest stay
     * without an owner and are visible to admins only.
     */
    public function up(): void
    {
        Schema::table('court_record_imports', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->index(['user_id', 'created_at']);
        });

        DB::table('court_record_imports')
            ->whereNotNull('legal_case_id')
            ->orderBy('id')
            ->get(['id', 'legal_case_id'])
            ->each(function (object $import): void {
                $ownerId = DB::table('legal_cases')->where('id', $import->legal_case_id)->value('user_id');

                if ($ownerId) {
                    DB::table('court_record_imports')->where('id', $import->id)->update(['user_id' => $ownerId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('court_record_imports', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
