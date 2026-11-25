<?php

namespace App\Services\CauseLists;

use App\Services\DelhiHighCourtCauseList;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Supreme Court cause lists: sci.gov.in/cause-list links the published daily lists as PDFs
 * (api.sci.gov.in/jonew/cl/<date>/M_J_1.pdf …), reachable from outside India, without a captcha.
 */
class SupremeCourtCauseList
{
    public const PROVIDER = 'supreme-court';

    public const PAGE_URL = 'https://www.sci.gov.in/cause-list/';

    /**
     * The daily lists published for a date (and the advance list for it).
     *
     * @return list<array{title: string, url: string}>
     */
    public function documentsFor(Carbon $date): array
    {
        $page = Http::timeout(30)->connectTimeout(10)->withUserAgent('Mozilla/5.0 (VakilNiti cause list check)')->get(self::PAGE_URL);

        return $page->successful() ? $this->documentsInPage($page->body(), $date) : [];
    }

    /**
     * @return list<array{title: string, url: string}>
     */
    public function documentsInPage(string $html, Carbon $date): array
    {
        $day = preg_quote($date->toDateString(), '#');
        preg_match_all('#https://api\.sci\.gov\.in/jonew/cl/(advance/)?'.$day.'/([A-Za-z0-9_]+)\.pdf#', $html, $links, PREG_SET_ORDER);
        $documents = [];

        foreach ($links as [$url, $advance, $name]) {
            $documents[$url] = ['title' => ($advance ? 'Advance list' : 'Daily cause list').' '.$name.' · '.$date->format('d-m-Y'), 'url' => $url];
        }

        return array_values($documents);
    }

    /**
     * Every listed matter: item, case or diary number, court and bench. Applications ("IA No.") and the
     * main case named after "in" (a contempt petition in a civil appeal) are not counted as the case itself.
     *
     * @return list<array{key: string, reference: string, item: string, court: ?string, judges: list<string>, vc_link: ?string, line: int}>
     */
    public function entries(string $text): array
    {
        $lines = array_map(fn (string $line): string => trim(str_replace("\u{00A0}", ' ', $line)), preg_split('/\R/u', $text) ?: []);

        // A case number broken after "No." continues on the next line.
        for ($index = 0; $index < count($lines) - 1; $index++) {
            if (preg_match('/\bNo\.\s*-?$/', $lines[$index]) && preg_match('/^\d/', $lines[$index + 1])) {
                $lines[$index] .= ' '.$lines[$index + 1];
                $lines[$index + 1] = '';
            }
        }

        $court = null;
        $judges = [];
        $item = null;
        $readingBench = false;
        $entries = [];

        foreach ($lines as $index => $line) {
            if ($line === '') {
                continue;
            }

            $newCourt = match (true) {
                (bool) preg_match('/^COURT NO\.\s*:\s*(\d{1,2})\b/', $line, $match) => (string) (int) $match[1],
                (bool) preg_match("/^CHIEF JUSTICE['’]S COURT/", $line) => '1',
                (bool) preg_match('/^REGISTRAR COURT NO\.\s*:?\s*(\d{1,2})/', $line, $match) => 'R'.(int) $match[1],
                default => null,
            };

            if ($newCourt !== null) {
                // Each page repeats the court's name; a new bench starts only when the court changes.
                if ($newCourt !== $court) {
                    $court = $newCourt;
                    $judges = [];
                    $item = null;
                    $readingBench = true;
                }

                continue;
            }

            if ($readingBench && preg_match("/^HON['’]BLE\s+(.+)$/u", $line, $match)) {
                $judges[] = trim($match[1]);

                continue;
            }

            if (preg_match('/^(\d{1,4}(?:\.\d{1,2})?)\s*(?=Connected|Diary|[A-Z][A-Za-z.()]*(?:\s?\([A-Za-z.]+\))?\s*No\.)/', $line, $match)) {
                $item = $match[1];
                $readingBench = false;
            }

            if ($item === null || $court === null) {
                continue;
            }

            foreach ($this->references($line) as [$key, $reference]) {
                $entries[] = ['key' => $key, 'reference' => $reference, 'item' => $item, 'court' => $court, 'judges' => $judges, 'vc_link' => null, 'line' => $index + 1];
            }
        }

        return $entries;
    }

    /**
     * Case and diary numbers in one line, as [key, how it is printed].
     *
     * @return list<array{0: string, 1: string}>
     */
    private function references(string $line): array
    {
        $found = [];

        // "4Diary No. 47715-2026": the item number can run straight into "Diary".
        preg_match_all('/(?<![A-Za-z])Diary No\.\s*(\d{1,7})\s*-\s*((?:19|20)\d{2})/', $line, $diaries, PREG_SET_ORDER);

        foreach ($diaries as [$printed, $number, $year]) {
            $found[] = [CauseListRecorder::diaryKey($number, $year), $printed];
        }

        preg_match_all('/(\bin\s+|\bIN\s+)?\b([A-Za-z][A-Za-z.]*(?:\s?\([A-Za-z.]+\))?)\s*No\.\s*-?\s*(\d{1,7})(?:\s*-\s*(\d{1,7}))?\s*\/\s*((?:19|20)\d{2})/', $line, $cases, PREG_SET_ORDER);

        foreach ($cases as $case) {
            [$printed, $within, $type, $first, $last, $year] = $case + [5 => ''];

            if ($within !== '' || in_array(strtoupper(rtrim($type, '.')), ['IA', 'DIARY'], true)) {
                continue;
            }

            // "C.A. No. 2403-2404/2019" lists two appeals.
            $numbers = $last !== '' && (int) $last > (int) $first && (int) $last - (int) $first <= 30 ? range((int) $first, (int) $last) : [(int) $first];

            foreach ($numbers as $number) {
                $found[] = [DelhiHighCourtCauseList::caseKey($type, (string) $number, $year), trim($printed)];
            }
        }

        return $found;
    }
}
