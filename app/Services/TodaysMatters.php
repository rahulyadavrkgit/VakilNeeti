<?php

namespace App\Services;

use App\Models\CourtHearing;

/**
 * Today's matters that still need their next date: heard today, with no date after today yet.
 */
class TodaysMatters
{
    public function __construct(private CalendarEventService $calendar) {}

    /**
     * @return list<array{case_id: int, title: string, url: string}>
     */
    public function withoutNextDate(): array
    {
        $todayHearings = $this->calendar->between(today(), today(), ['groups' => ['hearing']]);
        $caseIds = $todayHearings->pluck('case_id')->filter()->unique()->values();

        if ($caseIds->isEmpty()) {
            return [];
        }

        // A matter has its next date when any of its hearings falls after today.
        $withNextDate = CourtHearing::query()
            ->whereIn('case_id', $caseIds)
            ->where(fn ($dates) => $dates->whereDate('hearing_date', '>', today())->orWhereDate('next_hearing_date', '>', today()))
            ->pluck('case_id')
            ->unique();

        return $todayHearings
            ->filter(fn (array $event): bool => $event['case_id'] && ! $withNextDate->contains($event['case_id']))
            ->unique('case_id')
            ->map(fn (array $event): array => ['case_id' => $event['case_id'], 'title' => $event['title'], 'url' => $event['url']])
            ->values()
            ->all();
    }
}
