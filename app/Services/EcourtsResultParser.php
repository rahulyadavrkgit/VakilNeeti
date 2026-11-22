<?php

namespace App\Services;

/**
 * Reads the case list a person copies from an eCourts "search by advocate" result page
 * and pastes into VakilNiti. Nothing is fetched from eCourts: the person searches there
 * themselves (including the CAPTCHA) and brings the text back.
 */
class EcourtsResultParser
{
    /** Case number written as TYPE/NUMBER/YEAR, e.g. "O.S./123/2019" or "CRL. A/45/2021". */
    private const CASE_NUMBER = '/(?<![A-Z0-9])([A-Z][A-Z0-9.()&\- ]{0,24}?)\s*\/\s*(\d{1,7})\s*\/\s*((?:19|20)\d{2})(?!\d)/i';

    /** 16-character CNR number, e.g. "UPLK010012342019". */
    private const CNR = '/\b([A-Z]{4}\d{12})\b/';

    private const PARTIES = '/^(.+?)\s+(?:vs\.?|v\/s\.?|versus|v\.)\s+(.+)$/i';

    private const COURT_WORDS = '/\b(court|judge|tribunal|bench|magistrate|forum|commission|munsif|sessions)\b/i';

    private const HEADER_WORDS = '/^(sr\.?\s*no|s\.?\s*no|case\s*type|case\s*number|petitioner|respondent|advocate|view|party\s*name)/i';

    /**
     * @return list<array{case_type: string, case_number: string, case_year: string, full_number: string, cnr: ?string, petitioner: ?string, respondent: ?string, title: string, court: ?string}>
     */
    public function parse(string $text, ?string $defaultCourt = null): array
    {
        $lines = array_values(array_filter(
            array_map(fn (string $line): string => trim((string) preg_replace('/[^\S\t]+/u', ' ', $line)), preg_split('/\R/u', $text) ?: []),
            fn (string $line): bool => $line !== '',
        ));

        $cases = [];
        $current = null;
        $court = $defaultCourt ?: null;

        foreach ($lines as $line) {
            $cells = array_values(array_filter(array_map('trim', preg_split('/\t| {2,}/', $line) ?: []), fn (string $cell): bool => $cell !== ''));
            $caseNumber = $this->findCaseNumber($cells, $line);

            if ($caseNumber) {
                if ($current) {
                    $cases[] = $current;
                }

                $current = $caseNumber + ['cnr' => $this->findCnr($line), 'petitioner' => null, 'respondent' => null, 'court' => $court];
                $this->fillParties($current, $cells, $caseNumber['raw']);

                continue;
            }

            if ($current && ! $current['petitioner'] && $this->fillParties($current, $cells, null)) {
                continue;
            }

            if ($current && ! $current['cnr'] && ($cnr = $this->findCnr($line))) {
                $current['cnr'] = $cnr;

                continue;
            }

            if ($this->looksLikeCourtHeading($line)) {
                if ($current) {
                    $cases[] = $current;
                    $current = null;
                }

                $court = mb_substr($line, 0, 255);
            }
        }

        if ($current) {
            $cases[] = $current;
        }

        $unique = [];

        foreach ($cases as $case) {
            unset($case['raw']);
            $case['title'] = $case['petitioner'] && $case['respondent']
                ? $case['petitioner'].' vs '.$case['respondent']
                : $case['full_number'];
            $unique[strtoupper($case['full_number'].'|'.$case['court'])] ??= $case;
        }

        return array_values($unique);
    }

    /**
     * @param  list<string>  $cells
     * @return array{case_type: string, case_number: string, case_year: string, full_number: string, raw: string}|null
     */
    private function findCaseNumber(array $cells, string $line): ?array
    {
        foreach ([...$cells, $line] as $candidate) {
            if (preg_match(self::CASE_NUMBER, $candidate, $match)) {
                $type = strtoupper(trim($match[1], ' -'));

                // A leading serial number ("1 CS") is not part of the case type.
                $type = trim((string) preg_replace('/^\d+\s+/', '', $type));

                if ($type === '' || preg_match('/\b(VS|VERSUS)\b/', $type)) {
                    continue;
                }

                return [
                    'case_type' => $type,
                    'case_number' => ltrim($match[2], '0') ?: '0',
                    'case_year' => $match[3],
                    'full_number' => $type.'/'.(ltrim($match[2], '0') ?: '0').'/'.$match[3],
                    'raw' => $match[0],
                ];
            }
        }

        return null;
    }

    private function findCnr(string $line): ?string
    {
        return preg_match(self::CNR, strtoupper($line), $match) ? $match[1] : null;
    }

    /**
     * Find "Petitioner vs Respondent" in the cells and store it on the case. Returns whether it was found.
     *
     * @param  array<string, mixed>  $case
     * @param  list<string>  $cells
     */
    private function fillParties(array &$case, array $cells, ?string $caseNumberText): bool
    {
        foreach ($cells as $cell) {
            if ($caseNumberText !== null) {
                $cell = trim(str_replace($caseNumberText, '', $cell));
            }

            $cell = trim((string) preg_replace('/^\d+\s+/', '', $cell));

            if (preg_match(self::PARTIES, $cell, $match)) {
                $case['petitioner'] = mb_substr(trim($match[1], ' ,-'), 0, 200);
                $case['respondent'] = mb_substr(trim((string) preg_replace('/\s+view$/i', '', $match[2]), ' ,-'), 0, 200);

                return true;
            }
        }

        return false;
    }

    private function looksLikeCourtHeading(string $line): bool
    {
        return mb_strlen($line) <= 150
            && preg_match(self::COURT_WORDS, $line) === 1
            && preg_match(self::HEADER_WORDS, $line) !== 1
            && preg_match(self::PARTIES, $line) !== 1;
    }
}
