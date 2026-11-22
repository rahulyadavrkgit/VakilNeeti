<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * Reads the "My Cases" export of the official eCourts Services mobile app
 * (myCases.txt for district courts, hcMyCases.txt for High Courts).
 *
 * The file is a JSON string holding a JSON list whose items are JSON strings,
 * one per case, so it is decoded up to three times.
 */
class EcourtsAppExportParser
{
    /** CNR numbers are 16 characters: 4 letters then 12 digits. */
    private const CNR = '/^[A-Z]{4}\d{12}$/';

    /**
     * @return list<array{cnr: string, is_high_court: bool, case_type: ?string, case_number: string, case_title: string, petitioner: ?string, respondent: ?string, court_name: string, court_complex: ?string, judge_name: ?string, filing_number: ?string, case_status: string, disposal: ?string, disposed_on: ?string, stage: ?string, last_date: ?string, next_date: ?string, state: ?string, district: ?string}>
     *
     * @throws \InvalidArgumentException when the text is not an eCourts app export
     */
    public function parse(string $contents): array
    {
        $items = $this->decodeList($contents);
        $cases = [];

        foreach ($items as $item) {
            $record = is_string($item) ? json_decode($item, true) : $item;

            if (! is_array($record)) {
                continue;
            }

            $case = $this->mapRecord($record);

            if ($case) {
                $cases[$case['cnr']] = $case;
            }
        }

        if ($cases === []) {
            throw new \InvalidArgumentException('No cases were found in this file. Choose the file exported from the eCourts Services app (My Cases → Export).');
        }

        return array_values($cases);
    }

    /**
     * @return list<mixed>
     */
    private function decodeList(string $contents): array
    {
        $value = trim((string) preg_replace('/^\xEF\xBB\xBF/', '', $contents));

        for ($attempt = 0; $attempt < 3 && is_string($value); $attempt++) {
            $decoded = json_decode($value, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                break;
            }

            $value = $decoded;
        }

        if (! is_array($value) || ! array_is_list($value)) {
            throw new \InvalidArgumentException('This file is not an eCourts app export. Choose the myCases or hcMyCases file exported from the eCourts Services app.');
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>|null
     */
    private function mapRecord(array $record): ?array
    {
        $cnr = strtoupper(trim((string) ($record['cino'] ?? '')));

        if (! preg_match(self::CNR, $cnr)) {
            return null;
        }

        $text = fn (string $key): ?string => ($value = trim((string) preg_replace('/\s+/', ' ', (string) ($record[$key] ?? '')))) === '' ? null : $value;
        $isHighCourt = substr($cnr, 2, 2) === 'HC';

        $type = $text('type_name') ?? $text('fil_type_name');
        $number = (int) ($record['reg_no'] ?? 0) ?: (int) ($record['fil_no'] ?? 0);
        $year = (int) ($record['reg_year'] ?? 0) ?: (int) ($record['fil_year'] ?? 0);
        $caseNumber = implode('/', array_filter([$type, $number ?: null, $year ?: null]));

        $petitioner = $text('petparty_name');
        $respondent = $text('resparty_name');

        $establishment = $text('establishment_name');
        $bench = $text('court_no_desg_name');

        if ($bench && str_contains(strtoupper($bench), 'NOT ALLOCATED')) {
            $bench = null;
        }

        // Some High Court rows carry a database name ("High Court cisdb_…") instead of the court's name.
        if ($establishment && preg_match('/cisdb|_\d{6,}/i', $establishment)) {
            $establishment = null;
        }

        if ($isHighCourt) {
            $courtName = $text('district_name') ?? $establishment ?? 'High Court';
            $complex = $establishment !== $courtName ? $establishment : null;
            $judge = $bench && ! preg_match('/^HON\'?BLE JUSTICE$/i', $bench) ? $bench : null;
        } else {
            $courtName = $bench ?? $establishment ?? 'Court not recorded';
            $complex = collect([$text('district_name'), $text('state_name')])
                ->filter()
                ->reject(fn (string $part): bool => $establishment !== null && str_contains($establishment, $part))
                ->prepend($establishment)
                ->filter()->unique()->reject(fn (string $part): bool => $part === $courtName)->implode(', ') ?: null;
            $judge = null;
        }

        $disposal = $text('disp_name');
        $status = match (true) {
            $disposal === null => 'pending',
            str_contains(strtoupper($disposal), 'TRANSFER') => 'transferred',
            str_contains(strtoupper($disposal), 'WITHDRAW') => 'withdrawn',
            default => 'disposed',
        };

        $filNumber = (int) ($record['fil_no'] ?? 0);
        $filYear = (int) ($record['fil_year'] ?? 0);

        return [
            'cnr' => $cnr,
            'is_high_court' => $isHighCourt,
            'case_type' => $type ? mb_substr($type, 0, 100) : null,
            'case_number' => mb_substr($caseNumber ?: $cnr, 0, 255),
            'case_title' => mb_substr($petitioner && $respondent ? "{$petitioner} vs {$respondent}" : ($petitioner ?? $respondent ?? $caseNumber ?: $cnr), 0, 255),
            'petitioner' => $petitioner,
            'respondent' => $respondent,
            'court_name' => mb_substr($courtName, 0, 255),
            'court_complex' => $complex ? mb_substr($complex, 0, 255) : null,
            'judge_name' => $judge ? mb_substr($judge, 0, 255) : null,
            'filing_number' => $filNumber ? $filNumber.($filYear ? '/'.$filYear : '') : null,
            'case_status' => $status,
            'disposal' => $disposal,
            'disposed_on' => $status !== 'pending' ? $this->date($record['date_of_decision'] ?? null) : null,
            'stage' => $text('purpose_name') ? mb_substr($text('purpose_name'), 0, 255) : null,
            'last_date' => $this->date($record['date_last_list'] ?? null),
            'next_date' => $status === 'pending' ? $this->date($record['date_next_list'] ?? null) : null,
            'state' => $text('state_name'),
            'district' => $text('district_name'),
        ];
    }

    private function date(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
