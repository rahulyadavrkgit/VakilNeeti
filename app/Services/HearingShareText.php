<?php

namespace App\Services;

use App\Models\LegalCase;
use Illuminate\Support\Carbon;

/**
 * Plain-text messages for sharing a case or a day's list of hearings on WhatsApp, SMS or email:
 * the details an advocate, clerk or client needs (case, our side, date and time, court, room,
 * judge, VC link, purpose). No private VakilNiti links.
 */
class HearingShareText
{
    /**
     * One case.
     *
     * @param  array{title?: ?string, case_number?: ?string, side?: ?string, parties?: ?string, date?: ?string, time?: ?string, court?: ?string, room?: ?string, judge?: ?string, vc_link?: ?string, details?: ?string, item?: ?string, list_total?: ?int}  $item
     */
    public static function single(array $item): string
    {
        $side = LegalCase::sideLabel($item['side'] ?? null, $item['parties'] ?? null);

        return collect([
            '*'.trim((string) ($item['title'] ?? 'Case')).'*',
            ($item['case_number'] ?? null) ? 'Case no: '.$item['case_number'] : null,
            $side ? 'We appear for: '.$side : null,
            self::when($item) ? 'Hearing: '.self::when($item) : null,
            self::where($item) ? 'Court: '.self::where($item) : null,
            ($item['item'] ?? null) ? 'Item: '.$item['item'] : null,
            ($item['list_total'] ?? null) ? 'Cases in list: '.$item['list_total'] : null,
            ($item['supplementary'] ?? null) ? 'Supple: '.$item['supplementary'] : null,
            ($item['vc_link'] ?? null) ? 'VC link: '.$item['vc_link'] : null,
            ($item['details'] ?? null) ? 'Purpose: '.$item['details'] : null,
        ])->filter()->implode("\n");
    }

    /**
     * A list of hearings under one heading ("Hearings · Today, Tue 10 Mar"), each numbered.
     *
     * @param  iterable<array<string, mixed>>  $items
     */
    public static function list(string $heading, iterable $items): string
    {
        $blocks = collect($items)->values()->map(fn (array $item, int $index): string => ($index + 1).'. '.self::entry($item));

        return '*'.$heading."*\n\n".($blocks->isEmpty() ? 'No hearings.' : $blocks->implode("\n\n"));
    }

    /**
     * One hearing in the order of the hearing cards: case and side; court, number and stage; judge;
     * item, supplementary list, room and time; VC link.
     *
     * @param  array<string, mixed>  $item
     */
    public static function entry(array $item): string
    {
        $side = LegalCase::sideLabel($item['side'] ?? null, $item['parties'] ?? null);
        $room = trim((string) ($item['room'] ?? ''));

        return collect([
            trim((string) ($item['title'] ?? 'Case')).($side ? ' (For '.$side.')' : ''),
            collect([$item['court'] ?? null, $item['case_number'] ?? null, ($item['details'] ?? null) ?: 'Hearing'])->filter()->implode(' · '),
            ($item['judge'] ?? null) ?: null,
            collect([
                ($item['item'] ?? null) ? 'ITEM '.$item['item'].(($item['list_total'] ?? null) ? ' @ '.$item['list_total'] : '') : null,
                ($item['supplementary'] ?? null) ? 'Supple: '.$item['supplementary'] : null,
                $room !== '' ? 'Court Room '.preg_replace('/^(court|room)\s*(no\.?)?\s*/i', '', $room) : null,
                ($item['time'] ?? null) ? substr((string) $item['time'], 0, 5) : null,
            ])->filter()->implode(' · ') ?: null,
            ($item['vc_link'] ?? null) ? 'VC: '.$item['vc_link'] : null,
        ])->filter()->implode("\n");
    }

    /**
     * The hearings among calendar events as one list, with room, judge and VC link filled from the
     * official court lists where the case has none (as the court-info line shows them).
     *
     * @param  iterable<array<string, mixed>>  $events  CalendarEventService events
     */
    public static function forEvents(string $heading, iterable $events): string
    {
        return self::list($heading, collect($events)
            ->filter(fn (array $event): bool => ($event['group'] ?? null) === 'hearing')
            ->map(fn (array $event): array => self::fromEvent($event))
            ->values());
    }

    /**
     * Who sits in a room on the hearing's date. Supreme Court benches change daily: only that day's bench counts
     * (App\Services\SupremeCourtBenches). Other courts sit by roster, so the roster's judges are given.
     */
    public static function roomJudges(?string $court, ?string $room, mixed $date, ?string $rosterJudges): ?string
    {
        if (LiveCourtBoard::boardFor($court) === 'supreme-court') {
            return SupremeCourtBenches::judgesOn($court, $room, $date);
        }

        return $rosterJudges;
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array<string, mixed>
     */
    public static function fromEvent(array $event): array
    {
        $listing = $event['cause_list'] ?? [];
        $room = $listing['court'] ?? $event['court_room'] ?? null;
        $judge = $listing['judges'] ?? $event['judge_name'] ?? null;
        $vcLink = $listing['vc_link'] ?? $event['vc_link'] ?? null;

        if (! $room || ! $judge || ! $vcLink) {
            $bench = app(CourtBenchMatcher::class)->find($event['court'] ?? null, $event['court_complex'] ?? null, $judge);
            $room = $room ?: $bench?->court_room;
            $judge = $judge ?: ($bench?->judgeList()[0]['name'] ?? null);
            $vcLink = $vcLink ?: $bench?->vc_link;
        }

        // Supreme Court and High Court lists give the room; who sits there and the room's VC link come from the court.
        if ((! $judge || ! $vcLink) && $room) {
            $roomBench = app(CourtBenchMatcher::class)->findRoom($event['court'] ?? null, $room);
            $vcLink = $vcLink ?: $roomBench?->vc_link;
            $judge = $judge ?: self::roomJudges($event['court'] ?? null, $room, $event['date'] ?? null, $roomBench?->judgeNames());
        }

        return [
            'title' => $event['title'] ?? null,
            'case_number' => $event['case_number'] ?? null,
            'side' => $event['appearing_for'] ?? null,
            'parties' => $event['appearing_for_parties'] ?? null,
            'date' => $event['date'] ?? null,
            'time' => $event['time'] ?? null,
            'court' => $event['court'] ?? null,
            'room' => $room,
            'judge' => $judge,
            'vc_link' => $vcLink,
            'details' => $event['details'] ?? null,
            'item' => $listing['item'] ?? $event['item_number'] ?? null,
            'list_total' => $listing['place']['total'] ?? $listing['total'] ?? $event['list_total'] ?? null,
            'supplementary' => $listing['room_supplementary'] ?? $listing['place']['supplementary'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private static function when(array $item): ?string
    {
        if (! ($item['date'] ?? null)) {
            return $item['time'] ?? null;
        }

        $date = Carbon::parse($item['date']);
        $day = $date->isToday() ? 'Today, '.$date->format('D d M') : ($date->isTomorrow() ? 'Tomorrow, '.$date->format('D d M') : $date->format('D d M Y'));

        return $day.(($item['time'] ?? null) ? ', '.substr((string) $item['time'], 0, 5) : '');
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private static function where(array $item): ?string
    {
        $room = trim((string) ($item['room'] ?? ''));

        return collect([
            $item['court'] ?? null,
            $room !== '' ? (preg_match('/^(court|room)\b/i', $room) ? $room : 'Room '.$room) : null,
            ($item['judge'] ?? null) ? 'Judge: '.$item['judge'] : null,
        ])->filter()->implode(' · ') ?: null;
    }
}
