<?php

namespace App\Console\Commands;

use App\Models\PoliceDistrict;
use App\Models\PoliceRange;
use App\Models\PoliceStation;
use App\Models\PoliceSubDivision;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportVerifiedPoliceDirectory extends Command
{
    protected $signature = 'police:import-verified {file : Path to the verified CSV file}';
    protected $description = 'Safely import or update verified official police ranges, districts, sub-divisions, and stations/specialist units.';

    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return self::FAILURE;
        }

        $handle = fopen($filePath, 'r');
        $headers = fgetcsv($handle);

        if (!$headers) {
            $this->error('CSV file is empty or invalid.');
            fclose($handle);
            return self::FAILURE;
        }

        $headers = array_map(fn($h) => trim((string) preg_replace('/^\xEF\xBB\xBF/', '', $h)), $headers);
        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) {
                continue;
            }

            $data = array_combine($headers, array_map('trim', $row));
            $state = DB::table('states')
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($data['state_name'] ?? '')])
                ->first();

            if (!$state) {
                $this->warn("Skipping row - State not found: " . ($data['state_name'] ?? 'EMPTY'));
                continue;
            }

            $verifiedAt = !empty($data['last_verified_at']) ? $data['last_verified_at'] : now();
            $status = !empty($data['source_status']) ? $data['source_status'] : 'verified';
            $sourceName = $data['source_name'] ?? 'Official State Police Portal';
            $sourceUrl = $data['source_url'] ?? null;

            $rangeId = null;
            if (!empty($data['range_name'])) {
                $range = PoliceRange::updateOrCreate(
                    ['state_id' => $state->id, 'range_name' => $data['range_name']],
                    [
                        'source_name' => $sourceName,
                        'source_url' => $sourceUrl,
                        'source_status' => $status,
                        'last_verified_at' => $verifiedAt,
                        'is_active' => true,
                    ]
                );
                $rangeId = $range->id;
            }

            $isSpecialist = filter_var($data['is_specialist_unit'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $districtId = null;

            if (!$isSpecialist && !empty($data['police_district_name'])) {
                $district = PoliceDistrict::updateOrCreate(
                    ['state_id' => $state->id, 'district_name' => $data['police_district_name']],
                    [
                        'police_range_id' => $rangeId,
                        'range_name' => $data['range_name'] ?? null,
                        'source_name' => $sourceName,
                        'source_url' => $sourceUrl,
                        'source_status' => $status,
                        'last_verified_at' => $verifiedAt,
                        'is_active' => true,
                    ]
                );
                $districtId = $district->id;
            }

            $subDivisionId = null;
            if (!$isSpecialist && !empty($data['sub_division_name'])) {
                $subDivision = PoliceSubDivision::updateOrCreate(
                    [
                        'state_id' => $state->id,
                        'police_district_id' => $districtId,
                        'sub_division_name' => $data['sub_division_name'],
                    ],
                    [
                        'source_name' => $sourceName,
                        'source_url' => $sourceUrl,
                        'source_status' => $status,
                        'last_verified_at' => $verifiedAt,
                        'is_active' => true,
                    ]
                );
                $subDivisionId = $subDivision->id;
            }

            if (!empty($data['station_name'])) {
                PoliceStation::updateOrCreate(
                    [
                        'state_id' => $state->id,
                        'police_district_id' => $districtId,
                        'station_name' => $data['station_name'],
                    ],
                    [
                        'police_sub_division_id' => $subDivisionId,
                        'station_code' => $data['station_code'] ?: null,
                        'station_type' => $data['station_type'] ?: null,
                        'is_specialist_unit' => $isSpecialist,
                        'specialist_unit_category' => $isSpecialist ? ($data['specialist_unit_category'] ?: null) : null,
                        'address' => $data['address'] ?: null,
                        'city' => $data['city'] ?: null,
                        'locality' => $data['locality'] ?: null,
                        'pincode' => $data['pincode'] ?: null,
                        'phone' => $data['phone'] ?: null,
                        'control_room_phone' => $data['control_room_phone'] ?: null,
                        'email' => $data['email'] ?: null,
                        'sho_name' => $data['sho_name'] ?: null,
                        'sho_designation' => $data['sho_designation'] ?: null,
                        'jurisdiction_area' => $data['jurisdiction_area'] ?: null,
                        'official_url' => $data['official_url'] ?: null,
                        'citizen_portal_url' => $data['citizen_portal_url'] ?: null,
                        'source_name' => $sourceName,
                        'source_url' => $sourceUrl,
                        'source_status' => $status,
                        'last_verified_at' => $verifiedAt,
                        'is_active' => true,
                    ]
                );
                $count++;
            }
        }

        fclose($handle);
        $this->info("Successfully imported/updated {$count} verified police station/unit records.");
        return self::SUCCESS;
    }
}