<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Works out whether a court's display board may be shown inside VakilNiti (in a frame).
 *
 * Courts decide this with the X-Frame-Options and Content-Security-Policy headers. VakilNiti
 * respects that choice: a board is embedded only when its court allows it, never by proxying.
 */
class CourtBoardEmbedCheck
{
    private const CACHE_HOURS = 48;

    /**
     * The last known answer, without contacting the court (false until a check says otherwise).
     */
    public function isEmbeddable(string $url): bool
    {
        return (bool) Cache::get($this->cacheKey($url), false);
    }

    /**
     * Ask the court's server now and remember the answer.
     */
    public function refresh(string $url): bool
    {
        try {
            $response = Http::timeout(12)->withUserAgent('VakilNiti display-board check')->get($url);
            $allowed = $response->successful() && $this->headersAllowFraming(
                (string) $response->header('X-Frame-Options'),
                (string) $response->header('Content-Security-Policy'),
            );
        } catch (\Throwable) {
            $allowed = false;
        }

        Cache::put($this->cacheKey($url), $allowed, now()->addHours(self::CACHE_HOURS));

        return $allowed;
    }

    public function headersAllowFraming(string $frameOptions, string $contentSecurityPolicy): bool
    {
        if (in_array(strtoupper(trim($frameOptions)), ['DENY', 'SAMEORIGIN'], true)) {
            return false;
        }

        if (preg_match('/frame-ancestors\s+([^;]+)/i', $contentSecurityPolicy, $match)) {
            $sources = preg_split('/\s+/', trim($match[1])) ?: [];
            $ownHost = parse_url((string) config('app.url'), PHP_URL_HOST);

            return in_array('*', $sources, true)
                || ($ownHost && collect($sources)->contains(fn (string $source): bool => str_contains($source, $ownHost)));
        }

        return true;
    }

    private function cacheKey(string $url): string
    {
        return 'court-board-embeddable:'.md5($url);
    }
}
