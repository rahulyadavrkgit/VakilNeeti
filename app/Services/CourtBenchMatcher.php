<?php

namespace App\Services;

use App\Models\CourtBench;
use App\Models\LegalCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Finds the court room in the official court lists (court_benches) for a case, from what eCourts
 * records: the court's designation ("DISTRICT JUDGE - 04") and establishment ("District and Sessions
 * Judge, West, THC, Delhi"), or the judge's name. Its judge, room and VC link fill whatever the
 * case does not have itself. Only Delhi district courts can be matched (they list every room).
 */
class CourtBenchMatcher
{
    /**
     * Which district list an establishment belongs to; first match wins, so the more specific come first.
     *
     * @var array<string, string>
     */
    private const DISTRICT_PATTERNS = [
        '/\b(rac|rouse\s*avenue)\b/' => 'delhi-district-central-rouse-avenue-courts',
        '/south[\s-]*east/' => 'delhi-district-south-east',
        '/south[\s-]*west/' => 'delhi-district-south-west',
        '/north[\s-]*east/' => 'delhi-district-north-east',
        '/north[\s-]*west/' => 'delhi-district-north-west',
        '/new\s*delhi/' => 'delhi-district-new-delhi',
        '/\bshahdara\b/' => 'delhi-district-shahdara',
        '/\bcentral\b/' => 'delhi-district-central',
        '/\bwest\b/' => 'delhi-district-west',
        '/\beast\b/' => 'delhi-district-east',
        '/\bnorth\b/' => 'delhi-district-north',
        '/\bsouth\b/' => 'delhi-district-south',
    ];

    /**
     * Long designations and their short forms, so "DISTRICT JUDGE - 04" and "DJ-04" read the same.
     * Longer phrases come first.
     *
     * @var array<string, string>
     */
    private const PHRASES = [
        'additional chief judicial magistrate' => 'acjm',
        'additional chief metropolitan magistrate' => 'acjm',
        'chief judicial magistrate' => 'cjm',
        'chief metropolitan magistrate' => 'cjm',
        'judicial magistrate first class' => 'jmfc',
        'metropolitan magistrate' => 'jmfc',
        'additional sessions judge' => 'asj',
        'additional session judge' => 'asj',
        'additional district judge' => 'dj',
        'principal district & sessions judge' => 'pdsj',
        'principal district and sessions judge' => 'pdsj',
        'district judge' => 'dj',
        'senior civil judge' => 'scj',
        'additional senior civil judge' => 'ascj',
        'additional civil judge' => 'acj',
        'civil judge' => 'cj',
        'additional rent controller' => 'arc',
        'rent controller' => 'rc',
        'guardian judge' => 'gj',
        'judge small cause court' => 'jscc',
        'special judge' => 'sj',
        'spl. judge' => 'sj',
        'spl judge' => 'sj',
        'presiding officer' => 'po',
        'principal judge' => 'pj',
        'family court' => 'fc',
        'commercial courts' => 'comm',
        'commercial court' => 'comm',
        'commercial' => 'comm',
        'digital traffic court' => 'dtc',
        'mahila court' => 'mahila',
        'fast track special court' => 'ftsc',
        'fast track court' => 'ftc',
    ];

    /**
     * @var list<string>
     */
    private const IGNORED_WORDS = ['ld', 'cum', 'court', 'courts', 'the', 'of', 'and', 'no', 'room', 'judge'];

    /**
     * Benches per court list, loaded once per request.
     *
     * @var array<string, Collection<int, CourtBench>>
     */
    private array $benches = [];

    /**
     * Each bench's designation tokens, worked out once per request.
     *
     * @var array<int, array{words: list<string>, numbers: list<string>}>
     */
    private array $benchTokens = [];

    /**
     * Answers already found this request: pages show the same court on many rows.
     *
     * @var array<string, CourtBench|null>
     */
    private array $found = [];

    /**
     * The official court list a case's court belongs to, or null outside the Delhi district courts.
     */
    public function courtKeyFor(?string $courtName, ?string $courtComplex): ?string
    {
        $text = mb_strtolower(trim($courtComplex.' '.$courtName));

        if ($text === '' || str_contains($text, 'high court') || str_contains($text, 'supreme court')
            || ! preg_match('/\b(delhi|thc|tis hazari|kkd|karkardooma|saket|rohini|dwarka|phc|patiala|rouse|rac)\b/', $text)) {
            return null;
        }

        foreach (self::DISTRICT_PATTERNS as $pattern => $courtKey) {
            if (preg_match($pattern, $text)) {
                return $courtKey;
            }
        }

        return null;
    }

    /**
     * The court room for a case: by designation first, then by the judge's name. Null unless exactly one fits.
     */
    public function find(?string $courtName, ?string $courtComplex, ?string $judgeName = null): ?CourtBench
    {
        $courtKey = $this->courtKeyFor($courtName, $courtComplex);

        if (! $courtKey) {
            return null;
        }

        $answerKey = $courtKey."\0".$courtName."\0".$judgeName;

        if (array_key_exists($answerKey, $this->found)) {
            return $this->found[$answerKey];
        }

        $benches = $this->benches[$courtKey] ??= CourtBench::query()->where('court_key', $courtKey)->orderBy('sort_order')->get();

        return $this->found[$answerKey] = $this->byDesignation($benches, (string) $courtName) ?? $this->byJudge($benches, (string) $judgeName);
    }

