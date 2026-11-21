<?php

namespace Database\Seeders;

use App\Models\InstitutionCategory;
use Illuminate\Database\Seeder;

class InstitutionCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'category_key' => 'constitutional_courts',
                'name' => 'Constitutional Courts',
                'parent_category_key' => null,
                'description' => 'Supreme Court and High Courts.',
                'sort_order' => 1,
            ],
            [
                'category_key' => 'supreme_court',
                'name' => 'Supreme Court of India',
                'parent_category_key' => 'constitutional_courts',
                'description' => 'The apex court of India.',
                'sort_order' => 1,
            ],
            [
                'category_key' => 'high_courts',
                'name' => 'High Courts',
                'parent_category_key' => 'constitutional_courts',
                'description' => 'High Courts established for States and territories.',
                'sort_order' => 2,
            ],
            [
                'category_key' => 'district_subordinate_courts',
                'name' => 'District and Subordinate Courts',
                'parent_category_key' => null,
                'description' => 'District courts and subordinate judicial establishments.',
                'sort_order' => 2,
            ],
            [
                'category_key' => 'district_courts',
                'name' => 'District Courts',
                'parent_category_key' => 'district_subordinate_courts',
                'description' => 'District-level judicial courts and establishments.',
                'sort_order' => 1,
            ],
            [
                'category_key' => 'subordinate_courts',
                'name' => 'Subordinate Courts',
                'parent_category_key' => 'district_subordinate_courts',
                'description' => 'Civil and criminal courts subordinate to district courts.',
                'sort_order' => 2,
            ],
            [
                'category_key' => 'special_courts',
                'name' => 'Special Courts',
                'parent_category_key' => null,
                'description' => 'Courts designated under specific laws or for specified case categories.',
                'sort_order' => 3,
            ],
            [
                'category_key' => 'tribunals',
                'name' => 'Tribunals',
                'parent_category_key' => null,
                'description' => 'Statutory tribunals exercising specialised jurisdiction.',
                'sort_order' => 4,
            ],
            [
                'category_key' => 'appellate_tribunals',
                'name' => 'Appellate Tribunals',
                'parent_category_key' => 'tribunals',
                'description' => 'Tribunals exercising appellate jurisdiction.',
                'sort_order' => 1,
            ],
            [
                'category_key' => 'administrative_tribunals',
                'name' => 'Administrative Tribunals',
                'parent_category_key' => 'tribunals',
                'description' => 'Tribunals dealing with specified administrative matters.',
                'sort_order' => 2,
            ],
            [
                'category_key' => 'tax_tribunals',
                'name' => 'Tax Tribunals',
                'parent_category_key' => 'tribunals',
                'description' => 'Tribunals dealing with direct and indirect tax matters.',
                'sort_order' => 3,
            ],
            [
                'category_key' => 'company_law_tribunals',
                'name' => 'Company Law Tribunals',
                'parent_category_key' => 'tribunals',
                'description' => 'Company law and insolvency adjudicatory institutions.',
                'sort_order' => 4,
            ],
            [
                'category_key' => 'debt_recovery_tribunals',
                'name' => 'Debt Recovery Tribunals',
                'parent_category_key' => 'tribunals',
                'description' => 'Debt recovery adjudicatory institutions.',
                'sort_order' => 5,
            ],
            [
                'category_key' => 'consumer_commissions',
                'name' => 'Consumer Commissions',
                'parent_category_key' => null,
                'description' => 'National, State and District Consumer Disputes Redressal Commissions.',
                'sort_order' => 5,
            ],
            [
                'category_key' => 'family_courts',
                'name' => 'Family Courts',
                'parent_category_key' => null,
                'description' => 'Family Courts established under the Family Courts Act.',
                'sort_order' => 6,
            ],
            [
                'category_key' => 'labour_industrial_institutions',
                'name' => 'Labour and Industrial Adjudicatory Institutions',
                'parent_category_key' => null,
                'description' => 'Labour courts, industrial tribunals and related institutions.',
                'sort_order' => 7,
            ],
            [
                'category_key' => 'statutory_quasi_judicial',
                'name' => 'Statutory and Quasi-Judicial Authorities',
                'parent_category_key' => null,
                'description' => 'Statutory authorities exercising adjudicatory or quasi-judicial functions.',
                'sort_order' => 8,
            ],
            [
                'category_key' => 'other_judicial_institutions',
                'name' => 'Other Judicial Institutions',
                'parent_category_key' => null,
                'description' => 'Other officially recognised judicial or adjudicatory institutions.',
                'sort_order' => 9,
            ],
        ];

        foreach ($categories as $category) {
            InstitutionCategory::updateOrCreate(
                ['category_key' => $category['category_key']],
                array_merge($category, ['is_active' => true])
            );
        }
    }
}
