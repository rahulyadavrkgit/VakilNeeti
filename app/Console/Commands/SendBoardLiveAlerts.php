<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\ReminderNotification;
use App\Services\CalendarEventService;
use App\Services\HearingShareText;
use App\Services\LiveCourtBoard;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * During court hours, every minute: when a court room's display board goes live (starts showing the item being
 * heard), each person with a matter in that room today gets one alert, in Alerts and as a push notification:
 * "Court 23 is live · Item 4 now · your item 66". Supreme Court and Delhi High Court boards.
 */
#[Signature('courts:board-live-alerts')]
#[Description('Tell each person when the board of a court room where they have a matter today goes live')]
class SendBoardLiveAlerts extends Command
{
    public function handle(CalendarEventService $calendar, LiveCourtBoard $boards): int
    {
        $matters = $this->todaysMatters($calendar);

        if ($matters->isEmpty()) {
            $this->line('No matters today in courts with a live board.');

            return self::SUCCESS;
        }

        $this->line($matters->count().' matters today: '.$matters->map(fn (array $matter): string => $matter['board'].' '.$matter['room'])->unique()->implode(', '));
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

                if ($room && $this->tell($roomMatters, $room, $court, $board['short'])) {
                    $sent++;
                }
            }
        }

        $this->info("Sent {$sent} board alerts.");

        return self::SUCCESS;
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
                    $room = $board ? LiveCourtBoard::roomKey($board, $details['room'] ?? null) : null;

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
     * One alert per person, room and day (a later check does not repeat it): "Court 23 is live · Delhi High Court",
     * "Item 4 now · your item 66 · Nawab Ali vs Anjuman". It opens the hearing (or today's hearings for several).
     *
     * @param  Collection<int, array{user: User, board: string, room: string, label: string, item: ?string, title: string, case_number: ?string, url: string}>  $matters
     * @param  array{room: string, label: string, item: ?string}  $room
     */
    private function tell(Collection $matters, array $room, string $court, string $courtShort): bool
    {
        $first = $matters->first();
        $user = $first['user'];
        $key = 'board-live:'.$court.':'.$room['room'].':'.today()->toDateString();

        if ($user->notifications()->where('data->key', $key)->exists()) {
            return false;
        }

        $items = $matters->pluck('item')->filter()->unique()->sort(SORT_NATURAL)->values();

        $user->notify(new ReminderNotification(
            kind: 'hearing',
            title: $room['label'].' is live · '.$courtShort,
            body: collect([
                $room['item'] ? 'Item '.$room['item'].' now' : null,
                $items->isNotEmpty() ? ($items->count() === 1 ? 'your item ' : 'your items ').$items->implode(', ') : null,
                $matters->count() === 1 ? collect([$first['title'], $first['case_number']])->filter()->implode(' · ') : $matters->count().' of your matters',
            ])->filter()->implode(' · '),
            url: $matters->count() === 1 ? $this->relative($first['url']) : route('dashboard', absolute: false),
            key: $key,
        ));

        return true;
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
