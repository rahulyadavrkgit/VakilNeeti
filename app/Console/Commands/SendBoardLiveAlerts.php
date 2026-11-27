<?php

namespace App\Console\Commands;

use App\Models\CauseListAppearance;
use App\Models\User;
use App\Notifications\ReminderNotification;
use App\Services\CalendarEventService;
use App\Services\HearingShareText;
use App\Services\LiveCourtBoard;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Sleep;

/**
 * During court hours, every minute, for each person with a matter today in a court room with a live board
 * (Supreme Court, Delhi High Court), in Alerts and as a push notification:
 *  - when the room's board goes live: "Court 23 is live · Item 4 now · your item 66";
 *  - then each time the court reaches item 10, 20, 30…;
 *  - once their item is 7 or fewer away, every 2 items ("Court 23 at item 61 · your item 66 · 5 to go"),
 *    and when their item is taken up ("Your item 66 is on now");
 *  - High Courts take the supplementary list up first: while it runs, other items wait (no count-down), and
 *    "Court 23: supplementary list over" when the court moves on to the day's other matters.
 * Each alert goes once; a court that has passed someone's item sends them nothing more for it.
 */
#[Signature('courts:board-live-alerts {--passes=4 : Looks at the boards this many times, 15 seconds apart}')]
#[Description('Tell each person when the board of a court room with their matter today goes live, and how near their item is')]
class SendBoardLiveAlerts extends Command
{
    /** From this many items away, the count-down alerts every 2 items. */
    private const NEAR = 7;

    /** Before that, an alert each time the court reaches a multiple of this. */
    private const STEP = 10;

    /** Seconds between looks at the boards within one run. */
    private const PASS_SECONDS = 15;

    public function handle(CalendarEventService $calendar, LiveCourtBoard $boards): int
    {
        $matters = $this->todaysMatters($calendar);

        if ($matters->isEmpty()) {
            $this->line('No matters today in courts with a live board.');

            return self::SUCCESS;
        }

        $this->line($matters->count().' matters today: '.$matters->map(fn (array $matter): string => $matter['board'].' '.$matter['room'])->unique()->implode(', '));
        $sent = 0;
        $passes = max(1, (int) $this->option('passes'));

        // The schedule starts this once a minute; looking at the boards every 15 seconds keeps alerts close to the court.
        for ($pass = 1; $pass <= $passes; $pass++) {
            $sent += $this->look($matters, $boards);

            if ($pass < $passes) {
                Sleep::for(self::PASS_SECONDS)->seconds();
            }
        }

        $this->info("Sent {$sent} board alerts.");

        return self::SUCCESS;
    }

    /**
     * One look at the boards of the courts with matters today; returns how many alerts went out.
     *
     * @param  Collection<int, array{user: User, board: string, room: string, label: string, item: ?string, title: string, case_number: ?string, url: string}>  $matters
     */
    private function look(Collection $matters, LiveCourtBoard $boards): int
    {
        $sent = 0;

        foreach ($matters->groupBy('board') as $court => $courtMatters) {
            $board = $boards->board($court);

            if (! ($board['ok'] ?? false)) {
                continue;
            }

            $live = collect($board['rooms'])->where('status', 'running')->keyBy('room');

            // One alert per person and room, however many of their matters are listed there.
            foreach ($courtMatters->groupBy(fn (array $matter): string => $matter['user']->id.'|'.$matter['room']) as $roomMatters) {
                $room = $live->get($roomMatters->first()['room']);

                if ($room) {
                    $sent += $this->tell($roomMatters, $room, $court, $board['short']);
                }
            }
        }

        return $sent;
    }

    /**
     * Everyone's hearings today in a court with a live board, with the room number as the board writes it.
     *
     * @return Collection<int, array{user: User, board: string, room: string, label: string, item: ?string, title: string, case_number: ?string, url: string}>
     */
    private function todaysMatters(CalendarEventService $calendar): Collection
    {
        return User::query()->get()->flatMap(function (User $user) use ($calendar): array {
            return $calendar->between(today(), today(), ['groups' => ['hearing'], 'user_id' => $user->workspaceId()])
                ->map(function (array $event) use ($user): ?array {
                    $details = HearingShareText::fromEvent($event);
                    $board = LiveCourtBoard::boardFor($event['court'] ?? null);
                    $room = in_array($board, ['delhi-high-court', 'supreme-court'], true)
                        ? LiveCourtBoard::roomKey($board, $details['room'] ?? null)
                        : null;

                    return $room === null ? null : [
                        'user' => $user,
                        'board' => $board,
                        'room' => $room,
                        'label' => trim((string) $details['room']),
                        'item' => $details['item'] ?? null,
                        'title' => (string) $event['title'],
                        'case_number' => $event['case_number'] ?? null,
                        'url' => (string) $event['url'],
                    ];
                })
                ->filter()
                ->values()
                ->all();
        });
    }

