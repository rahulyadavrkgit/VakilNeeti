<?php

namespace App\Http\Controllers;

use App\Models\CourtHearing;
use App\Models\CourtRecordImport;
use App\Models\LegalCase;
use App\Models\User;
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

    private function recordAttempt(string $kind, string $status, ?int $retry, array $extra = []): CourtRecordImport
    {
        $parent = CourtRecordImport::retryParent($retry, $kind);

        return CourtRecordImport::create([
            'source_name' => $kind === 'app' ? 'eCourts app export' : 'Calendar export',
            'raw_content' => '', 'import_status' => $status,
            'parsed_data' => array_filter(['method' => $kind, 'retry_of' => $parent?->id]) + $extra,
        ]);
    }

    private function markAttempt(CourtRecordImport $attempt, string $status, array $result = []): void
    {
        $data = array_merge($attempt->parsed_data ?? [], $result);
        $attempt->update(['import_status' => $status, 'parsed_data' => $data]);
        $attempt->auditRetryOutcome(auth()->id());
    }

    private function attempt(string $method, string $token): ?CourtRecordImport
    {
        return CourtRecordImport::query()->where('import_status', 'preview')->where('parsed_data->method', $method)
            ->where('parsed_data->token_hash', hash('sha256', $token))->first();
    }

    private function completeAttempt(CourtRecordImport $attempt, array &$counts, array &$entries, \Closure $import): bool
    {
        try {
            return DB::transaction(function () use ($attempt, &$counts, &$entries, $import): bool {
                $claimed = CourtRecordImport::query()->whereKey($attempt->id)->where('import_status', 'preview')
                    ->update(['import_status' => 'processing']);
                if (! $claimed) {
                    return false;
                }

                User::query()->whereKey($attempt->user_id)->lockForUpdate()->firstOrFail();
                $import();
                $this->markAttempt($attempt, empty($counts['failed']) ? 'imported' : 'partial', ['counts' => $counts, 'entries' => $entries]);

                return true;
            });
        } catch (\Throwable $exception) {
            report($exception);
            $this->failPendingAttempt($attempt, 'The save failed and no changes from this attempt were kept.');

            return false;
        }
    }

    private function failPendingAttempt(CourtRecordImport $attempt, string $reason): void
    {
        DB::transaction(function () use ($attempt, $reason): void {
            $pending = CourtRecordImport::query()->where('import_status', 'preview')->lockForUpdate()->find($attempt->id);
            if ($pending) {
                $this->markAttempt($pending, 'failed', ['reason' => $reason]);
            }
        });
    }

    public function results(CourtRecordImport $import): View
    {
        abort_unless(isset($import->parsed_data['counts']), 404);

        return view('cases.import-results', ['import' => $import, 'entries' => $import->parsed_data['entries'] ?? []]);
    }

    public function ecourtsPreview(Request $request, EcourtsAppExportParser $parser): View
    {
        $validated = $request->validate([
            'retry' => ['nullable', 'integer', 'min:1'],
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
                $attempt = $this->recordAttempt('app', 'preview', $validated['retry'] ?? null);
                $this->markAttempt($attempt, 'failed', ['reason' => 'Could not read the eCourts export. Check the file format and upload a corrected export.']);
                throw ValidationException::withMessages(['files' => $file->getClientOriginalName().': '.$exception->getMessage()]);
            }
        }

        $rows = $rows->keyBy('cnr');
        $existing = LegalCase::query()->whereIn('cnr_number', $rows->keys())->pluck('id', 'cnr_number');

        $rows = $rows->map(fn (array $row): array => $row + ['existing_case_id' => $existing[$row['cnr']] ?? null])
            ->sortBy(fn (array $row): array => [$row['case_status'] === 'pending' ? 0 : 1, $row['next_date'] ?? '9999-12-31'])
            ->values();

        $token = $this->remember('ecourts', $rows->all());
        $this->recordAttempt('app', 'preview', $validated['retry'] ?? null, ['token_hash' => hash('sha256', $token)]);

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

        $attempt = $this->attempt('app', $validated['token']);
        if ($rows->isEmpty() || ! $attempt) {
            if ($attempt) {
                $this->failPendingAttempt($attempt, 'The preview expired before saving.');
            }

            return redirect()->route('cases.find', ['method' => 'app'])->withErrors(['files' => 'That upload has expired. Upload the file again.']);
        }

        $selected = $rows->only($validated['selected'] ?? []);
        $updateExisting = (bool) ($validated['update_existing'] ?? false);
        $counts = ['added' => 0, 'updated' => 0, 'skipped' => 0];

        $entries = [];
        $completed = $this->completeAttempt($attempt, $counts, $entries, function () use ($selected, $updateExisting, &$counts, &$entries): void {
            foreach ($selected as $row) {
                $case = LegalCase::query()->where('cnr_number', $row['cnr'])->first();
                $reference = $row['case_number'] ?: $row['cnr'];

                if ($case && ! $updateExisting) {
                    $counts['skipped']++;
                    $entries[] = ['reference' => $reference, 'outcome' => 'Skipped - already in your cases'];

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
                    $entries[] = ['reference' => $reference, 'outcome' => 'Updated'];
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
                    $entries[] = ['reference' => $reference, 'outcome' => 'Added'];
                }

                $this->syncHearing($case, $row);
            }
        });

        Cache::forget($this->cacheKey('ecourts', $validated['token']));
        if (! $completed) {
            return redirect()->route('cases.import.history')->with('error', 'The import did not finish. Check import history before retrying.');
        }

        return redirect()->route('cases.index')->with('success', $this->summary($counts));
    }

    public function calendarPreview(Request $request, IcsCalendarParser $parser): View
    {
        $validated = $request->validate([
            'retry' => ['nullable', 'integer', 'min:1'],
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
            $attempt = $this->recordAttempt('calendar', 'preview', $validated['retry'] ?? null);
            $this->markAttempt($attempt, 'failed', ['reason' => 'Could not read the calendar export. Check the file format and upload a corrected export.']);
            throw ValidationException::withMessages(['calendar' => $exception->getMessage()]);
        }

        $cases = LegalCase::query()->get(['id', 'case_title', 'case_number', 'cnr_number', 'court_name']);
        $likelyOnly = (bool) ($validated['likely_only'] ?? false);

        // What is already in the diary: hearing dates per case, and events imported before.
        $savedDates = CourtHearing::query()->whereIn('case_id', $cases->pluck('id'))->whereNotNull('hearing_date')
            ->get(['case_id', 'hearing_date'])
            ->map(fn (CourtHearing $hearing): string => $hearing->case_id.'|'.$hearing->hearing_date->toDateString())
            ->flip();
        $importedUids = CourtHearing::query()->where('source_type', 'google_calendar')->whereNotNull('source_reference')
            ->get(['source_reference', 'hearing_date'])
            ->map(fn (CourtHearing $hearing): string => $hearing->source_reference.'|'.$hearing->hearing_date?->toDateString())
            ->flip();

        $events = $events
            ->filter(fn (array $event): bool => $event['date'] >= $validated['from'] && $event['date'] <= $validated['to'])
            ->map(function (array $event) use ($cases, $savedDates, $importedUids): array {
                $match = $this->matchCase($event, $cases);
                $likely = $match !== null || $this->looksLikeCourtEvent($event);
                $alreadySaved = ($match && $savedDates->has($match->id.'|'.$event['date']))
                    || (filled($event['uid']) && $importedUids->has($event['uid'].'|'.$event['date']));

                return $event + [
                    'matched_case_id' => $match?->id,
                    'likely' => $likely,
                    'already_saved' => $alreadySaved,
                    'default_action' => $alreadySaved ? 'skip' : ($match ? 'case:'.$match->id : ($likely ? 'new' : 'skip')),
                ];
            })
            ->when($likelyOnly, fn (Collection $events) => $events->where('likely', true))
            ->take(self::MAX_CALENDAR_EVENTS)
            ->values();

        $token = $this->remember('calendar', $events->all());
        $this->recordAttempt('calendar', 'preview', $validated['retry'] ?? null, ['token_hash' => hash('sha256', $token)]);

        return view('cases.import-calendar', [
            'events' => $events,
            'cases' => $cases->sortBy('case_title')->values(),
            'token' => $token,
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

        $attempt = $this->attempt('calendar', $validated['token']);
        if ($events === [] || ! $attempt) {
            if ($attempt) {
                $this->failPendingAttempt($attempt, 'The preview expired before saving or contained no selected events.');
            }

            return redirect()->route('cases.find', ['method' => 'calendar'])->withErrors(['calendar' => 'That upload has expired. Upload the calendar file again.']);
        }

        $counts = ['cases' => 0, 'hearings' => 0, 'skipped' => 0, 'failed' => 0];

        $entries = [];
        $completed = $this->completeAttempt($attempt, $counts, $entries, function () use ($events, $validated, &$counts, &$entries): void {
            $newCases = [];

            foreach ($validated['actions'] ?? [] as $index => $action) {
                $event = $events[$index] ?? null;

                if (! $event) {
                    $counts['failed']++;
                    $entries[] = ['reference' => 'Calendar selection', 'outcome' => 'Failed - preview entry unavailable'];

                    continue;
                }
                if ($action === 'skip') {
                    continue;
                }
                $reference = $event['date'].' '.$event['title'];
                if ($action === 'new' && filled($event['uid']) && CourtHearing::query()->where('source_type', 'google_calendar')
                    ->where('source_reference', $event['uid'])->whereDate('hearing_date', $event['date'])->exists()) {
                    $counts['skipped']++;
                    $entries[] = ['reference' => $reference, 'outcome' => 'Skipped - already imported'];

                    continue;
                }

                if ($action === 'new') {
                    $key = Str::lower(trim($event['title']));
                    // A case with this title already in the diary is used, never added a second time.
                    $case = $newCases[$key] ??= LegalCase::query()->whereRaw('lower(trim(case_title)) = ?', [$key])->first() ?? tap(LegalCase::create([
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

                if (! $case) {
                    $counts['failed']++;
                    $entries[] = ['reference' => $reference, 'outcome' => 'Failed - selected case unavailable'];

                    continue;
                }
                if ($case->hearings()->whereDate('hearing_date', $event['date'])->exists()) {
                    $counts['skipped']++;
                    $entries[] = ['reference' => $reference, 'outcome' => 'Skipped - hearing already saved on that date'];

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
                $entries[] = ['reference' => $reference, 'outcome' => 'Hearing added'];
            }
        });

        Cache::forget($this->cacheKey('calendar', $validated['token']));
        if (! $completed) {
            return redirect()->route('cases.import.history')->with('error', 'The import did not finish. Check import history before retrying.');
        }

        $message = trim(
            ($counts['hearings'] === 1 ? '1 hearing added' : "{$counts['hearings']} hearings added")
            .($counts['cases'] ? ', with '.($counts['cases'] === 1 ? '1 new case' : "{$counts['cases']} new cases") : '').'.'
            .($counts['skipped'] ? ' '.$counts['skipped'].' already on those dates were skipped.' : '')
            .($counts['failed'] ? ' '.$counts['failed'].' could not be saved. Check import history to retry.' : '')
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
