<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Services\DataGovInService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Imports the cities and towns of India (urban local bodies) from the official Local
 * Government Directory on data.gov.in and links each one to its State and district.
 */
class ImportLgdCities extends Command
{
    protected $signature = 'locations:import-cities
        {--from-file : Import from the saved snapshot instead of calling data.gov.in}
        {--file= : Snapshot path (default: storage/app/imports/lgd_cities.json)}';

    protected $description = 'Import Indian cities and towns (LGD urban local bodies) with their State and district.';

    private const SUB_DISTRICTS_RESOURCE = '6be51a29-876a-403a-a6da-42fde795e751';

    private const LOCAL_BODIES_RESOURCE = '1a6c26ed-d67c-40ea-aa20-d38d35f341a5';

    private const LOCAL_BODIES_WITH_PIN_RESOURCE = '71818d1a-c114-46cb-aa9b-56ed70d4bc4a';

    /**
     * Urban local body types, largest first (used to pick one entry when names repeat).
     *
     * @var list<string>
     */
    private const URBAN_TYPES = [
        'Municipal Corporations',
        'NCT Municipal Council',
        'City Municipal Council',
        'Municipality',
        'Town Municipal Council',
        'Notified Area Council',
        'Town Panchayat',
    ];

    /**
     * Official short or alternative names mapped to the commonly used city name, with the
     * district to use when the official data does not link one (normalised name => [name, district]).
     *
     * @var array<string, array{string, string|null}>
     */
    private const NAME_ALIASES = [
        'bbmp' => ['Bengaluru', 'Bengaluru Urban'],
        'bkt' => ['Bakshi Ka Talab', 'Lucknow'],
        'morabi' => ['Morbi', 'Morbi'],
        'ahmadabad' => ['Ahmedabad', 'Ahmedabad'],
    ];

    public function handle(DataGovInService $dataGovIn): int
    {
        $snapshotPath = (string) ($this->option('file') ?: storage_path('app/imports/lgd_cities.json'));

        if ($this->option('from-file')) {
            if (! is_file($snapshotPath)) {
                $this->error("Snapshot not found: {$snapshotPath}");

                return self::FAILURE;
            }

            $data = json_decode((string) file_get_contents($snapshotPath), true, flags: JSON_THROW_ON_ERROR);
        } else {
            $data = $this->download($dataGovIn);
            @mkdir(dirname($snapshotPath), 0755, true);
            file_put_contents($snapshotPath, json_encode($data, JSON_UNESCAPED_UNICODE));
            $this->info("Snapshot saved to {$snapshotPath}");
        }

        [$saved, $withDistrict, $skippedStates] = $this->import($data);

        $this->info("Cities saved: {$saved} ({$withDistrict} linked to a district).");

        if ($skippedStates !== []) {
            $this->warn('Skipped unknown states: '.implode(', ', $skippedStates));
        }

        return self::SUCCESS;
    }

    /**
     * @return array{sub_districts: list<array<string, mixed>>, coverage: list<array<string, mixed>>, pin_codes: list<array<string, mixed>>}
     */
    private function download(DataGovInService $dataGovIn): array
    {
        $this->info('Downloading sub-districts…');
        $subDistricts = $dataGovIn->fetchAllRecords(self::SUB_DISTRICTS_RESOURCE);

        $coverage = [];
        foreach (self::URBAN_TYPES as $type) {
            $this->info("Downloading coverage for {$type}…");
            array_push($coverage, ...$dataGovIn->fetchAllRecords(self::LOCAL_BODIES_RESOURCE, ['localBodyTypeName' => $type]));
        }

        $this->info('Downloading PIN codes…');
        $pinCodes = $dataGovIn->fetchAllRecords(self::LOCAL_BODIES_WITH_PIN_RESOURCE);

        return ['sub_districts' => $subDistricts, 'coverage' => $coverage, 'pin_codes' => $pinCodes];
    }

    /**
     * @param  array{sub_districts: list<array<string, mixed>>, coverage: list<array<string, mixed>>, pin_codes: list<array<string, mixed>>}  $data
     * @return array{int, int, list<string>}
     */
    private function import(array $data): array
    {
        $stateIds = DB::table('states')->pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [$this->normalise($name) => (int) $id]);

        $districtsByCode = DB::table('judicial_districts')
            ->whereNotNull('district_code')
            ->get(['id', 'state_id', 'district_code', 'district_name'])
            ->keyBy(fn ($district) => (string) (int) $district->district_code);

        $districtCodeBySubDistrict = [];
        $districtCodeByPlaceName = [];

        foreach ($data['sub_districts'] as $row) {
            $districtCode = (string) (int) $row['district_code'];
            $districtCodeBySubDistrict[(string) (int) $row['subdistrict_code']] = $districtCode;
            $stateKey = $this->normalise((string) $row['state_name_english']);
            $districtCodeByPlaceName[$stateKey][$this->normalise((string) $row['subdistrict_name_english'])][$districtCode] = true;
            $districtCodeByPlaceName[$stateKey][$this->normalise((string) $row['district_name_english'])][$districtCode] = true;
        }

        $bodies = [];

        foreach ($data['coverage'] as $row) {
            $code = (int) $row['localBodyCode'];
            $bodies[$code] ??= [
                'name' => (string) $row['localBodyNameEnglish'],
                'type' => trim((string) $row['localBodyTypeName']),
                'state' => (string) $row['stateNameEnglish'],
                'pincode' => null,
                'district_codes' => [],
            ];

            $entityCode = (string) (int) $row['entityCode'];
            $districtCode = match (true) {
                $row['entityType'] === 'District' => $entityCode,
                str_starts_with((string) $row['entityType'], 'Sub') => $districtCodeBySubDistrict[$entityCode] ?? null,
                default => null,
            };

            if ($districtCode !== null) {
                $bodies[$code]['district_codes'][] = $districtCode;
            }
        }

