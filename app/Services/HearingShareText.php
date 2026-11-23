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
     * @param  array{title?: ?string, case_number?: ?string, side?: ?string, parties?: ?string, date?: ?string, time?: ?string, court?: ?string, room?: ?string, judge?: ?string, vc_link?: ?string, details?: ?string}  $item
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
            ($item['vc_link'] ?? null) ? 'VC link: '.$item['vc_link'] : null,
            ($item['details'] ?? null) ? 'Purpose: '.$item['details'] : null,
        ])->filter()->implode("\n");
    }

    /**
     * A list of hearings under one heading ("Hearings · Today, Tue 10 Mar").
     *
     * @param  iterable<array<string, mixed>>  $items
     */
    public static function list(string $heading, iterable $items): string
    {
        $lines = ['*'.$heading.'*'];
        $number = 0;

        foreach ($items as $item) {
            $number++;
            $side = LegalCase::sideLabel($item['side'] ?? null, $item['parties'] ?? null);
            $lines[] = '';
            $lines[] = $number.'. '.trim((string) ($item['title'] ?? 'Case')).(($item['case_number'] ?? null) ? ' ('.$item['case_number'].')' : '');
            $lines[] = collect([$side ? 'For '.$side : null, self::when($item)])->filter()->implode(' · ') ?: null;
            $lines[] = self::where($item) ?: null;
            $lines[] = ($item['vc_link'] ?? null) ? 'VC: '.$item['vc_link'] : null;
            $lines[] = ($item['details'] ?? null) ? 'Purpose: '.$item['details'] : null;
        }

        if ($number === 0) {
            $lines[] = 'No hearings.';
        }

        return collect($lines)->reject(fn ($line) => $line === null)->implode("\n");
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
     * @param  array<string, mixed>  $event
     * @return array<string, mixed>
     */
    public static function fromEvent(array $event): array
    {
        $room = $event['court_room'] ?? null;
        $judge = $event['judge_name'] ?? null;
        $vcLink = $event['vc_link'] ?? null;

        if (! $room || ! $judge || ! $vcLink) {
            $bench = app(CourtBenchMatcher::class)->find($event['court'] ?? null, $event['court_complex'] ?? null, $judge);
            $room = $room ?: $bench?->court_room;
            $judge = $judge ?: ($bench?->judgeList()[0]['name'] ?? null);
            $vcLink = $vcLink ?: $bench?->vc_link;
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
