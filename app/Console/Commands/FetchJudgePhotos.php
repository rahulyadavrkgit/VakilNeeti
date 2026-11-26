<?php

namespace App\Console\Commands;

use App\Http\Controllers\CourtLinksController;
use App\Models\CourtBench;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * The judges' photos for the court-room lists, from each court's own judges page: saved once on this server
 * (small copies, storage/app/public/judges) so the lists load fast, and matched to the judges by name.
 * The photos are refreshed weekly from the higher courts and the supported district directories.
 */
#[Signature('courts:judge-photos {--districts-only : Refresh only the district court portraits}')]
#[Description('Save official judge portraits for the court directories and case timeline')]
class FetchJudgePhotos extends Command
{
    private const PAGES = [
        'Supreme Court' => 'https://www.sci.gov.in/chief-justice-judges/',
        'Delhi High Court' => 'https://delhihighcourt.nic.in/web/CJ_Sitting_Judges',
    ];

    public function handle(): int
    {
        $photos = $this->option('districts-only') ? [] : $this->higherCourtPhotos();
        $photos += $this->refreshDistrictPhotos();

        if ($photos === []) {
            $this->warn('No portraits could be downloaded.');

            return self::FAILURE;
        }

        $saved = rescue(fn () => json_decode((string) Storage::disk('local')->get(CourtBench::PHOTOS_FILE), true), [], false) ?: [];
        if (! Storage::disk('local')->put(CourtBench::PHOTOS_FILE, json_encode(array_replace($saved, $photos), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR))) {
            $this->error('The judge photo index could not be saved.');

            return self::FAILURE;
        }

        $this->info('Saved '.count($photos).' photos.');

        return self::SUCCESS;
    }

    /** @return array<string, string> */
    private function higherCourtPhotos(): array
    {
        $saved = [];

        foreach (self::PAGES as $court => $url) {
            // Delhi HC splits its judges over pages (?page=0, 1, 2…); read on until a page adds nobody new.
            $photos = [];
            foreach (range(0, 9) as $page) {
                $onPage = $this->photosOn($page === 0 ? $url : $url.'?page='.$page);
                $before = count($photos);
                $photos += $onPage;

                if (count($photos) === $before) {
                    break;
                }
            }
            $this->line("{$court}: ".count($photos).' judges with photos on the court\'s page.');

            foreach ($photos as $name => $imageUrl) {
                $key = CourtBench::judgeKey($name);

                if ($key === '' || ! ($path = $this->savePhoto($key, $imageUrl))) {
                    continue;
                }

                $saved[$key] = $path;
            }
        }

        return $saved;
    }

    /** @return array<string, string> */
    private function refreshDistrictPhotos(): array
    {
        $pages = ['raigad' => 'https://raigad.dcourts.gov.in/list-of-judges/'];
        foreach (CourtLinksController::DELHI_DISTRICT_COMPLEXES as $districts) {
            foreach ($districts as $district) {
                $pages[Str::slug($district['district'])] = $district['url'].'list-of-judges/';
            }
        }
        $pages = array_unique($pages);
        $candidates = [];

        foreach ($this->districtRequests($pages) as $district => $response) {
            if (! $response instanceof Response || ! $response->successful()) {
                $this->warn("Could not open the {$district} judges directory.");

                continue;
            }

            $portraits = $this->districtPortraits($response->body(), $pages[$district]);
            $this->line("{$district}: ".count($portraits).' judge portraits in the official directory.');
            foreach ($portraits as $key => $url) {
                $candidates[$district.'-'.$key] = $url;
            }
        }

        $saved = [];
        foreach (array_chunk($candidates, 24, true) as $batch) {
            foreach ($this->districtRequests($batch) as $key => $response) {
                if ($response instanceof Response && ($path = $this->saveImage($key, $response))) {
                    $saved[$key] = $path;
                }
            }
        }

        return $saved;
    }

    /**
     * @param  array<string, string>  $urls
     * @return array<string, Response|Throwable>
     */
    private function districtRequests(array $urls): array
    {
        return Http::pool(function (Pool $pool) use ($urls): void {
            foreach ($urls as $key => $url) {
                $pool->as($key)->withoutRedirecting()->connectTimeout(6)->timeout(18)
                    ->withUserAgent('Mozilla/5.0 (VakilNiti judges list)')->get($url);
            }
        }, concurrency: 4);
    }

