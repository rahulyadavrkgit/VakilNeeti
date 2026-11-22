<?php

namespace App\Http\Controllers;

use App\Models\LegalCase;
use App\Services\EcourtsAppExportParser;
use App\Services\IcsCalendarParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Imports cases from files the advocate exports themselves: the eCourts Services app
 * "My Cases" export, and a Google Calendar (.ics) export. Nothing is fetched from outside.
 */
class CaseFileImportController extends Controller
{
    private const MAX_CALENDAR_EVENTS = 500;

    private const MAX_UNZIPPED_BYTES = 20 * 1024 * 1024;

    /**
     * The largest upload this server accepts, in whole megabytes (PHP limits and our own cap).
     */
    public static function uploadLimitMegabytes(int $ownCapMegabytes): int
    {
        $toBytes = function (string $value): int {
            $number = (float) $value;

            return (int) match (strtoupper(substr(trim($value), -1))) {
                'G' => $number * 1024 ** 3,
                'M' => $number * 1024 ** 2,
                'K' => $number * 1024,
                default => $number,
            };
        };

        $limits = array_filter([
            $toBytes((string) ini_get('upload_max_filesize')),
            $toBytes((string) ini_get('post_max_size')),
            $ownCapMegabytes * 1024 ** 2,
        ]);

        return max(1, intdiv(min($limits), 1024 ** 2));
    }

