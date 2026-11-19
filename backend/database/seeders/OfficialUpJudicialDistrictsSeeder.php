<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OfficialUpJudicialDistrictsSeeder extends Seeder
{
    public function run(): void
    {
        $state = DB::table('states')
            ->where('name', 'Uttar Pradesh')
            ->first();

        if (!$state) {
            throw new RuntimeException(
                'Uttar Pradesh was not found in the states catalogue. No district records were inserted.'
            );
        }

        $directoryUrl = 'https://ecourts.gov.in/ecourts2.0/?p=dist_court/up';

        $districts = [
            ['Ghaziabad', 'https://ghaziabad.dcourts.gov.in/'],
            ['Allahabad', 'https://prayagraj.dcourts.gov.in/'],
            ['Faizabad', 'https://ayodhya.dcourts.gov.in/'],
            ['Ghazipur', 'https://ghazipur.dcourts.gov.in/'],
            ['Gorakhpur', 'https://gorakhpur.dcourts.gov.in/'],
            ['Kannauj', 'https://kannauj.dcourts.gov.in/'],
            ['Lucknow', 'https://lucknow.dcourts.gov.in/'],
            ['Varanasi', 'https://varanasi.dcourts.gov.in/'],
            ['Jaunpur', 'https://jaunpur.dcourts.gov.in/'],
            ['Bareilly', 'https://bareilly.dcourts.gov.in/'],
            ['Muzaffarnagar', 'https://muzaffarnagar.dcourts.gov.in/'],
            ['Meerut', 'https://meerut.dcourts.gov.in/'],
            ['Agra', 'https://agra.dcourts.gov.in/'],
            ['Sitapur', 'https://sitapur.dcourts.gov.in/'],
            ['Aligarh', 'https://aligarh.dcourts.gov.in/'],
            ['Ambedkar Nagar', 'https://ambedkarnagar.dcourts.gov.in/'],
            ['Auraiya', 'https://auraiya.dcourts.gov.in/'],
            ['Azamgarh', 'https://azamgarh.dcourts.gov.in/'],
            ['Bagpat', 'https://baghpat.dcourts.gov.in/'],
            ['Bahraich', 'https://bahraich.dcourts.gov.in/'],
            ['Ballia', 'https://ballia.dcourts.gov.in/'],
            ['Balrampur', 'https://balrampurup.dcourts.gov.in/'],
            ['Banda', 'https://banda.dcourts.gov.in/'],
            ['Barabanki', 'https://barabanki.dcourts.gov.in/'],
            ['Basti', 'https://basti.dcourts.gov.in/'],
            ['Bijnor', 'https://bijnor.dcourts.gov.in/'],
            ['Budaun', 'https://budaun.dcourts.gov.in/'],
            ['Bulandshahar', 'https://bulandshahar.dcourts.gov.in/'],
            ['Chandauli', 'https://chandauli.dcourts.gov.in/'],
            ['Chitrakoot', 'https://chitrakoot.dcourts.gov.in/'],
            ['Deoria', 'https://deoria.dcourts.gov.in/'],
            ['Etah', 'https://etah.dcourts.gov.in/'],
            ['Etawah', 'https://etawah.dcourts.gov.in/'],
            ['Farrukhabad', 'https://farrukhabad.dcourts.gov.in/'],
            ['Fatehpur', 'https://fatehpur.dcourts.gov.in/'],
            ['Firozabad', 'https://firozabad.dcourts.gov.in/'],
            ['Gautam Buddha Nagar', 'https://gbnagar.dcourts.gov.in/'],
            ['Gonda', 'https://gonda.dcourts.gov.in/'],
            ['Hamirpur UP', 'https://hamirpurup.dcourts.gov.in/'],
            ['Hardoi', 'https://hardoi.dcourts.gov.in/'],
            ['Hathras', 'https://hathras.dcourts.gov.in/'],
            ['Jalaun', 'https://jalaun.dcourts.gov.in/'],
            ['Jhansi', 'https://jhansi.dcourts.gov.in/'],
            ['Jyotiba Phule Nagar', 'https://amroha.dcourts.gov.in/'],
            ['Kanpur Dehat', 'https://kanpurdehat.dcourts.gov.in/'],
            ['Kanpur Nagar', 'https://kanpurnagar.dcourts.gov.in/'],
            ['Kanshiramnagar', 'https://kasganj.dcourts.gov.in/'],
            ['Kaushambi', 'https://kaushambi.dcourts.gov.in/'],
            ['Kheri', 'https://kheri.dcourts.gov.in/'],
            ['Kushinagar', 'https://kushinagar.dcourts.gov.in/'],
            ['Lalitpur', 'https://lalitpur.dcourts.gov.in/'],
            ['Maharajganj', 'https://maharajganj.dcourts.gov.in/'],
            ['Mahoba', 'https://mahoba.dcourts.gov.in/'],
            ['Mathura', 'https://mathura.dcourts.gov.in/'],
            ['Mau', 'https://mau.dcourts.gov.in/'],
            ['Mirzapur', 'https://mirzapur.dcourts.gov.in/'],
            ['Moradabad', 'https://moradabad.dcourts.gov.in/'],
            ['Pilibhit', 'https://pilibhit.dcourts.gov.in/'],
            ['RaeBareli', 'https://raebareli.dcourts.gov.in/'],
            ['Rampur', 'https://rampur.dcourts.gov.in/'],
            ['Sant Kabir Nagar', 'https://santkabirnagar.dcourts.gov.in/'],
            ['Bhadohi', 'https://bhadohi.dcourts.gov.in/'],
            ['Shahjahanpur', 'https://shahjahanpur.dcourts.gov.in/'],
            ['Shravasti', 'https://shravasti.dcourts.gov.in/'],
            ['Siddharthnagar', 'https://siddharthnagar.dcourts.gov.in/'],
            ['Sonbhadra', 'https://sonbhadra.dcourts.gov.in/'],
            ['Sultanpur', 'https://sultanpur.dcourts.gov.in/'],
            ['Unnao', 'https://unnao.dcourts.gov.in/'],
            ['Mainpuri', 'https://mainpuri.dcourts.gov.in/'],
            ['Saharanpur', 'https://saharanpur.dcourts.gov.in/'],
            ['Hapur', 'https://hapur.dcourts.gov.in/'],
            ['Sambhal', 'https://sambhal.dcourts.gov.in/'],
            ['Shamli', 'https://shamli.dcourts.gov.in/'],
            ['Amethi', $directoryUrl],
            ['Pratapgarh', 'https://pratapgarhup.dcourts.gov.in/'],
        ];

        $now = now();
        $inserted = 0;
        $updated = 0;

        DB::transaction(function () use (
            $state,
            $districts,
            $directoryUrl,
            $now,
            &$inserted,
            &$updated
        ) {
            foreach ($districts as [$name, $url]) {
                $existing = DB::table('judicial_districts')
                    ->where('state_id', $state->id)
                    ->where('district_name', $name)
                    ->first();

                $values = [
                    'court_id' => null,
                    'district_code' => null,
                    'is_active' => true,
                    'source_url' => $url ?: $directoryUrl,
                    'source_name' => 'eCourts District Courts Portal',
                    'source_status' => 'verified',
                    'last_verified_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];

                if ($existing) {
                    DB::table('judicial_districts')
                        ->where('id', $existing->id)
                        ->update($values);

                    $updated++;
                } else {
                    DB::table('judicial_districts')->insert(
                        array_merge($values, [
                            'state_id' => $state->id,
                            'district_name' => $name,
                            'created_at' => $now,
                        ])
                    );

                    $inserted++;
                }
            }
        });

        $this->command->info('Uttar Pradesh official eCourts district catalogue processed.');
        $this->command->info('Directory entries: ' . count($districts));
        $this->command->info('New entries: ' . $inserted);
        $this->command->info('Updated existing entries: ' . $updated);
        $this->command->info('Source: ' . $directoryUrl);
    }
}