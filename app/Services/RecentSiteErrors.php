<?php

namespace App\Services;

use Illuminate\Support\Carbon;

class RecentSiteErrors
{
    /**
     * The message of each error line logged after the given moment (stack traces are skipped).
     *
     * @return list<string>
     */
    public function since(Carbon $since): array
    {
        $messages = [];

        foreach ($this->logFilesSince($since) as $file) {
            array_push($messages, ...$this->errorsIn($file, $since));
        }

        return $messages;
    }

    /**
     * The log files that can hold lines from that moment on: one file a day (laravel-2026-11-26.log), or the
     * single laravel.log, whichever the server writes.
     *
     * @return list<string>
     */
    private function logFilesSince(Carbon $since): array
    {
        $daily = array_filter(
            glob(storage_path('logs/laravel-*.log')) ?: [],
            fn (string $file): bool => preg_match('/laravel-(\d{4}-\d{2}-\d{2})\.log$/', $file, $day) === 1 && $day[1] >= $since->toDateString(),
        );
        $single = storage_path('logs/laravel.log');

        return array_values(array_filter([...$daily, $single], 'is_readable'));
    }

    /**
     * @return list<string>
     */
    private function errorsIn(string $file, Carbon $since): array
    {
        // Read only the last 2 MB: a day of errors fits easily, and a very large log stays fast.
        $handle = fopen($file, 'r');
        $size = filesize($file);
        fseek($handle, max(0, $size - 2_000_000));
        $text = stream_get_contents($handle);
        fclose($handle);

        // Lines from test runs ("testing.ERROR") share this file on a developer machine; they are not site errors.
        preg_match_all('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (?!testing\.)\w+\.(?:ERROR|CRITICAL|ALERT|EMERGENCY): (.{1,160})/m', (string) $text, $matches, PREG_SET_ORDER);

        $messages = [];

        foreach ($matches as [, $at, $message]) {
            if (Carbon::parse($at, config('app.timezone'))->gte($since)) {
                // Strip view paths and ids so the same error groups together.
                $messages[] = trim((string) preg_replace(['/\(View: [^)]*\)/', '/\s+\{.*$/', '/\d{3,}/'], ['', '', '#'], $message));
            }
        }

        return $messages;
    }
}
