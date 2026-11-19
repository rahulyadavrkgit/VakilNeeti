<?php

namespace Database\Seeders;

use App\Models\Court;
use App\Models\CourtType;
use App\Models\InstitutionCategory;
use Illuminate\Database\Seeder;

class TribunalSeeder extends Seeder
{
    public function run(): void
    {
        $tribunalType = CourtType::where('slug', 'tribunal')->firstOrFail();

        $otherType = CourtType::where(
            'slug',
            'other-judicial-institution'
        )->firstOrFail();

        $source = 'https://www.doj.gov.in/static/uploads/2026/02/7d06f171d48ad0fb456482d62f3cb449.pdf';

        $records = [
            ['Industrial Tribunal constituted by the Central Government', 'tribunal', ['labour_industrial_institutions']],
            ['Income Tax Appellate Tribunal', 'tribunal', ['tax_tribunals', 'appellate_tribunals']],
            ['Customs, Excise and Service Tax Appellate Tribunal', 'tribunal', ['tax_tribunals', 'appellate_tribunals']],
            ['Appellate Tribunal under SAFEMA', 'tribunal', ['appellate_tribunals']],
            ['Central Administrative Tribunal', 'tribunal', ['administrative_tribunals']],
            ['State Administrative Tribunals', 'tribunal', ['administrative_tribunals']],
            ['Railway Claims Tribunal', 'tribunal', ['tribunals']],
            ['Securities Appellate Tribunal', 'tribunal', ['appellate_tribunals']],
            ['Debts Recovery Tribunal', 'tribunal', ['debt_recovery_tribunals']],
            ['Debts Recovery Appellate Tribunal', 'tribunal', ['debt_recovery_tribunals', 'appellate_tribunals']],
            ['Telecom Disputes Settlement and Appellate Tribunal', 'tribunal', ['appellate_tribunals']],
            ['National Company Law Appellate Tribunal', 'tribunal', ['company_law_tribunals', 'appellate_tribunals']],
            ['National Consumer Disputes Redressal Commission', 'other-judicial-institution', ['consumer_commissions']],
            ['Appellate Tribunal for Electricity', 'tribunal', ['appellate_tribunals']],
            ['Armed Forces Tribunal', 'tribunal', ['tribunals']],
            ['National Green Tribunal', 'tribunal', ['tribunals']],
        ];

        foreach ($records as [$name, $type, $categoryKeys]) {
            $courtType = $type === 'tribunal' ? $tribunalType : $otherType;

            $court = Court::withTrashed()->firstOrNew([
                'court_name' => $name,
            ]);

            $court->fill([
                'court_type' => $type === 'tribunal'
                    ? 'Tribunal'
                    : 'Other Judicial Institution',
                'court_level' => 'National',
                'country' => 'India',
                'description' => "Listed in the Department of Justice annexure concerning functioning tribunals and adjudicatory bodies dated 18 December 2025. Official source: {$source}",
                'is_active' => true,
                'state_id' => null,
                'court_type_id' => $courtType->id,
            ]);

            if ($court->exists && $court->trashed()) {
                $court->restore();
            }

            $court->save();

            foreach ($categoryKeys as $index => $key) {
                $category = InstitutionCategory::where(
                    'category_key',
                    $key
                )->first();

                if (!$category) {
                    $this->command->warn("Category not found: {$key}");
                    continue;
                }

                $court->institutionCategories()->syncWithoutDetaching([
                    $category->id => [
                        'is_primary' => $index === 0,
                    ],
                ]);
            }
        }

        $this->command->info('Official tribunal reference records seeded.');
    }
}