    /**
     * Ids of the person's cases heard in a court room whose judge or room (per the official lists) matches the text.
     *
     * @param  list<'judge'|'room'>  $fields
     * @return list<int>
     */
    public function caseIdsMatching(string $text, array $fields): array
    {
        $text = mb_strtolower(trim($text));

        if (mb_strlen($text) < 2) {
            return [];
        }

        // Compared in PHP: a LIKE on the JSON judges column is case-sensitive on MySQL.
        $benchIds = CourtBench::query()
            ->where('court_key', 'like', 'delhi-district-%')
            ->get(['id', 'court_room', 'judges'])
            ->filter(fn (CourtBench $bench): bool => (in_array('judge', $fields, true)
                    && collect($bench->judgeList())->contains(fn (array $judge): bool => str_contains(mb_strtolower($judge['name']), $text)))
                || (in_array('room', $fields, true) && str_contains(mb_strtolower((string) $bench->court_room), $text)))
            ->pluck('id')
            ->all();

        if ($benchIds === []) {
            return [];
        }

        return LegalCase::query()
            ->whereNotNull('court_complex')
            ->get(['id', 'court_name', 'court_complex', 'judge_name'])
            ->filter(fn ($case) => in_array($this->find($case->court_name, $case->court_complex, $case->judge_name)?->id, $benchIds, true))
            ->pluck('id')
            ->values()
            ->all();
    }

    /**
     * Words and numbers of a designation in short form: "District Judge (Commercial Court)-02" → dj comm 2.
     *
     * @return array{words: list<string>, numbers: list<string>}
     */
    public function tokens(string $designation): array
    {
        $text = ' '.mb_strtolower($designation).' ';
        $text = preg_replace('/\badd(l|itional)?\.?\s+/u', 'additional ', $text);

        foreach (self::PHRASES as $phrase => $short) {
            $text = str_replace($phrase, $short, $text);
        }

        // "additional scj" / "addl. sj" → ascj / asj; "adj" (additional district judge) → dj.
        $text = preg_replace('/\badditional\s+(scj|cj|sj|cjm|rc|dj)\b/u', 'a$1', $text);
        $text = preg_replace('/\badj\b/u', 'dj', $text);

        preg_match_all('/[a-z]+|\d+/u', $text, $parts);
        $words = [];
        $numbers = [];

        foreach ($parts[0] as $part) {
            if (ctype_digit($part)) {
                $numbers[] = ltrim($part, '0') ?: '0';
            } elseif (! in_array($part, self::IGNORED_WORDS, true) && $part !== 'additional') {
                $words[] = $part;
            }
        }

        return ['words' => array_values(array_unique($words)), 'numbers' => array_values(array_unique($numbers))];
    }

    /**
     * @param  Collection<int, CourtBench>  $benches
     */
    private function byDesignation(Collection $benches, string $courtName): ?CourtBench
    {
        $case = $this->tokens($courtName);

        if ($case['words'] === []) {
            return null;
        }

        $scored = $benches->map(function (CourtBench $bench) use ($case): ?array {
            $room = $this->benchTokens[$bench->id] ??= $this->tokens(collect($bench->judgeList())->pluck('designation')->filter()->implode(' '));

            // Numbers must agree exactly (DJ-04 is not DJ-05), and one set of words must hold the other.
            sort($room['numbers']);
            $caseNumbers = $case['numbers'];
            sort($caseNumbers);

            if ($room['words'] === [] || $room['numbers'] !== $caseNumbers) {
                return null;
            }

            $roomInCase = array_diff($room['words'], $case['words']) === [];
            $caseInRoom = array_diff($case['words'], $room['words']) === [];

            if (! $roomInCase && ! $caseInRoom) {
                return null;
            }

            // Without a court number only an exact designation counts ("ACJ-cum-CCJ-cum-ARC").
            if ($caseNumbers === [] && $room['words'] !== $case['words'] && array_diff($room['words'], $case['words']) !== []) {
                return null;
            }

            return ['bench' => $bench, 'difference' => count(array_diff($room['words'], $case['words'])) + count(array_diff($case['words'], $room['words']))];
        })->filter()->sortBy('difference')->values();

        if ($scored->isEmpty()) {
            return null;
        }

        // Only a clear winner: two rooms that fit equally well (e.g. "ACJM" with no number) are not guessed.
        $best = $scored->first()['difference'];

        return $scored->where('difference', $best)->count() === 1 ? $scored->first()['bench'] : null;
    }

    /**
     * @param  Collection<int, CourtBench>  $benches
     */
    private function byJudge(Collection $benches, string $judgeName): ?CourtBench
    {
        $name = $this->plainName($judgeName);

        if (mb_strlen($name) < 5) {
            return null;
        }

        $found = $benches->filter(fn (CourtBench $bench): bool => collect($bench->judgeList())
            ->contains(fn (array $judge): bool => $this->plainName($judge['name']) === $name));

        return $found->count() === 1 ? $found->first() : null;
    }

    private function plainName(string $name): string
    {
        return (string) Str::of($name)->lower()->replaceMatches('/^(sh|shri|ms|mr|mrs|smt|dr|hon\'?ble)\.?\s+/', '')->replaceMatches('/[^a-z ]/', '')->squish();
    }
}
