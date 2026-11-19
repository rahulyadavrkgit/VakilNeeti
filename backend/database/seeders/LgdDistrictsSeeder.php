<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class LgdDistrictsSeeder extends Seeder
{
    public function run(): void
    {
        $path = storage_path('app/imports/lgd_districts.csv.csv');

        if (!is_file($path)) {
            $path = storage_path('app/imports/lgd_districts.csv');
        }

        if (!is_file($path)) {
            throw new RuntimeException('LGD CSV file not found.');
        }

        $stateColumns = Schema::getColumnListing('states');

        $stateNameColumn = null;

        foreach (['name', 'state_name', 'state_name_english'] as $candidate) {
            if (in_array($candidate, $stateColumns, true)) {
                $stateNameColumn = $candidate;
                break;
            }
        }

        if (!$stateNameColumn) {
            throw new RuntimeException(
                'Could not identify the state name column in states table.'
            );
        }

        $districtColumns = Schema::getColumnListing('judicial_districts');

        $requiredColumns = [
            'state_id',
            'court_id',
            'district_name',
            'district_code',
            'is_active',
            'source_url',
            'source_name',
            'source_status',
            'last_verified_at',
        ];

        foreach ($requiredColumns as $column) {
            if (!in_array($column, $districtColumns, true)) {
                throw new RuntimeException(
                    "Missing judicial_districts column: {$column}"
                );
            }
        }

        $normalise = static function ($value): string {
            $value = mb_strtolower(trim((string) $value), 'UTF-8');
            $value = preg_replace('/^the\s+/u', '', $value);
            $value = str_replace('&', ' and ', $value);
            $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value);
            return trim(preg_replace('/\s+/u', ' ', $value));
        };

        $states = DB::table('states')->get();

        $stateMap = [];

        foreach ($states as $state) {
            $name = $state->{$stateNameColumn};
            $stateMap[$normalise($name)] = $state->id;
        }

        $handle = fopen($path, 'r');

        if (!$handle) {
            throw new RuntimeException('Unable to open LGD CSV file.');
        }

        $headers = fgetcsv($handle);

        if (!$headers) {
            fclose($handle);
            throw new RuntimeException('CSV file has no header row.');
        }

        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        $headers = array_map('trim', $headers);

        $headerMap = array_flip($headers);

        $requiredHeaders = [
            'state_name_english',
            'district_code',
            'district_name_english',
        ];

        foreach ($requiredHeaders as $header) {
            if (!isset($headerMap[$header])) {
                fclose($handle);
                throw new RuntimeException(
                    "Missing CSV column: {$header}"
                );
            }
        }

        $existingRows = DB::table('judicial_districts')
            ->get([
                'id',
                'state_id',
                'district_name',
                'district_code',
                'deleted_at',
            ]);

        $existingMap = [];

        foreach ($existingRows as $existing) {
            $key = $existing->state_id . '|' .
                $normalise($existing->district_name);

            $existingMap[$key] = $existing;
        }

        $created = 0;
        $matched = 0;
        $duplicates = 0;
        $unmatchedStates = [];
        $seen = [];
        $rowNumber = 1;

        $sourceUrl = 'https://data.gov.in/resource/local-government-directory-lgd-districts';
        $sourceName = 'Local Government Directory (LGD) - Ministry of Panchayati Raj';

        DB::transaction(function () use (
            $handle,
            $headerMap,
            $stateMap,
            $normalise,
            $existingMap,
            &$created,
            &$matched,
            &$duplicates,
            &$unmatchedStates,
            &$seen,
            &$rowNumber,
            $sourceUrl,
            $sourceName
        ) {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if (count($row) !== count($headerMap)) {
                    continue;
                }

                $stateName = trim(
                    $row[$headerMap['state_name_english']] ?? ''
                );

                $districtName = trim(
                    $row[$headerMap['district_name_english']] ?? ''
                );

                $districtCode = trim(
                    $row[$headerMap['district_code']] ?? ''
                );

                if ($stateName === '' || $districtName === '') {
                    continue;
                }

                $stateKey = $normalise($stateName);

                if (!isset($stateMap[$stateKey])) {
                    $unmatchedStates[] = $stateName;
                    continue;
                }

                $stateId = $stateMap[$stateKey];

                $districtKey = $normalise($districtName);
                $key = $stateId . '|' . $districtKey;

                if (isset($seen[$key])) {
                    $duplicates++;
                    continue;
                }

                $seen[$key] = true;

                if (isset($existingMap[$key])) {
                    $existing = $existingMap[$key];

                    if ($existing->deleted_at !== null) {
                        $matched++;
                        continue;
                    }

                    if (
                        ($existing->district_code === null ||
                         $existing->district_code === '') &&
                        $districtCode !== ''
                    ) {
                        DB::table('judicial_districts')
                            ->where('id', $existing->id)
                            ->update([
                                'district_code' => $districtCode,
                                'updated_at' => now(),
                            ]);
                    }

                    $matched++;
                    continue;
                }

                DB::table('judicial_districts')->insert([
                    'state_id' => $stateId,
                    'court_id' => null,
                    'district_name' => $districtName,
                    'district_code' => $districtCode ?: null,
                    'is_active' => true,
                    'source_url' => $sourceUrl,
                    'source_name' => $sourceName,
                    'source_status' => 'verified',
                    'last_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $existingMap[$key] = (object) [
                    'id' => null,
                    'state_id' => $stateId,
                    'district_name' => $districtName,
                    'district_code' => $districtCode,
                    'deleted_at' => null,
                ];

                $created++;
            }
        });

        fclose($handle);

        if (!empty($unmatchedStates)) {
            throw new RuntimeException(
                'Unmatched state names: ' .
                implode(', ', array_unique($unmatchedStates))
            );
        }

        $this->command->info('LGD DISTRICT IMPORT COMPLETED');
        $this->command->info("New districts inserted: {$created}");
        $this->command->info("Existing districts matched: {$matched}");
        $this->command->info("Duplicate CSV rows skipped: {$duplicates}");
        $this->command->info(
            'States in database: ' . count($stateMap)
        );
    }
}