<?php

namespace App\Services;

use App\Http\Controllers\CourtLinksController;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * The latest bail roster and duty magistrate roster of each Delhi district, read from the district's own website
 * (they list their PDFs newest first; the file name starts with the upload date, e.g. 2026110651.pdf). The PDF is
 * kept here for a few hours, so it opens inside VakilNiti on any phone and can be shared.
 */
class DistrictCourtDocuments
{
    /**
     * The documents, by their address in VakilNiti.
     *
     * @var array<string, array{label: string, page: string, match: string}>
     */
    public const KINDS = [
        'bail-roster' => ['label' => 'Bail roster', 'page' => '?s=bail+roster', 'match' => '/\bbail\b/i'],
        'duty-magistrate' => ['label' => 'Duty magistrate', 'page' => 'duty-magistrate/', 'match' => '/\b(duty|magistrate|jmfc|mm)\b/i'],
    ];

    /** How long a found document is trusted before the district's site is read again. */
    private const KEEP_HOURS = 6;

    /** PDFs larger than this are not copied (the rosters are 0.5–2 MB). */
    private const MAX_BYTES = 20_000_000;

    /**
     * Every district, by its address: "south", "south-east", "central-rouse-avenue-courts"…
     *
     * @return array<string, array{district: string, complex: string, url: string, bail_roster?: string}>
     */
    public static function districts(): array
    {
        $districts = [];

        foreach (CourtLinksController::DELHI_DISTRICT_COMPLEXES as $complex => $list) {
            foreach ($list as $district) {
                $districts[Str::slug($district['district'])] = $district + ['complex' => $complex];
            }
        }

        return $districts;
    }

    /**
     * The district's latest document of this kind: its title, the court's PDF address and the upload date.
     *
     * @return array{title: string, url: string, date: ?string, page: string}|null
     */
    public function latest(string $districtSlug, string $kind, bool $fresh = false): ?array
    {
        $district = self::districts()[$districtSlug] ?? null;

        if (! $district || ! isset(self::KINDS[$kind])) {
            return null;
        }

        $key = "district-document:{$districtSlug}:{$kind}";

        if ($fresh) {
            Cache::forget($key);
        }

        $found = Cache::get($key);

        if ($found === null) {
            $found = $this->find($district, $kind);
            // A site that did not answer is asked again sooner.
            Cache::put($key, $found ?: false, $found ? now()->addHours(self::KEEP_HOURS) : now()->addMinutes(30));
        }

        return $found ?: null;
    }

    /**
     * The district's page that lists this kind of document.
     *
     * @param  array{url: string, bail_roster?: string}  $district
     */
    public static function listPage(array $district, string $kind): string
    {
        $path = $kind === 'bail-roster' ? ($district['bail_roster'] ?? self::KINDS[$kind]['page']) : self::KINDS[$kind]['page'];

        return $district['url'].$path;
    }

    /**
     * The newest matching PDF on the district's page.
     *
     * @param  array{url: string, bail_roster?: string}  $district
     * @return array{title: string, url: string, date: ?string, page: string}|null
     */
    private function find(array $district, string $kind): ?array
    {
        $page = self::listPage($district, $kind);

        try {
            $html = Http::timeout(15)->connectTimeout(8)->withUserAgent('Mozilla/5.0 (VakilNiti district rosters)')->get($page)->body();
        } catch (Throwable) {
            return null;
        }

        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="utf-8"?>'.$html);
        $candidates = [];

        foreach ((new DOMXPath($document))->query('//a[contains(@href, ".pdf")]') as $link) {
            /** @var DOMElement $link */
            $url = $link->getAttribute('href');
            $title = trim((string) preg_replace('/\s+/u', ' ', $link->textContent));
            $title = trim((string) preg_replace('/\(?\s*PDF\s*[\d.]+\s*[KM]B\s*\)?\.?$/i', '', $title));
            $host = (string) parse_url($url, PHP_URL_HOST);

            // Only the court's own files, with a real title ("View", "Download" are the same file again).
            if (! str_ends_with($host, '.gov.in') || mb_strlen($title) < 8 || ! preg_match(self::KINDS[$kind]['match'], $title)) {
                continue;
            }

            $stamp = preg_match('/(\d{8})\d*\.pdf$/i', $url, $match) ? $match[1] : '00000000';
            $candidates[$url] ??= ['title' => rtrim($title, '. '), 'url' => $url, 'stamp' => $stamp];
        }

        if ($candidates === []) {
            return null;
        }

        $latest = collect($candidates)->sortByDesc('stamp')->first();
        $date = $latest['stamp'] !== '00000000' ? substr($latest['stamp'], 0, 4).'-'.substr($latest['stamp'], 4, 2).'-'.substr($latest['stamp'], 6, 2) : null;

        return ['title' => $latest['title'], 'url' => $latest['url'], 'date' => $date && strtotime($date) ? $date : null, 'page' => $page];
    }

    /**
     * The document's PDF, copied here (path on the local disk), or null when it cannot be had.
     *
     * @param  array{url: string}  $found
     */
    public function pdfPath(string $districtSlug, string $kind, array $found): ?string
    {
        $path = 'district-documents/'.$districtSlug.'-'.$kind.'-'.md5($found['url']).'.pdf';
        $disk = Storage::disk('local');

        if ($disk->exists($path)) {
            return $path;
        }

        try {
            $response = Http::timeout(30)->connectTimeout(8)->withUserAgent('Mozilla/5.0 (VakilNiti district rosters)')->get($found['url']);
        } catch (Throwable) {
            return null;
        }

        $bytes = $response->body();

        if (! $response->successful() || ! str_starts_with($bytes, '%PDF') || strlen($bytes) > self::MAX_BYTES) {
            return null;
        }

        // One copy per district and kind: older rosters go.
        foreach ($disk->files('district-documents') as $old) {
            if (str_starts_with(basename($old), $districtSlug.'-'.$kind.'-')) {
                $disk->delete($old);
            }
        }

        $disk->put($path, $bytes);

        return $path;
    }
}
