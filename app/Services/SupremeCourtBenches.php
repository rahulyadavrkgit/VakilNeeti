<?php

namespace App\Services;

use App\Models\CourtBench;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Who sits in each Supreme Court room on a given day, from the court's daily cause list per court
 * (api.sci.gov.in, one page per court and date). Supreme Court benches change from day to day, so they are
 * kept by date (a small file, the last two weeks) and a hearing shows the bench of its own date.
 */
class SupremeCourtBenches
{
    /** The court's cause list page for one court and date. */
    public const PAGE_URL = 'https://api.sci.gov.in/';

    /** Saved benches, by date and court number. */
    public const FILE = 'supreme-court-benches.json';

    /** The court rooms read every day (Court No. 1 to 17). */
    public const COURTS = 17;

    /** How long a day's benches are kept. */
    private const KEEP_DAYS = 14;

    /**
     * The lists a court can sit in; the first with a bench wins.
     */
    private const STAGES = ['Misc. Court', 'Regular Court'];

    /**
     * Saved benches, loaded once per request.
     *
     * @var array<string, array<string, array{judges: list<string>, time: ?string, stage?: string}>>|null
     */
    private static ?array $saved = null;

    /**
     * Each court's bench on the date: court number → judges and sitting time. Courts not sitting are left out.
     *
     * @return array<string, array{judges: list<string>, time: ?string, stage?: string}>
     */
    public function fetch(Carbon $date): array
    {
        $benches = [];

        foreach (range(1, self::COURTS) as $court) {
            foreach (self::STAGES as $stage) {
                $bench = $this->benchFrom($this->page($court, $stage, $date), $court);

                if ($bench) {
                    $benches[(string) $court] = $bench + ['stage' => $stage];

                    break;
                }
            }
        }

        return $benches;
    }

    /**
     * The court's own cause list for one court and date: the page the court links to, e.g.
     * https://api.sci.gov.in/?pageid=900104&courtno=2&stage=Misc.%20Court&msb=both&cldate=2026-10-26.
     * The list the court sat in that day (Misc. or Regular) when known, otherwise the Misc. list.
     */
    public static function listUrl(int|string $court, mixed $date = null, ?string $stage = null): string
    {
        $day = Carbon::parse($date ?? today())->toDateString();
        $stage ??= self::all()[$day][(string) (int) $court]['stage'] ?? self::STAGES[0];

        return self::PAGE_URL.'?'.http_build_query(self::query((int) $court, $stage, $day), '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @return array{pageid: string, courtno: int, stage: string, msb: string, cldate: string}
     */
    private static function query(int $court, string $stage, string $day): array
    {
        return ['pageid' => '900104', 'courtno' => $court, 'stage' => $stage, 'msb' => 'both', 'cldate' => $day];
    }

    /**
     * VakilNiti's link to a Supreme Court room's cause list. It goes through VakilNiti so the date is filled in
     * when the link is opened: no date means "today" at that moment. Null for other courts and Registrar courts.
     */
    public static function linkFor(?string $courtName, ?string $room, mixed $date = null): ?string
    {
        $court = LiveCourtBoard::boardFor($courtName) === 'supreme-court' && ! preg_match('/^\s*(r\b|rc|registrar)/i', (string) $room)
            ? LiveCourtBoard::roomKey('supreme-court', $room)
            : null;

        if ($court === null || (int) $court < 1 || (int) $court > self::COURTS) {
            return null;
        }

        return route('courts.supreme-court.cause-list', array_filter(['court' => (int) $court, 'date' => $date ? Carbon::parse($date)->toDateString() : null]));
    }

    /**
     * The court's page for one court, list and date. The site answers with status 404 even when the list is there,
     * so the page is read whatever the status.
     */
    private function page(int $court, string $stage, Carbon $date): string
    {
        try {
            return Http::timeout(30)->connectTimeout(10)->withUserAgent('Mozilla/5.0 (VakilNiti bench check)')
                ->get(self::PAGE_URL, self::query($court, $stage, $date->toDateString()))
                ->body();
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * The first bench in a court's page: the judges named under "COURT NO. : 5" and the time below them.
     * A special bench later in the day is not the room's own bench.
     *
     * @return array{judges: list<string>, time: ?string}|null
     */
    public function benchFrom(string $html, int $court): ?array
    {
        $text = html_entity_decode(strip_tags((string) preg_replace('/<br\s*\/?>/i', "\n", $html)), ENT_QUOTES | ENT_HTML5);
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $text) ?: []), fn (string $line): bool => $line !== ''));
        $judges = [];
        $time = null;

        foreach ($lines as $index => $line) {
            if (! preg_match('/^COURT\s*NO\.?\s*:\s*(\d+)\b/i', $line, $match) || (int) $match[1] !== $court) {
                continue;
            }

            foreach (array_slice($lines, $index + 1, 6) as $next) {
                if (preg_match("/^HON[’']?BLE\s+(.+)$/iu", $next, $judge)) {
                    $judges[] = self::judgeName($judge[1]);
                } elseif (preg_match('/^\(?\s*Time\s*:\s*([^)]+)\)?/i', $next, $sitting)) {
                    $time = trim($sitting[1]);

                    break;
                } elseif ($judges !== []) {
                    break;
                }
            }

            break;
        }

        return $judges === [] ? null : ['judges' => $judges, 'time' => $time];
    }

