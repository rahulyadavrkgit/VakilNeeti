<?php

namespace App\Services\CauseLists;

use App\Services\DelhiHighCourtCauseList;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Allahabad High Court (principal seat) cause lists: allahabadhighcourt.in/causelist publishes one
 * "Combined Cause List" PDF per court for each date. The pages are plain forms (no captcha):
 * list type → date → court → PDF link.
 */
class AllahabadHighCourtCauseList
{
    public const PROVIDER = 'allahabad-high-court';

    public const PAGE_URL = 'https://www.allahabadhighcourt.in/causelist/indexA.html';

    private const BASE = 'https://www.allahabadhighcourt.in/causelist/';

    private const CASE_PATTERN = '/\b([A-Z][A-Z]{1,14})\/(\d{1,7})\/((?:19|20)\d{2})\b/';

    /**
     * Each court's list for the date (one request per court, one after the other, to be gentle with the site).
     *
     * @return list<array{title: string, url: string}>
     */
    public function documentsFor(Carbon $date): array
    {
        $day = $date->format('d-m-Y');
        $client = $this->client(new CookieJar);

        $dates = $client->asForm()->post(self::BASE.'input1A.jsp', ['listType' => 'Z']);

        if (! $dates->successful() || ! str_contains($dates->body(), $day)) {
            return [];
        }

        $courts = $client->asForm()->post(self::BASE.'input2A.jsp', ['listDate' => $day, 'criteria' => 'court', 'listType' => 'Z']);
        preg_match_all('/<OPTION VALUE=(-?\d+)[^>]*>([^<]+)/i', (string) $courts->body(), $options, PREG_SET_ORDER);
        $documents = [];

        foreach ($options as [, $value, $name]) {
            if ((int) $value === -99) {
                continue;
            }

            $page = $client->asForm()->post(self::BASE.'viewlistA.jsp', ['courtNo' => $value, 'location' => 'A', 'listType' => 'Z', 'listDate' => $day]);
            preg_match_all('#href="(https://www2\.allahabadhighcourt\.in/clist/[^"]+\.pdf)"#i', (string) $page->body(), $links);

            foreach (array_unique($links[1]) as $index => $url) {
                $documents[] = ['title' => 'Combined Cause List · '.trim(html_entity_decode($name)).' · '.$day.(count($links[1]) > 1 ? ' (part '.($index + 1).')' : ''), 'url' => $url];
            }

            usleep(200_000);
        }

        return $documents;
    }

    /**
     * Every listed matter in one court's list: item, case number, court and bench.
     *
     * @return list<array{key: string, reference: string, item: string, court: ?string, judges: list<string>, vc_link: ?string, line: int, item_line: int}>
     */
    public function entries(string $text): array
    {
        $lines = preg_split('/\R/u', $text) ?: [];
        $court = null;
        $judges = [];
        $item = null;
        $itemLine = 0;
        $entries = [];

        foreach ($lines as $index => $rawLine) {
            $line = trim(str_replace("\u{00A0}", ' ', $rawLine));

            // "Court No-1", or on later pages "Court No-126-11-2026" (the court number runs into the date).
            if (preg_match('/^Court\s*No[\s.\-:]*(\d{1,3}?)\s*(?:\d{2}-\d{2}-\d{4})?$/i', $line, $match) || preg_match("/^THE\s+CHIEF\s+JUSTICE['’]?S\s+COURT/i", $line)) {
                $newCourt = isset($match[1]) ? (string) (int) $match[1] : 'CJ';

                if ($newCourt !== $court) {
                    $court = $newCourt;
                    $judges = [];
                }

                $match = [];

                continue;
            }

            if ($court !== null && $item === null && preg_match("/^HON[’']?BLE\s+(.+)$/iu", $line, $match) && count($judges) < 3) {
                $judges[] = trim(preg_replace('/\s+/', ' ', $match[1]) ?? '');

                continue;
            }

            // " 12 \tPARTY NAME…" starts an item.
            if (preg_match('/^\s*(\d{1,4})\s*\t/', $rawLine, $match)) {
                $item = $match[1];
                $itemLine = $index + 1;
            }

            if ($item === null || str_contains(strtoupper($line), 'NOTICE NO')) {
                continue;
            }

            preg_match_all(self::CASE_PATTERN, strtoupper($line), $refs, PREG_SET_ORDER);

            foreach ($refs as [$reference, $type, $number, $year]) {
                $entries[] = [
                    'key' => DelhiHighCourtCauseList::caseKey($type, $number, $year),
                    'reference' => $reference,
                    'item' => $item,
                    'court' => $court,
                    'judges' => $judges,
                    'vc_link' => null,
                    'line' => $index + 1,
                    'item_line' => $itemLine,
                ];
            }
        }

        return $entries;
    }

    private function client(CookieJar $jar): PendingRequest
    {
        return Http::timeout(60)->connectTimeout(10)->withUserAgent('Mozilla/5.0 (VakilNiti cause list check)')->withOptions(['cookies' => $jar]);
    }
}
