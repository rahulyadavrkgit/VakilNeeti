<?php

namespace App\Services;

use App\Models\CourtCode;
use App\Models\LegalCase;
use App\Models\State;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Works out which court and state a case belongs to.
 *
 * With a CNR (eCourts' 16-character number) it is exact: "UPJL010012342023" = state UP, district code JL,
 * court complex 01. The district's name is read from the court complex eCourts gives ("…, Jalaun, Uttar
 * Pradesh"), checked against the official judicial districts, and remembered for that code (court_codes),
 * so later cases with the same code are named too. Without a CNR (Supreme Court, tribunals, typed-in
 * cases) it uses what the advocate chose, or reads the court's name.
 */
class CourtIdentity
{
    public const LEVELS = [
        'supreme' => 'Supreme Court',
        'high' => 'High Court',
        'district' => 'District Court',
        'tribunal' => 'Tribunal / Commission',
    ];

    /**
     * High Courts known by a city's name rather than the state's.
     *
     * @var array<string, string>
     */
    private const HIGH_COURT_NAMES = [
        'allahabad' => 'UP', 'lucknow' => 'UP', 'bombay' => 'MH', 'nagpur' => 'MH', 'aurangabad' => 'MH',
        'calcutta' => 'WB', 'madras' => 'TN', 'madurai' => 'TN', 'gauhati' => 'AS', 'guwahati' => 'AS',
        'orissa' => 'OD', 'odisha' => 'OD', 'patna' => 'BR', 'punjab and haryana' => 'PB', 'punjab & haryana' => 'PB',
        'jammu' => 'JK', 'kashmir' => 'JK', 'delhi' => 'DL', 'jabalpur' => 'MP', 'indore' => 'MP', 'gwalior' => 'MP',
        'jodhpur' => 'RJ', 'jaipur' => 'RJ', 'dharwad' => 'KA', 'kalaburagi' => 'KA', 'ernakulam' => 'KL',
        'amaravati' => 'AP', 'hyderabad' => 'TS', 'nainital' => 'UK', 'shimla' => 'HP', 'ranchi' => 'JH',
        'bilaspur' => 'CG', 'cuttack' => 'OD', 'gangtok' => 'SK', 'agartala' => 'TR', 'imphal' => 'MN', 'shillong' => 'ML',
    ];

    /**
     * Short names advocates use for a state's High Court.
     *
     * @var array<string, string>
     */
    private const HIGH_COURT_SHORT = [
        'UP' => 'Allahabad HC', 'DL' => 'Delhi HC', 'MH' => 'Bombay HC', 'WB' => 'Calcutta HC', 'TN' => 'Madras HC',
        'AS' => 'Gauhati HC', 'OD' => 'Orissa HC', 'BR' => 'Patna HC', 'PB' => 'Punjab & Haryana HC',
        'HR' => 'Punjab & Haryana HC', 'CH' => 'Punjab & Haryana HC', 'JK' => 'J&K and Ladakh HC',
    ];

    private const TRIBUNAL_WORDS = ['tribunal', 'commission', 'nclt', 'nclat', 'itat', 'cestat', 'rera', 'appellate authority', 'forum'];

    /** @var array<string, list<string>> */
    private array $districtsByState = [];

    /**
     * Set state_code, court_level, court_code and district on the case (not saved here).
     */
    public function fill(LegalCase $case): void
    {
        $cnr = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $case->cnr_number));

        if (preg_match('/^([A-Z]{2})([A-Z]{2})(\d{2})\d{10}$/', $cnr, $parts)) {
            $this->fillFromCnr($case, $parts[1], $parts[2], $parts[3]);

            return;
        }

        $text = mb_strtolower(trim($case->court_name.' '.$case->court_complex));

        $case->court_level ??= match (true) {
            str_contains($text, 'supreme court') => 'supreme',
            str_contains($text, 'high court') => 'high',
            Str::contains($text, self::TRIBUNAL_WORDS) => 'tribunal',
            $text !== '' => 'district',
            default => null,
        };

        if ($case->court_level === 'supreme') {
            $case->state_code = null;
            $case->district = null;
            $case->court_code = 'SC';

            return;
        }

        $case->state_code ??= $case->court_level === 'high' ? $this->stateFromHighCourtName($text) : $this->stateFromText($text);

        if ($case->court_level === 'high' && $case->state_code) {
            $case->court_code ??= $case->state_code.'HC';
        }

        if ($case->court_level === 'district' && $case->state_code) {
            $case->district ??= $this->districtFromText($case->state_code, (string) $case->court_complex.', '.$case->court_name);
            $case->court_code ??= $case->district
                ? CourtCode::query()->where('state_code', $case->state_code)->where('district', $case->district)->value('code')
                : null;
        }
    }

    private function fillFromCnr(LegalCase $case, string $state, string $districtCode, string $complex): void
    {
        $prefix = $state.$districtCode;
        $level = $districtCode === 'HC' ? 'high' : 'district';

        $case->state_code = $state;
        $case->court_level = $level;
        $case->court_code = $prefix.$complex;

        if ($level === 'high') {
            $case->district = null;
            CourtCode::query()->firstOrCreate(['code' => $prefix], ['state_code' => $state, 'court_level' => 'high']);

            return;
        }

        $learned = $this->districtFromText($state, (string) $case->court_complex.', '.$case->court_name);

        if ($learned) {
            CourtCode::query()->firstOrCreate(['code' => $prefix], ['state_code' => $state, 'court_level' => 'district', 'district' => $learned]);
        }

        $case->district = CourtCode::query()->where('code', $prefix)->value('district') ?? $learned ?? $case->district;
    }

    /**
     * "Supreme Court", "Allahabad HC", "Jalaun, UP", "Central, Delhi"; null when nothing is known.
     */
    public static function label(?string $level, ?string $state, ?string $district): ?string
    {
        return match ($level) {
            'supreme' => 'Supreme Court',
            'high' => self::HIGH_COURT_SHORT[$state] ?? (self::stateName($state) ? self::stateName($state).' HC' : 'High Court'),
            'district' => $district ? $district.', '.self::stateShort($state) : ($state ? self::stateShort($state).' district court' : null),
            'tribunal' => 'Tribunal'.($state ? ', '.self::stateShort($state) : ''),
            default => null,
        };
    }

    /**
     * A court's full name when only its level and State are known: "Supreme Court of India", "Allahabad High Court".
     */
    public static function fullName(?string $level, ?string $state): ?string
    {
        return match ($level) {
            'supreme' => 'Supreme Court of India',
            'high' => isset(self::HIGH_COURT_SHORT[$state])
                ? str_replace(' HC', ' High Court', self::HIGH_COURT_SHORT[$state])
                : (self::stateName($state) ? 'High Court of '.self::stateName($state) : 'High Court'),
            default => null,
        };
    }

    public static function stateName(?string $state): ?string
    {
        if (! $state) {
            return null;
        }

        $names = array_flip(State::INDIAN_STATES + State::INDIAN_UNION_TERRITORIES);

        return $names['IN-'.$state] ?? null;
    }

    /**
     * "UP", "MH"… but "Delhi" spelled out, which reads better than "DL".
     */
    public static function stateShort(?string $state): string
    {
        return $state === 'DL' ? 'Delhi' : (string) $state;
    }

    /**
     * States/UTs as code => name, for pickers.
     *
     * @return array<string, string>
     */
    public static function states(): array
    {
        $states = [];

        foreach (State::INDIAN_STATES + State::INDIAN_UNION_TERRITORIES as $name => $code) {
            $states[substr($code, 3)] = $name;
        }

        asort($states);

        return $states;
    }

    private function stateFromHighCourtName(string $text): ?string
    {
        foreach (self::HIGH_COURT_NAMES as $word => $state) {
            if (str_contains($text, $word)) {
                return $state;
            }
        }

        return $this->stateFromText($text);
    }

    private function stateFromText(string $text): ?string
    {
        foreach (self::states() as $code => $name) {
            if (str_contains($text, mb_strtolower($name))) {
                return $code;
            }
        }

        return null;
    }

    /**
     * The official judicial district named in a court's text ("…, South-East, Saket, Delhi" → "South East").
     */
    public function districtFromText(string $state, string $text): ?string
    {
        $districts = $this->districtsByState[$state] ??= DB::table('judicial_districts')
            ->join('states', 'states.id', '=', 'judicial_districts.state_id')
            ->where('states.code', 'IN-'.$state)
            ->whereNull('judicial_districts.deleted_at')
            ->pluck('judicial_districts.district_name')
            ->all();

        $plain = fn (string $value): string => (string) preg_replace('/[^a-z]/', '', mb_strtolower($value));
        $stateName = $plain((string) self::stateName($state));
        // eCourts writes the district last, before the state ("…, Rouse Avenue, New Delhi, Central"), so read from the end.
        $segments = array_reverse(array_values(array_filter(array_map($plain, preg_split('/,|\s-\s/', $text) ?: []), fn (string $segment) => $segment !== '' && $segment !== $stateName)));

        // Exact first, then a near spelling ("Gautam Buddha Nagar" / "Gautam Buddh Nagar").
        foreach ([0, 2] as $tolerance) {
            foreach ($segments as $segment) {
                $matches = [];
                foreach ($districts as $district) {
                    $name = $plain($district);

                    if ($segment === $name || ($tolerance && strlen($name) >= 6 && levenshtein($segment, $name) <= $tolerance)) {
                        $matches[$district] = levenshtein($segment, $name);
                    }
                }

                if ($matches !== []) {
                    $closest = array_keys($matches, min($matches), true);

                    return count($closest) === 1 ? $closest[0] : null;
                }
            }
        }

        return null;
    }
}
