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
        $file = storage_path('logs/laravel.log');

        if (! is_readable($file)) {
            return [];
        }

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
