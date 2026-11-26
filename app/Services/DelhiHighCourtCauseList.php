<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Parser;

/**
 * Reads the Delhi High Court's public cause lists (PDFs on delhihighcourt.nic.in) and finds where a case is listed:
 * court number, item number, bench and the court's VC link.
 */
class DelhiHighCourtCauseList
{
    public const PAGE_URL = 'https://delhihighcourt.nic.in/web/cause-lists/cause-list';

    public const EXTRACTION_VERSION = 'list-sections-v2';

    private const SITE = 'https://delhihighcourt.nic.in';

    /**
     * Lists that say where matters are NOT heard (deletions, corrections): a case in them is not "listed".
     */
    private const SKIPPED_TITLES = '/deletion|corrigendum|delete/i';

    /**
     * "W.P.(C) 9569/2007", "W.P.(C)-15570/2026", "FAO (COMM) 36/2022", "BAIL APPLN.-5003/2026" at the start of a text.
     */
    private const CASE_PATTERN = '([A-Z][A-Z.()&\'\s]{0,40}?)\s*-?\s*(\d{1,6})\s*\/\s*((?:19|20)\d{2})';

    /**
     * The court's cause-list pages (newest first, ten lists a page), read until they reach lists older than $earliest.
     * Null when the site does not answer.
     */
    public function listingPages(Carbon $earliest, int $maxPages = 6): ?string
    {
        $html = '';

        for ($page = 0; $page < $maxPages; $page++) {
            $response = Http::timeout(30)->connectTimeout(10)->withUserAgent('Mozilla/5.0 (VakilNiti cause list check)')
                ->get(self::PAGE_URL, $page ? ['page' => $page] : []);

            if (! $response->successful()) {
                return $page === 0 ? null : $html;
            }

            $html .= $response->body();
            preg_match_all('#field-date">\s*(\d{2}-\d{2}-\d{4})#', $response->body(), $dates);

            $oldest = collect($dates[1])->map(fn (string $date): Carbon => Carbon::createFromFormat('d-m-Y', $date)->startOfDay())->min();

            if ($oldest === null || $oldest->lt($earliest->copy()->startOfDay())) {
                break;
            }
        }

        return $html;
    }

    /**
     * @return list<array{title: string, url: string}>
     */
    public function documentsInPage(string $html, Carbon $date): array
    {
        preg_match_all('#views-field-title">(.*?)</td>\s*<td[^>]*field-date">(.*?)</td>\s*<td[^>]*>.*?href="([^"]+\.pdf)"#si', $html, $rows, PREG_SET_ORDER);

        $documents = [];

        foreach ($rows as [, $title, $listed, $href]) {
            $title = trim(html_entity_decode(strip_tags($title)));

            if (trim(strip_tags($listed)) !== $date->format('d-m-Y') || preg_match(self::SKIPPED_TITLES, $title)) {
                continue;
            }

            $url = str_starts_with($href, 'http') ? $href : self::SITE.'/'.ltrim(html_entity_decode($href), '/');
            $documents[$url] = ['title' => $title, 'url' => $url];
        }

        return array_values($documents);
    }

    /**
     * The text of a cause-list PDF.
     */
    public function textOf(string $pdfContents): string
    {
        return (new Parser)->parseContent($pdfContents)->getText();
    }

    /**
     * Every listed matter in a cause list's text, with the court it is listed in.
     *
     * @return list<array{key: string, reference: string, item: string, court: ?string, judges: list<string>, vc_link: ?string, line: int, board_section: ?string}>
     */
    public function entries(string $text): array
    {
        $lines = preg_split('/\R/u', $text) ?: [];
        $court = null;
        $judges = [];
        $vcLink = null;
        $item = null;
        $section = null;
        $entries = [];

        foreach ($lines as $index => $rawLine) {
            $line = trim(str_replace("\u{00A0}", ' ', $rawLine));

            if (preg_match('/^(?:\d{2}[.\/-]\d{2}[.\/-]\d{4}\s+)?(ADVANCE|SUPPLEMENTARY|REGULAR|FINAL)(?:\s+MATTERS)?\s+CAUSE\s+LIST\b/i', $line, $heading)) {
                $newSection = strtolower($heading[1]);

                if ($newSection !== $section) {
                    $section = $newSection;
                    $court = null;
                    $judges = [];
                    $vcLink = null;
                    $item = null;
                }

                continue;
            }

            if (preg_match('/^COURT\s*NO\.?\s*:?\s*(\d{1,3})\b/i', $line, $match)) {
                $court = (string) (int) $match[1];
                $judges = [];
                $vcLink = null;
                $item = null;

                continue;
            }

            if ($court !== null && preg_match("/^HON[’']?BLE\s+(.+)$/iu", $line, $match) && count($judges) < 3) {
                $judges[] = trim(preg_replace('/\s+/', ' ', rtrim($match[1], '_ ')) ?? '');

                continue;
            }

            if ($vcLink === null && preg_match('#(https?://[a-z0-9.-]*webex\.com/(?:meet|join)/[A-Za-z0-9._-]+(?: [a-z0-9._-]+)?)#i', $line, $match)) {
                $vcLink = str_replace(' ', '', $match[1]);

                continue;
            }

            // "12. W.P.(C) 9569/2007 ..." starts an item; "WITH W.P.(C) 298/2008" on the next lines is heard with it.
            if (preg_match('/^(\d{1,4})\.?\s+'.self::CASE_PATTERN.'/', $line, $match)) {
                $item = $match[1];
                $reference = $match;
            } elseif ($item !== null && preg_match('/^WITH\s+'.self::CASE_PATTERN.'/i', $line, $with)) {
                $reference = [null, $item, $with[1], $with[2], $with[3]];
            } else {
                continue;
            }

            $entries[] = [
                'key' => self::caseKey($reference[2], $reference[3], $reference[4]),
                'reference' => trim(preg_replace('/\s+/', ' ', $reference[2]) ?? '').' '.$reference[3].'/'.$reference[4],
                'item' => $item,
                'court' => $court,
                'judges' => $judges,
                'vc_link' => $vcLink,
                'line' => $index + 1,
                'board_section' => $section,
            ];
        }

        return $entries;
    }

    /**
     * One spelling for a case number, however it is written: "W.P.(C)/7570/2023" and "W.P.(C)-7570/2023" both give "WPC-7570-2023".
     */
    public static function caseKey(string $type, string $number, string $year): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z]/', '', $type)).'-'.((int) $number).'-'.$year;
    }

    /**
     * The key of a saved case number such as "W.P.(C)/7570/2023" (or "7570/2023" with the type kept separately).
     */
    public static function keyForCase(?string $caseNumber, ?string $caseType = null): ?string
    {
        if (! preg_match('/^(.*?)[\s\/-]*(\d{1,6})\s*\/\s*((?:19|20)\d{2})\s*$/', trim((string) $caseNumber), $match)) {
            return null;
        }

        $type = trim($match[1]) !== '' ? $match[1] : (string) $caseType;

        return preg_match('/[A-Za-z]/', $type) ? self::caseKey($type, $match[2], $match[3]) : null;
    }
}