    /**
     * The alerts due for one person in one live room; returns how many were sent.
     *
     * @param  Collection<int, array{user: User, board: string, room: string, label: string, item: ?string, title: string, case_number: ?string, url: string}>  $matters
     * @param  array{room: string, label: string, item: ?string}  $room
     */
    private function tell(Collection $matters, array $room, string $court, string $courtShort): int
    {
        $user = $matters->first()['user'];
        $base = $court.':'.$room['room'].':'.today()->toDateString();
        $now = self::number($room['item']);

        // High Courts take the supplementary list up first ("70–85" before 1, 2, 3…).
        $supplementary = collect(CauseListAppearance::supplementaryItems($court, today()->toDateString(), $room['room']))->map(fn (string $item): ?float => self::number($item))->filter()->values();
        $inSupplementary = $now !== null && $supplementary->contains($now);
        $supplementaryNote = $supplementary->isNotEmpty() ? ' (supplementary '.$supplementary->first().($supplementary->count() > 1 ? '–'.$supplementary->last() : '').')' : '';

        // The court started: one alert, with where it is now (no count-downs in the same minute).
        if ($this->send($user, 'board-live:'.$base, $room['label'].' is live · '.$courtShort, $this->body($matters, $room['item'], $inSupplementary ? $supplementaryNote : ''), $this->link($matters))) {
            return 1;
        }

        if ($now === null) {
            return 0;
        }

        $sent = 0;
        $isSupplementary = fn (array $matter): bool => $supplementary->contains(self::number($matter['item']));

        // The supplementary list is over: the court is on the day's other matters now.
        if ($supplementary->isNotEmpty() && ! $inSupplementary && $matters->contains(fn (array $matter): bool => ! $isSupplementary($matter))) {
            $sent += (int) $this->send($user, 'board-supplementary-over:'.$base, $room['label'].': supplementary list over · '.$courtShort, $this->body($matters, $room['item']), $this->link($matters));
        }

        // Each of their items not reached yet, nearest first. While the supplementary list runs, only supplementary
        // items count down; the others wait for it to end (item 72 of the supplementary list is not past item 66).
        $ahead = $matters->filter(function (array $matter) use ($now, $inSupplementary, $isSupplementary): bool {
            $item = self::number($matter['item']);

            return $item !== null && ($inSupplementary ? $isSupplementary($matter) && $item >= $now : ! $isSupplementary($matter) && $item >= $now);
        })->sortBy(fn (array $matter): float => (float) self::number($matter['item']))->values();

        foreach ($ahead as $matter) {
            $left = (int) (self::number($matter['item']) - $now);
            $title = collect([$matter['title'], $matter['case_number']])->filter()->implode(' · ');

            if ($left === 0) {
                $sent += (int) $this->send($user, 'board-now:'.$base.':'.$matter['item'], 'Your item '.$matter['item'].' is on now · '.$room['label'], $title.' · '.$courtShort, $this->relative($matter['url']));
            } elseif ($left <= self::NEAR) {
                // Every 2 items: 7 and 6 share an alert, then 5 and 4, 3 and 2, and 1.
                $sent += (int) $this->send($user, 'board-near:'.$base.':'.$matter['item'].':'.intdiv($left, 2), $room['label'].' at item '.$room['item'].' · your item '.$matter['item'],
                    $left.' to go · '.$title, $this->relative($matter['url']));
            }
        }

        // Every 10 items, while none of their items is near yet (or their item number is not known); not during the
        // supplementary list.
        $nearest = $ahead->first();
        $waiting = $matters->contains(fn (array $matter): bool => self::number($matter['item']) === null || ($inSupplementary && ! $isSupplementary($matter)));
        $far = $nearest === null ? $waiting : self::number($nearest['item']) - $now > self::NEAR;
        $step = (int) (floor($now / self::STEP) * self::STEP);

        if ($far && ! $inSupplementary && $step >= self::STEP) {
            $sent += (int) $this->send($user, 'board-step:'.$base.':'.$step, $room['label'].' at item '.$room['item'].' · '.$courtShort, $this->body($matters, null), $this->link($matters));
        }

        return $sent;
    }

    /**
     * "Item 4 now · your item 66 · Nawab Ali vs Anjuman · RSA/141/2026", or "your items 12, 66 · 2 of your matters".
     *
     * @param  Collection<int, array{item: ?string, title: string, case_number: ?string}>  $matters
     */
    private function body(Collection $matters, ?string $now, string $note = ''): string
    {
        $items = $matters->pluck('item')->filter()->unique()->sort(SORT_NATURAL)->values();
        $first = $matters->first();

        return collect([
            $now ? 'Item '.$now.' now'.$note : null,
            $items->isNotEmpty() ? ($items->count() === 1 ? 'your item ' : 'your items ').$items->implode(', ') : null,
            $matters->count() === 1 ? collect([$first['title'], $first['case_number']])->filter()->implode(' · ') : $matters->count().' of your matters',
        ])->filter()->implode(' · ');
    }

    /**
     * The hearing's case, or today's hearings on Home for several.
     *
     * @param  Collection<int, array{url: string}>  $matters
     */
    private function link(Collection $matters): string
    {
        return $matters->count() === 1 ? $this->relative($matters->first()['url']) : route('dashboard', absolute: false);
    }

    /**
     * Sends the alert unless this person already has it; true when sent.
     */
    private function send(User $user, string $key, string $title, string $body, string $url): bool
    {
        if ($user->notifications()->where('data->key', $key)->exists()) {
            return false;
        }

        $user->notify(new ReminderNotification(kind: 'hearing', title: $title, body: $body, url: $url, key: $key));

        return true;
    }

    /**
     * The number in an item as boards and lists write it: "66", "A72" (72), "2.1" (2.1); null when there is none.
     */
    private static function number(?string $item): ?float
    {
        return $item !== null && preg_match('/\d+(?:\.\d+)?/', $item, $match) ? (float) $match[0] : null;
    }

    /**
     * Only the path, so the link works on any address the site is served from.
     */
    private function relative(string $url): string
    {
        $parts = parse_url($url);

        return ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '');
    }
}