    /**
     * "MR. JUSTICE B.V. NAGARATHNA" → "Hon'ble Mr. Justice B.V. Nagarathna", the way the other court lists write it.
     */
    public static function judgeName(string $name): string
    {
        return "Hon'ble ".Str::title(mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $name))));
    }

    /**
     * Keep the day's benches with the last two weeks', and, for today, show them in the court list (court_benches).
     *
     * @param  array<string, array{judges: list<string>, time: ?string, stage?: string}>  $benches
     */
    public function save(Carbon $date, array $benches): void
    {
        $saved = self::all();
        $saved[$date->toDateString()] = $benches;
        $cutoff = today()->subDays(self::KEEP_DAYS)->toDateString();
        $saved = array_filter($saved, fn (string $day): bool => $day >= $cutoff, ARRAY_FILTER_USE_KEY);
        ksort($saved);

        Storage::disk('local')->put(self::FILE, (string) json_encode($saved, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        self::$saved = $saved;

        if ($date->isToday()) {
            CourtBench::query()->where('court_key', 'supreme-court')->get()->each(function (CourtBench $bench) use ($benches): void {
                $court = LiveCourtBoard::roomKey('supreme-court', $bench->court_room);
                $judges = $court !== null && ! preg_match('/^R/i', (string) $bench->court_room) ? ($benches[$court]['judges'] ?? []) : null;

                if ($judges !== null) {
                    $bench->update(['judges' => array_map(fn (string $name): array => ['name' => $name, 'designation' => null], $judges)]);
                }
            });
        }
    }

    /**
     * The judges sitting in a Supreme Court room on a date ("Hon'ble Mr. Justice A & Hon'ble Mr. Justice B"),
     * or null when that day's list is not out, the court is not the Supreme Court or the room is unknown.
     */
    public static function judgesOn(?string $courtName, ?string $room, mixed $date): ?string
    {
        $courtNumber = LiveCourtBoard::boardFor($courtName) === 'supreme-court' ? LiveCourtBoard::roomKey('supreme-court', $room) : null;

        if ($courtNumber === null || ! $date) {
            return null;
        }

        $judges = self::all()[Carbon::parse($date)->toDateString()][$courtNumber]['judges'] ?? [];

        return $judges === [] ? null : implode(' & ', $judges);
    }

    /**
     * @return array<string, array<string, array{judges: list<string>, time: ?string, stage?: string}>>
     */
    private static function all(): array
    {
        return self::$saved ??= rescue(fn () => json_decode((string) Storage::disk('local')->get(self::FILE), true), [], false) ?: [];
    }

    /**
     * Forget what was loaded (tests, and a long-running process after a new day's list).
     */
    public static function flush(): void
    {
        self::$saved = null;
    }
}