    /** @return array<string, string> */
    private function districtPortraits(string $html, string $directory): array
    {
        $document = new DOMDocument;
        if (trim($html) === '' || ! @$document->loadHTML($html)) {
            return [];
        }

        $xpath = new DOMXPath($document);
        $districtHost = parse_url($directory, PHP_URL_HOST);
        $portraits = [];
        $rows = $xpath->query('//table[.//th[contains(translate(normalize-space(.), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "profile pic")]]//tr[td]');

        foreach ($rows ?: [] as $row) {
            $image = $xpath->query('./td[1]//img', $row)->item(0);
            $name = trim($xpath->query('./td[2]', $row)->item(0)?->textContent ?? '');
            if (! $image instanceof DOMElement || $name === '' || preg_match('/\bvacant\b/i', $name)) {
                continue;
            }

            $src = $image->getAttribute('src');
            if (preg_match('/\b(?:no[\s_-]*image|placeholder|default[\s_-]*profile)\b/i', $image->getAttribute('alt').' '.basename($src))) {
                continue;
            }

            $url = str_starts_with($src, '//') ? 'https:'.$src : (str_starts_with($src, '/') ? 'https://'.$districtHost.$src : $src);
            $imageHost = parse_url($url, PHP_URL_HOST);
            $key = CourtBench::judgeKey($name);
            if ($key !== '' && parse_url($url, PHP_URL_SCHEME) === 'https' && is_string($imageHost)
                && ($imageHost === $districtHost || str_ends_with($imageHost, '.s3waas.gov.in'))) {
                $portraits[$key] ??= $url;
            }
        }

        return $portraits;
    }

    /**
     * Judge name => photo address, read from a court's judges page.
     * Delhi HC names each photo in its alt text; the Supreme Court prints the name after the photo.
     *
     * @return array<string, string>
     */
    public function photosOn(string $url): array
    {
        try {
            $response = Http::timeout(30)->connectTimeout(10)->withUserAgent('Mozilla/5.0 (VakilNiti judges list)')->get($url);
        } catch (Throwable) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        $document = new DOMDocument;
        @$document->loadHTML($response->body());
        $xpath = new DOMXPath($document);
        $photos = [];

        foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " judge-name ")]') ?: [] as $nameElement) {
            $image = $xpath->query('preceding::img[1]', $nameElement)->item(0);

            if ($image instanceof DOMElement && trim($nameElement->textContent) !== '') {
                $photos[trim($nameElement->textContent)] = $image->getAttribute('src');
            }
        }

        // Pages that name each photo in its alt text ("Justice Dinesh Mehta", "justice_amit_bansal", "jasmeet-singh").
        // Only photos posted as content (/files/, /uploads/) count, never logos; generic alt texts are skipped.
        foreach ($xpath->query('//img[@alt]') ?: [] as $image) {
            $alt = trim((string) preg_replace('/[_-]+/', ' ', $image->getAttribute('alt')));
            $src = $image->getAttribute('src');
            $isContent = str_contains($src, '/files/') || str_contains($src, '/uploads/');

            if ($isContent && str_word_count($alt) >= 2 && ! preg_match('/^(judges|chief justice of india)$/i', $alt) && ! in_array($src, $photos, true)) {
                $photos[$alt] = $src;
            }
        }

        $base = parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST);

        return array_map(fn (string $src): string => str_starts_with($src, 'http') ? $src : $base.'/'.ltrim($src, '/'), array_filter($photos));
    }

    /**
     * Download one photo and keep a small copy (240px wide where the server can resize). Returns its path, or null.
     */
    private function savePhoto(string $key, string $imageUrl): ?string
    {
        try {
            $image = Http::timeout(30)->connectTimeout(10)->withUserAgent('Mozilla/5.0 (VakilNiti judges list)')->get($imageUrl);
        } catch (Throwable) {
            return null;
        }

        return $this->saveImage($key, $image);
    }

    private function saveImage(string $key, Response $image): ?string
    {
        $type = Str::before(strtolower((string) $image->header('Content-Type')), ';');
        if (! $image->successful() || ! in_array($type, ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'], true)) {
            return null;
        }

        $bytes = $image->body();

        if (function_exists('imagecreatefromstring') && ($source = @imagecreatefromstring($bytes))) {
            $width = imagesx($source);
            $height = imagesy($source);
            $small = imagecreatetruecolor(240, max(1, (int) round($height * 240 / max(1, $width))));
            imagecopyresampled($small, $source, 0, 0, 0, 0, imagesx($small), imagesy($small), $width, $height);
            ob_start();
            imagejpeg($small, null, 82);
            $bytes = (string) ob_get_clean();
        }

        $path = 'judges/'.strtolower($key).'.jpg';

        return Storage::disk('public')->put($path, $bytes) ? $path : null;
    }
}
