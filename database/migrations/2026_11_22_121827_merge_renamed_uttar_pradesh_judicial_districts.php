<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Uttar Pradesh districts were seeded under their eCourts court names, and the LGD import
 * then added the official names as separate rows. This keeps one row per district: the
 * original row (which other tables link to) takes the official LGD name, links pointing at
 * the duplicate move to it, and the duplicate is deleted.
 */
return new class extends Migration
{
    /**
     * Court / older name => current official name (LGD).
     *
     * @var array<string, string>
     */
    private const RENAMES = [
        'Allahabad' => 'Prayagraj',
        'Faizabad' => 'Ayodhya',
        'Jyotiba Phule Nagar' => 'Amroha',
        'Kanshiramnagar' => 'Kasganj',
        'Bagpat' => 'Baghpat',
        'Barabanki' => 'Bara Banki',
        'Bulandshahar' => 'Bulandshahr',
        'Hamirpur UP' => 'Hamirpur',
        'Maharajganj' => 'Mahrajganj',
        'RaeBareli' => 'Rae Bareli',
        'Shravasti' => 'Shrawasti',
    ];

    /**
     * Link tables with a unique key on (partner column, judicial_district_id).
     *
     * @var array<string, string>
     */
    private const UNIQUE_LINK_TABLES = [
        'court_establishment_judicial_district' => 'court_establishment_id',
        'firm_practice_districts' => 'firm_practice_state_id',
    ];

    /**
     * Link tables without a unique key on judicial_district_id.
     *
     * @var list<string>
     */
    private const PLAIN_LINK_TABLES = [
        'institution_jurisdictions',
        'bar_associations',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('states') || ! Schema::hasTable('judicial_districts')) {
            return;
        }

        $stateId = DB::table('states')->where('name', 'Uttar Pradesh')->value('id');

        if (! $stateId) {
            return;
        }

        DB::transaction(function () use ($stateId): void {
            foreach (self::RENAMES as $oldName => $officialName) {
                $this->mergeDistrict($stateId, $oldName, $officialName);
                $this->renameStoredDistrictNames($stateId, $oldName, $officialName);
            }
        });
    }

    /**
     * Renames are data corrections, so they are not reversed.
     */
    public function down(): void
    {
        //
    }

    private function mergeDistrict(int $stateId, string $oldName, string $officialName): void
    {
        $original = DB::table('judicial_districts')
            ->where('state_id', $stateId)
            ->where('district_name', $oldName)
            ->first();

        if (! $original) {
            return;
        }

        $duplicate = DB::table('judicial_districts')
            ->where('state_id', $stateId)
            ->where('district_name', $officialName)
            ->first();

        $updates = ['district_name' => $officialName, 'updated_at' => now()];

        if ($duplicate) {
            $this->moveLinks($duplicate->id, $original->id);

            if (blank($original->district_code) && filled($duplicate->district_code)) {
                $updates['district_code'] = $duplicate->district_code;
            }

            DB::table('judicial_districts')->where('id', $duplicate->id)->delete();
        }

        DB::table('judicial_districts')->where('id', $original->id)->update($updates);
    }

    private function moveLinks(int $fromDistrictId, int $toDistrictId): void
    {
        foreach (self::UNIQUE_LINK_TABLES as $table => $partnerColumn) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $alreadyLinkedPartners = DB::table($table)
                ->where('judicial_district_id', $toDistrictId)
                ->pluck($partnerColumn);

            DB::table($table)
                ->where('judicial_district_id', $fromDistrictId)
                ->whereIn($partnerColumn, $alreadyLinkedPartners)
                ->delete();

            DB::table($table)
                ->where('judicial_district_id', $fromDistrictId)
                ->update(['judicial_district_id' => $toDistrictId]);
        }

        foreach (self::PLAIN_LINK_TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'judicial_district_id')) {
                DB::table($table)
                    ->where('judicial_district_id', $fromDistrictId)
                    ->update(['judicial_district_id' => $toDistrictId]);
            }
        }
    }

    /**
     * Keep district names stored as text in step with the catalogue.
     */
    private function renameStoredDistrictNames(int $stateId, string $oldName, string $officialName): void
    {
        if (Schema::hasTable('court_complexes')) {
            DB::table('court_complexes')
                ->where('state_id', $stateId)
                ->where('judicial_district_name', $oldName)
                ->update(['judicial_district_name' => $officialName]);
        }

        if (Schema::hasTable('advocate_profiles')) {
            DB::table('advocate_profiles')
                ->where('region', 'Uttar Pradesh')
                ->where('district', $oldName)
                ->update(['district' => $officialName]);
        }

        if (Schema::hasTable('firm_profiles')) {
            DB::table('firm_profiles')
                ->where('state', 'Uttar Pradesh')
                ->where('district', $oldName)
                ->update(['district' => $officialName]);
        }
    }
};
