<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * Reads events from an iCalendar (.ics) file, such as a Google Calendar export.
 * Only what VakilNiti needs is read: start, title, place and notes.
 */
class IcsCalendarParser
{
    /**
     * @return list<array{uid: string, date: string, time: ?string, title: string, location: ?string, description: ?string}>
     *
     * @throws \InvalidArgumentException when the text is not a calendar file
     */
    public function parse(string $contents): array
    {
        if (! str_contains($contents, 'BEGIN:VCALENDAR')) {
            throw new \InvalidArgumentException('This is not a calendar file. In Google Calendar open Settings → Import & export → Export, then upload the .zip or one .ics file from it.');
        }

        // Long lines are folded onto the next line, which starts with a space or tab.
        $lines = preg_split('/\r\n|\n|\r/', (string) preg_replace('/\r?\n[ \t]/', '', $contents)) ?: [];

        $events = [];
        $event = null;

        foreach ($lines as $line) {
            if ($line === 'BEGIN:VEVENT') {
                $event = [];

                continue;
            }

            if ($line === 'END:VEVENT') {
                if ($event !== null && ($parsed = $this->toEvent($event))) {
                    $events[$parsed['uid'].'|'.$parsed['date']] = $parsed;
                }
                $event = null;

                continue;
            }

            if ($event === null || ! str_contains($line, ':')) {
                continue;
            }

            [$nameAndParams, $value] = explode(':', $line, 2);
            $parts = explode(';', $nameAndParams);
            $name = strtoupper(array_shift($parts));
            $params = [];

            foreach ($parts as $part) {
                [$key, $paramValue] = array_pad(explode('=', $part, 2), 2, '');
                $params[strtoupper($key)] = trim($paramValue, '"');
            }

            $event[$name] ??= ['value' => $value, 'params' => $params];
        }

        $events = array_values($events);
        usort($events, fn (array $a, array $b): int => [$a['date'], $a['time'] ?? ''] <=> [$b['date'], $b['time'] ?? '']);

        return $events;
    }

    /**
     * @param  array<string, array{value: string, params: array<string, string>}>  $event
     * @return array{uid: string, date: string, time: ?string, title: string, location: ?string, description: ?string}|null
     */
    private function toEvent(array $event): ?array
    {
        if (! isset($event['DTSTART']) || strtoupper($event['STATUS']['value'] ?? '') === 'CANCELLED') {
            return null;
        }

        $start = $this->start($event['DTSTART']['value'], $event['DTSTART']['params']);

        if (! $start) {
            return null;
        }

        $title = $this->text($event['SUMMARY']['value'] ?? '') ?? '(No title)';

        return [
            'uid' => mb_substr($event['UID']['value'] ?? md5($title.$start['date']), 0, 255),
            'date' => $start['date'],
            'time' => $start['time'],
            'title' => mb_substr($title, 0, 255),
            'location' => ($location = $this->text($event['LOCATION']['value'] ?? '')) ? mb_substr($location, 0, 255) : null,
            'description' => ($description = $this->text($event['DESCRIPTION']['value'] ?? '')) ? mb_substr(strip_tags($description), 0, 2000) : null,
        ];
    }

    /**
     * @param  array<string, string>  $params
     * @return array{date: string, time: ?string}|null
     */
    private function start(string $value, array $params): ?array
    {
        try {
            if (($params['VALUE'] ?? '') === 'DATE' || preg_match('/^\d{8}$/', $value)) {
                return ['date' => Carbon::createFromFormat('!Ymd', substr($value, 0, 8))->toDateString(), 'time' => null];
            }

            if (! preg_match('/^(\d{8}T\d{6})(Z?)$/', $value, $match)) {
                return null;
            }

            $timezone = $match[2] === 'Z' ? 'UTC' : ($params['TZID'] ?? config('app.timezone'));

            try {
                $moment = Carbon::createFromFormat('Ymd\THis', $match[1], $timezone);
            } catch (\Throwable) {
                $moment = Carbon::createFromFormat('Ymd\THis', $match[1], config('app.timezone'));
            }

            $moment = $moment->setTimezone(config('app.timezone'));

            return ['date' => $moment->toDateString(), 'time' => $moment->format('H:i')];
        } catch (\Throwable) {
            return null;
        }
    }

    private function text(string $value): ?string
    {
        $value = str_replace(['\\n', '\\N', '\\,', '\\;', '\\\\'], ["\n", "\n", ',', ';', '\\'], $value);
        $value = trim((string) preg_replace('/[ \t]+/', ' ', $value));

        return $value === '' ? null : $value;
    }
}