        foreach ($data['pin_codes'] as $row) {
            $code = (int) $row['localBodyCode'];
            $bodies[$code] ??= [
                'name' => (string) $row['localBodyNameEnglish'],
                'type' => trim((string) $row['localBodyTypeName']),
                'state' => (string) $row['stateNameEnglish'],
                'pincode' => null,
                'district_codes' => [],
            ];
            $pincode = str_pad((string) $row['pincode'], 6, '0', STR_PAD_LEFT);
            $bodies[$code]['pincode'] = min(array_filter([$bodies[$code]['pincode'], $pincode]));
        }

        uasort($bodies, fn ($first, $second) => $this->typeRank($first['type']) <=> $this->typeRank($second['type']));

        $rows = [];
        $seen = [];
        $skippedStates = [];

        foreach ($bodies as $code => $body) {
            $stateKey = $this->normalise($body['state']);
            $stateId = $stateIds[$stateKey] ?? null;

            if (! $stateId) {
                $skippedStates[$body['state']] = true;

                continue;
            }

            $name = $this->cleanName($body['name']);
            [$name, $districtHint] = self::NAME_ALIASES[$this->normalise($name)] ?? [$name, null];
            $districtCode = $this->mostCommon($body['district_codes'])
                ?? $this->uniqueMatch($districtCodeByPlaceName[$stateKey][$this->normalise($name)] ?? [])
                ?? ($districtHint ? $this->uniqueMatch($districtCodeByPlaceName[$stateKey][$this->normalise($districtHint)] ?? []) : null);
            $district = $districtCode !== null ? $districtsByCode->get($districtCode) : null;
            $districtId = $district && (int) $district->state_id === $stateId ? (int) $district->id : null;

            $uniqueKey = $stateId.'|'.($districtId ?? 0).'|'.$this->normalise($name);

            if ($name === '' || isset($seen[$uniqueKey])) {
                continue;
            }

            $seen[$uniqueKey] = true;
            $rows[] = [
                'state_id' => $stateId,
                'judicial_district_id' => $districtId,
                'name' => $name,
                'lgd_code' => $code,
                'lgd_name' => $body['name'],
                'body_type' => $body['type'],
                'pincode' => $body['pincode'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::transaction(function () use ($rows): void {
            foreach (array_chunk($rows, 500) as $chunk) {
                City::query()->upsert(
                    $chunk,
                    ['lgd_code'],
                    ['state_id', 'judicial_district_id', 'name', 'lgd_name', 'body_type', 'pincode', 'is_active', 'updated_at']
                );
            }
        });

        $withDistrict = count(array_filter($rows, fn ($row) => $row['judicial_district_id'] !== null));

        return [count($rows), $withDistrict, array_keys($skippedStates)];
    }

    /**
     * "Municipal Committee Achabal" → "Achabal", "NAGPUR MUNICIPAL CORPORATION" → "Nagpur".
     */
    public function cleanName(string $name): string
    {
        $bodyWords = '(?:greater\s+)?(?:municipal\s+corporation(?:\s+of)?|municipal\s+corporations|municipal\s+committee|municipal\s+council|'
            .'municipal\s+co-?operation|municipal\s+board|city\s+corporation|mahanagara\s+palike|'
            .'city\s+municipal\s+council|town\s+municipal\s+council|nagar\s*palika\s+parishad|nagar\s*palika\s+nigam|nagar\s*palika,?|nagar\s*panchayat|'
            .'nagar\s+nigam|nagar\s+parishad|town\s+panchayat|notified\s+area\s+(?:council|committee)|municipality|corporation|'
            .'n\.?\s?p\.?\s?p\.?|n\.?\s?p\.?|m\.?\s?c\.?|m\.?\s?corp\.?|cmc|tmc|nac)';

        $clean = preg_replace('/\((?:[^)]*)\)/u', ' ', $name) ?? $name;
        $clean = preg_replace("/^\s*{$bodyWords}\s+/iu", '', $clean) ?? $clean;
        $clean = preg_replace("/\s+{$bodyWords}\s*$/iu", '', $clean) ?? $clean;
        $clean = trim(preg_replace('/\s+/u', ' ', $clean) ?? $clean, " \t.,-");

        if ($clean !== '' && $clean === mb_strtoupper($clean)) {
            $clean = Str::title(mb_strtolower($clean));
        }

        return $clean;
    }

    private function normalise(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/^the\s+/u', '', $value) ?? $value;
        $value = str_replace('&', ' and ', $value);
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    private function typeRank(string $type): int
    {
        $rank = array_search($type, self::URBAN_TYPES, true);

        return $rank === false ? count(self::URBAN_TYPES) : $rank;
    }

    /**
     * @param  list<string>  $codes
     */
    private function mostCommon(array $codes): ?string
    {
        if ($codes === []) {
            return null;
        }

        $counts = array_count_values($codes);
        arsort($counts);

        return (string) array_key_first($counts);
    }

    /**
     * @param  array<string, true>  $codes
     */
    private function uniqueMatch(array $codes): ?string
    {
        return count($codes) === 1 ? (string) array_key_first($codes) : null;
    }
}
