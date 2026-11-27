<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Live court display boards shown inside VakilNiti, read from the data each court publishes for its
 * own public board (the courts do not allow their board pages inside other websites).
 *
 * Every visitor shares one copy, refreshed at most every 10 seconds, so a court's server sees about
 * six requests a minute however many people are watching. When a court cannot be reached, the last
 * good copy is shown and marked as not current.
 */
class LiveCourtBoard
{
    private const FRESH_SECONDS = 5;

    private const USABLE_SECONDS = 300;

    private const RETRY_MINUTES = 10;

    /**
     * @var array<string, array{name: string, short: string, source: string, legend: string}>
     */
    public const COURTS = [
        'supreme-court' => [
            'name' => 'Supreme Court of India',
            'short' => 'Supreme Court',
            'source' => 'https://cdb.sci.gov.in/',
            'legend' => 'Item running in each court, with the case being heard.',
        ],
        'delhi-high-court' => [
            'name' => 'High Court of Delhi',
            'short' => 'Delhi High Court',
            'source' => 'https://delhihighcourt.nic.in/app/display-board',
            'legend' => 'Item running in each court. R1 to R9 are the Registrar courts.',
        ],
    ];

    /**
     * The Supreme Court's board covers courts 1 to 19 and the two Registrar courts (21 and 22).
     *
     * @var list<int>
     */
    private const SUPREME_COURT_ROOMS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 21, 22];

    /**
     * Delhi High Court courts 52 to 60 are the Registrar courts R1 to R9.
     */
    private const DELHI_HIGH_COURT_FIRST_REGISTRAR = 52;

    private const DELHI_HIGH_COURT_ROOMS = 60;

    /**
     * The board for a court, or null for a court we have no live board for.
     *
     * @return array{court: string, name: string, short: string, source: string, legend: string, ok: bool, updated_at: ?string, rooms: list<array{room: string, label: string, status: string, item: ?string, case_number: ?string, parties: ?string, message: ?string}>}|null
     */
    public function board(string $court): ?array
    {
        if (! isset(self::COURTS[$court])) {
            return null;
        }

        // The court's board is at most a few seconds old: a copy is kept for FRESH_SECONDS, then whoever asks next
        // fetches it from the court (one fetch at a time; everyone else meanwhile gets the copy, at most 5 minutes old).
        $board = Cache::get("live-board:{$court}");

        if (! $board || now()->getTimestamp() - ($board['fetched'] ?? 0) >= self::FRESH_SECONDS) {
            $lock = Cache::lock("live-board-fetch:{$court}", 20);

            if ($lock->get()) {
                try {
                    $board = $this->fresh($court);
                    Cache::put("live-board:{$court}", $board + ['fetched' => now()->getTimestamp()], self::USABLE_SECONDS);
                } finally {
                    $lock->release();
                }
            }
        }

        $board ??= $this->lastKnown($court);
        unset($board['fetched']);

        return ['court' => $court] + self::COURTS[$court] + $board;
    }

    /**
     * The court's board now, or the last good copy marked as not current when the court does not answer.
     *
     * @return array{ok: bool, updated_at: ?string, rooms: list<array<string, mixed>>}
     */
    private function fresh(string $court): array
    {
        // After a failure, wait before asking again so no visitor sits through the court's timeout.
        $rooms = Cache::has("live-board-down:{$court}") ? null : $this->fetch($court);

        if ($rooms !== null) {
            $fresh = ['ok' => true, 'updated_at' => now()->toIso8601String(), 'rooms' => $rooms];
            Cache::put("live-board-last:{$court}", $fresh, now()->addDay());

            return $fresh;
        }

        Cache::add("live-board-down:{$court}", true, now()->addMinutes(self::RETRY_MINUTES));

        return $this->lastKnown($court);
    }

    /**
     * @return array{ok: bool, updated_at: ?string, rooms: list<array<string, mixed>>}
     */
    private function lastKnown(string $court): array
    {
        $last = Cache::get("live-board-last:{$court}");

        return ['ok' => false, 'updated_at' => $last['updated_at'] ?? null, 'rooms' => $last['rooms'] ?? []];
    }

    /**
     * False when the court's data has not been reachable lately and there is no recent copy to show,
     * so the board viewer offers the court's own board instead. Never contacts the court.
     */
    public function isAvailable(string $court): bool
    {
        return ! Cache::has("live-board-down:{$court}") || Cache::has("live-board-last:{$court}");
    }

    /**
     * Which live board a case's court is on ("supreme-court", "delhi-high-court"), if any.
     */
    public static function boardFor(?string $courtName): ?string
    {
        $court = Str::lower((string) $courtName);

        return match (true) {
            str_contains($court, 'supreme court') => 'supreme-court',
            str_contains($court, 'high court') && str_contains($court, 'delhi') => 'delhi-high-court',
            default => null,
        };
    }

    /**
     * The board's room number for a case's court room: "Court No. 12", "C-12" or "12" give "12";
     * Registrar courts ("R3", "RC 2", "Registrar Court 1") map to the board's own numbering.
     */
    public static function roomKey(string $board, ?string $room): ?string
    {
        $room = trim((string) $room);

        if ($room === '') {
            return null;
        }

        if (preg_match('/^(?:registrar(?:\s+court)?|rc|r)\s*[-.:]?\s*(?:no\.?\s*)?(\d{1,2})$/i', $room, $registrar)) {
            $number = (int) $registrar[1];

            return match ($board) {
                'supreme-court' => $number >= 1 && $number <= 2 ? (string) (20 + $number) : null,
                'delhi-high-court' => $number >= 1 && $number <= 9 ? (string) (self::DELHI_HIGH_COURT_FIRST_REGISTRAR + $number - 1) : null,
                default => null,
            };
        }

        return preg_match('/(\d{1,3})/', $room, $number) ? (string) (int) $number[1] : null;
    }

    /**
     * @return list<array{room: string, label: string, status: string, item: ?string, case_number: ?string, parties: ?string, message: ?string}>|null
     */
    private function fetch(string $court): ?array
    {
        try {
            return match ($court) {
                'supreme-court' => $this->supremeCourt(),
                'delhi-high-court' => $this->delhiHighCourt(),
                default => null,
            };
        } catch (ConnectionException $exception) {
            // A court site that does not answer (the Supreme Court board cannot be reached from outside India) is a
            // warning, not a site error, so it does not raise the morning error alert every day.
            Log::warning("Live board {$court} could not be reached", ['message' => $exception->getMessage()]);

            return null;
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * @return list<array{room: string, label: string, status: string, item: ?string, case_number: ?string, parties: ?string, message: ?string}>|null
     */
    private function supremeCourt(): ?array
    {
        $response = $this->client()->get('https://cdb.sci.gov.in/index.php', [
            'courtListCsv' => implode(',', self::SUPREME_COURT_ROOMS),
            'request' => 'display_full',
            'requestType' => 'ajax',
        ]);

        $rows = $response->successful() ? $response->json('listedItemDetails') : null;

        if (! is_array($rows)) {
            return null;
        }

        return collect($rows)->map(function (array $row): array {
            $number = (int) ($row['court_no'] ?? 0);
            $item = $this->clean($row['item_no'] ?? null);
            $caseNumber = $this->clean($row['registration_number_display'] ?? null);
            $notInSession = $item === null || Str::lower((string) $caseNumber) === 'not in session';
            $petitioner = $this->clean($row['petitioner_name'] ?? null);
            $respondent = $this->clean($row['respondent_name'] ?? null);

            return [
                'room' => (string) $number,
                'label' => $number > 20 ? 'Registrar Court '.($number - 20) : 'Court '.$number,
                'status' => $notInSession ? 'not_in_session' : 'running',
                'item' => $notInSession ? null : $item,
                'case_number' => $notInSession ? null : $caseNumber,
                'parties' => $notInSession || (! $petitioner && ! $respondent)
                    ? null
                    : collect([$petitioner, $respondent])->filter()->map(fn (string $party): string => Str::title(Str::squish($party)))->implode(' vs '),
                'message' => $this->clean($row['court_message'] ?? null),
            ];
        })->filter(fn (array $room): bool => $room['room'] !== '0')->values()->all();
    }

    /**
     * @return list<array{room: string, label: string, status: string, item: ?string, case_number: ?string, parties: ?string, message: ?string}>|null
     */
    private function delhiHighCourt(): ?array
    {
        $response = $this->client()->get('https://delhihighcourt.nic.in/app/courtdata');
        $items = $response->successful() ? $response->json('all_courts') : null;

        if (! is_array($items)) {
            return null;
        }

        $rooms = [];

        for ($number = 1; $number <= self::DELHI_HIGH_COURT_ROOMS; $number++) {
            // The court's own legend: "*" is not in session, "X" is a board that is exhausted.
            $value = trim((string) ($items[(string) $number] ?? '*'));
            $status = match (Str::upper($value)) {
                '*', '', '--' => 'not_in_session',
                'X' => 'exhausted',
                default => 'running',
            };

            $rooms[] = [
                'room' => (string) $number,
                'label' => $number >= self::DELHI_HIGH_COURT_FIRST_REGISTRAR ? 'R'.($number - self::DELHI_HIGH_COURT_FIRST_REGISTRAR + 1) : 'Court '.$number,
                'status' => $status,
                'item' => $status === 'running' ? $value : null,
                'case_number' => null,
                'parties' => null,
                'message' => null,
            ];
        }

        return $rooms;
    }

    private function client(): PendingRequest
    {
        return Http::timeout(8)->connectTimeout(4)->acceptJson()->withUserAgent('VakilNiti live board');
    }

    /**
     * Plain text from a board field: no tags, entities or non-breaking spaces; null when empty.
     */
    private function clean(mixed $value): ?string
    {
        $text = Str::squish(str_replace("\u{00A0}", ' ', html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5)));

        return $text === '' ? null : $text;
    }
}