    public function ecourtsPreview(Request $request, EcourtsAppExportParser $parser): View
    {
        $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:4'],
            'files.*' => ['file', 'max:5120', 'extensions:txt,json'],
        ], [
            'files.required' => 'Choose the myCases or hcMyCases file exported from the eCourts Services app.',
            'files.*.extensions' => 'Choose the .txt file exported from the eCourts Services app.',
            'files.*.max' => 'Each file must be smaller than 5 MB.',
            'files.*.uploaded' => 'This file is larger than the '.self::uploadLimitMegabytes(5).' MB this server accepts.',
        ]);

        $rows = collect();

        foreach ($request->file('files') as $file) {
            try {
                $rows = $rows->merge($parser->parse((string) file_get_contents($file->getRealPath())));
            } catch (\InvalidArgumentException $exception) {
                throw ValidationException::withMessages(['files' => $file->getClientOriginalName().': '.$exception->getMessage()]);
            }
        }

        $rows = $rows->keyBy('cnr');
        $existing = LegalCase::query()->whereIn('cnr_number', $rows->keys())->pluck('id', 'cnr_number');

        $rows = $rows->map(fn (array $row): array => $row + ['existing_case_id' => $existing[$row['cnr']] ?? null])
            ->sortBy(fn (array $row): array => [$row['case_status'] === 'pending' ? 0 : 1, $row['next_date'] ?? '9999-12-31'])
            ->values();

        $token = $this->remember('ecourts', $rows->all());

        return view('cases.import-ecourts-app', [
            'rows' => $rows,
            'token' => $token,
        ]);
    }

    public function ecourtsImport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:40'],
            'selected' => ['nullable', 'array'],
            'selected.*' => ['string', 'regex:/^[A-Z]{4}\d{12}$/'],
            'update_existing' => ['nullable', 'boolean'],
        ]);

        $rows = collect($this->recall('ecourts', $validated['token']))->keyBy('cnr');

        if ($rows->isEmpty()) {
            return redirect()->route('cases.find', ['method' => 'app'])->withErrors(['files' => 'That upload has expired. Upload the file again.']);
        }

        $selected = $rows->only($validated['selected'] ?? []);
        $updateExisting = (bool) ($validated['update_existing'] ?? false);
        $counts = ['added' => 0, 'updated' => 0, 'skipped' => 0];

        DB::transaction(function () use ($selected, $updateExisting, &$counts): void {
            foreach ($selected as $row) {
                $case = LegalCase::query()->where('cnr_number', $row['cnr'])->first();

                if ($case && ! $updateExisting) {
                    $counts['skipped']++;

                    continue;
                }

                $details = [
                    'case_status' => $row['case_status'],
                    'stage' => $row['stage'],
                    'disposed_on' => $row['disposed_on'],
                    'judge_name' => $row['judge_name'],
                ];

                if ($case) {
                    $case->update(array_filter($details, fn ($value) => $value !== null) + ['case_status' => $row['case_status']]);
                    $counts['updated']++;
                } else {
                    $case = LegalCase::create($details + [
                        'case_title' => $row['case_title'],
                        'case_number' => $row['case_number'],
                        'cnr_number' => $row['cnr'],
                        'case_type' => $row['case_type'],
                        'court_name' => $row['court_name'],
                        'court_complex' => $row['court_complex'],
                        'filing_number' => $row['filing_number'],
                        'source_type' => 'ecourts_app_export',
                        'source_reference' => 'eCourts Services app export',
                    ]);
                    $counts['added']++;
                }

                $this->syncHearing($case, $row);
            }
        });

        Cache::forget($this->cacheKey('ecourts', $validated['token']));

        return redirect()->route('cases.index')->with('success', $this->summary($counts));
    }

    public function calendarPreview(Request $request, IcsCalendarParser $parser): View
    {
        $validated = $request->validate([
            'calendar' => ['required', 'file', 'max:10240', 'extensions:ics,zip'],
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'likely_only' => ['nullable', 'boolean'],
        ], [
            'calendar.required' => 'Choose the .ics or .zip file exported from Google Calendar.',
            'calendar.extensions' => 'Choose a .ics file, or the .zip file Google Calendar gives you.',
            'calendar.uploaded' => 'This file is larger than the '.self::uploadLimitMegabytes(10).' MB this server accepts. Open the .zip and upload just your court calendar\x27s .ics file.',
            'calendar.max' => 'This file is larger than 10 MB. Open the .zip and upload just your court calendar\x27s .ics file.',
        ]);

        try {
            $events = collect($this->calendarEvents($request->file('calendar'), $parser));
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['calendar' => $exception->getMessage()]);
        }

        $cases = LegalCase::query()->get(['id', 'case_title', 'case_number', 'cnr_number', 'court_name']);
        $likelyOnly = (bool) ($validated['likely_only'] ?? false);

        $events = $events
            ->filter(fn (array $event): bool => $event['date'] >= $validated['from'] && $event['date'] <= $validated['to'])
            ->map(function (array $event) use ($cases): array {
                $match = $this->matchCase($event, $cases);
                $likely = $match !== null || $this->looksLikeCourtEvent($event);

                return $event + [
                    'matched_case_id' => $match?->id,
                    'likely' => $likely,
                    'default_action' => $match ? 'case:'.$match->id : ($likely ? 'new' : 'skip'),
                ];
            })
            ->when($likelyOnly, fn (Collection $events) => $events->where('likely', true))
            ->take(self::MAX_CALENDAR_EVENTS)
            ->values();

        return view('cases.import-calendar', [
            'events' => $events,
            'cases' => $cases->sortBy('case_title')->values(),
            'token' => $this->remember('calendar', $events->all()),
            'range' => [$validated['from'], $validated['to']],
            'likelyOnly' => $likelyOnly,
        ]);
    }

    public function calendarImport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:40'],
            'actions' => ['nullable', 'array'],
            'actions.*' => ['string', 'regex:/^(skip|new|case:\d+)$/'],
        ]);

        $events = $this->recall('calendar', $validated['token']);

        if ($events === []) {
            return redirect()->route('cases.find', ['method' => 'calendar'])->withErrors(['calendar' => 'That upload has expired. Upload the calendar file again.']);
        }

        $counts = ['cases' => 0, 'hearings' => 0, 'skipped' => 0];

        DB::transaction(function () use ($events, $validated, &$counts): void {
            $newCases = [];

            foreach ($validated['actions'] ?? [] as $index => $action) {
                $event = $events[$index] ?? null;

                if (! $event || $action === 'skip') {
                    continue;
                }

                if ($action === 'new') {
                    $key = Str::lower($event['title']);
                    $case = $newCases[$key] ??= tap(LegalCase::create([
                        'case_title' => $event['title'],
                        'court_name' => $event['location'] ?: 'Court not recorded',
                        'case_status' => 'pending',
                        'source_type' => 'google_calendar',
                        'source_reference' => 'Imported from Google Calendar',
                    ]), function () use (&$counts): void {
                        $counts['cases']++;
                    });
                } else {
                    $case = LegalCase::query()->find((int) substr($action, 5));
                }

                if (! $case || $case->hearings()->whereDate('hearing_date', $event['date'])->exists()) {
                    $counts['skipped']++;

                    continue;
                }

                $isUpcoming = $event['date'] >= today()->toDateString();

                $case->hearings()->create([
                    'hearing_date' => $event['date'],
                    'hearing_time' => $event['time'],
                    'hearing_stage' => $event['title'],
                    'court_room' => $event['location'] ? mb_substr($event['location'], 0, 100) : null,
                    'proceedings' => $event['description'],
                    'next_hearing_date' => $isUpcoming ? $event['date'] : null,
                    'source_type' => 'google_calendar',
                    'source_reference' => $event['uid'],
                ]);
                $counts['hearings']++;
            }
        });

        Cache::forget($this->cacheKey('calendar', $validated['token']));

        $message = trim(
            ($counts['hearings'] === 1 ? '1 hearing added' : "{$counts['hearings']} hearings added")
            .($counts['cases'] ? ', with '.($counts['cases'] === 1 ? '1 new case' : "{$counts['cases']} new cases") : '').'.'
            .($counts['skipped'] ? ' '.$counts['skipped'].' already on those dates were skipped.' : '')
        );

        return redirect()->route('cases.calendar', ['view' => 'list'])->with('success', $message);
    }

    /**
     * Record the latest listing from the export as a hearing, without duplicating dates already recorded.
     *
     * @param  array<string, mixed>  $row
     */
    private function syncHearing(LegalCase $case, array $row): void
    {
        $last = $row['last_date'];
        $next = $row['next_date'];
        $today = today()->toDateString();

        [$hearingDate, $nextDate] = match (true) {
            $next && $last && $next !== $last => [$last, $next],
            (bool) $next => [$next, $next >= $today ? $next : null],
            (bool) $last => [$last, null],
            default => [null, null],
        };

        if (! $hearingDate) {
            return;
        }

        $attributes = [
            'hearing_stage' => $row['stage'],
            'judge_name' => $row['judge_name'],
            'next_hearing_date' => $nextDate,
            'proceedings' => $row['disposal'] ? 'Disposed: '.$row['disposal'] : null,
        ];

        $hearing = $case->hearings()->whereDate('hearing_date', $hearingDate)->first();

        if ($hearing) {
            if (! $hearing->isManual()) {
                $hearing->update(array_filter($attributes, fn ($value) => $value !== null) + ['next_hearing_date' => $nextDate]);
            }

            return;
        }

        $case->hearings()->create($attributes + [
            'hearing_date' => $hearingDate,
            'source_type' => 'ecourts_app_export',
            'source_reference' => $row['cnr'],
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function calendarEvents(UploadedFile $file, IcsCalendarParser $parser): array
    {
        if (strtolower($file->getClientOriginalExtension()) !== 'zip') {
            return $parser->parse((string) file_get_contents($file->getRealPath()));
        }

        $zip = new \ZipArchive;

        if ($zip->open($file->getRealPath()) !== true) {
            throw new \InvalidArgumentException('This .zip file could not be opened. Upload the .zip from Google Calendar again, or one .ics file from it.');
        }

        $events = [];
        $totalBytes = 0;

        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);

                if (! $stat || ! str_ends_with(strtolower($stat['name']), '.ics')) {
                    continue;
                }

                $totalBytes += $stat['size'];

                if ($totalBytes > self::MAX_UNZIPPED_BYTES) {
                    throw new \InvalidArgumentException('This calendar is too large. Export a single calendar and upload its .ics file.');
                }

                $events = array_merge($events, $parser->parse((string) $zip->getFromIndex($index)));
            }
        } finally {
            $zip->close();
        }

        if ($events === []) {
            throw new \InvalidArgumentException('No calendar events were found in this .zip file.');
        }

        usort($events, fn (array $a, array $b): int => [$a['date'], $a['time'] ?? ''] <=> [$b['date'], $b['time'] ?? '']);

        return $events;
    }

    /**
     * The signed-in user's case this event is about: by CNR, case number or title.
     *
     * @param  array<string, mixed>  $event
     * @param  Collection<int, LegalCase>  $cases
     */
    private function matchCase(array $event, Collection $cases): ?LegalCase
    {
        $text = Str::upper($event['title'].' '.$event['description']);
        $compact = (string) preg_replace('/[^A-Z0-9]/', '', $text);
        $title = Str::upper($event['title']);

        return $cases->first(function (LegalCase $case) use ($text, $compact, $title): bool {
            $cnr = Str::upper((string) $case->cnr_number);
            $number = (string) preg_replace('/[^A-Z0-9]/', '', Str::upper((string) $case->case_number));
            $caseTitle = Str::upper((string) $case->case_title);

            return ($cnr !== '' && str_contains($text, $cnr))
                || (strlen($number) >= 5 && str_contains($compact, $number))
                || (mb_strlen($caseTitle) >= 6 && (str_contains($text, $caseTitle) || (mb_strlen($title) >= 6 && str_contains($caseTitle, $title))));
        });
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function looksLikeCourtEvent(array $event): bool
    {
        $text = $event['title'].' '.$event['location'].' '.$event['description'];

        return preg_match('/\b(vs?\.?|versus|v\/s|hearing|court|case|bail|trial|petition|appeal|suit|tribunal|judge|cnr)\b/i', $text) === 1
            || preg_match('/\/\s*\d+\s*\/\s*(19|20)\d{2}/', $text) === 1;
    }

    /**
     * Keep parsed rows for an hour between the preview and the import.
     *
     * @param  array<int, mixed>  $rows
     */
    private function remember(string $kind, array $rows): string
    {
        $token = Str::random(40);
        Cache::put($this->cacheKey($kind, $token), $rows, now()->addHour());

        return $token;
    }

    /**
     * @return array<int, mixed>
     */
    private function recall(string $kind, string $token): array
    {
        return (array) Cache::get($this->cacheKey($kind, $token), []);
    }

    private function cacheKey(string $kind, string $token): string
    {
        return "case-import:{$kind}:".auth()->id().':'.$token;
    }

    /**
     * @param  array{added: int, updated: int, skipped: int}  $counts
     */
    private function summary(array $counts): string
    {
        $parts = [($counts['added'] === 1 ? '1 case added' : "{$counts['added']} cases added")];

        if ($counts['updated']) {
            $parts[] = ($counts['updated'] === 1 ? '1 case' : "{$counts['updated']} cases").' updated with the latest dates';
        }

        if ($counts['skipped']) {
            $parts[] = $counts['skipped'].' already in your cases were left as they were';
        }

        return implode(', ', $parts).'.';
    }
}
