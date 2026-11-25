<?php

namespace App\Services;

use App\Models\CauseListAppearance;
use App\Models\LegalCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The order of the case list, and the headings it is split under.
 *
 * Active cases: soonest next date first; cases with no date ahead (never given, or the last one passed) follow,
 * in the order they were last updated. Disposed cases: by date of disposal, earliest first; those without one follow,
 * by last update. Arranged "by court", the same order runs inside each court: district courts by district, High Courts
 * by bench (court number and judge from the cause list), the Supreme Court on its own.
 */
class CaseListArrangement
{
    /** Statuses still running; every other status is a closed (disposed) case. */
    public const ACTIVE_STATUSES = ['pending', 'other'];

    /**
     * @param  Collection<int, LegalCase>  $cases  with hearings and causeListAppearances.courtDocument loaded
     * @return Collection<int, array{id: int, group: string, label: string}> in display order
     */
    public function arrange(Collection $cases, string $list, string $arrangeBy): Collection
    {
        $today = today()->toDateString();

        return $cases
            ->map(function (LegalCase $case) use ($today, $list, $arrangeBy): array {
                $dates = $this->dates($case);
                $upcoming = $dates->filter(fn (string $date): bool => $date >= $today)->min();
                $updated = $case->updated_at?->toDateTimeString() ?? '0000';

                $order = $list === 'disposed'
                    ? ($case->disposed_on ? '0|'.$case->disposed_on->toDateString() : '1|'.$updated)
                    : ($upcoming ? '0|'.$upcoming : '1|'.$updated);

                [$groupOrder, $label] = $arrangeBy === 'court'
                    ? $this->courtGroup($case, $upcoming)
                    : $this->dateGroup($case, $list, $upcoming);

                return [
                    'id' => $case->id,
                    'group' => $groupOrder.'|'.$label,
                    'label' => $label,
                    'sort' => $groupOrder.'|'.($arrangeBy === 'court' ? $label.'|' : '').$order.'|'.Str::lower((string) $case->case_title),
                ];
            })
            ->sortBy('sort', SORT_STRING)
            ->values()
            ->map(fn (array $row): array => ['id' => $row['id'], 'group' => $row['group'], 'label' => $row['label']]);
    }

    /**
     * Every date on the case: hearings (their own date and the next date fixed) and cause list listings.
     *
     * @return Collection<int, string>
     */
    private function dates(LegalCase $case): Collection
    {
        return $case->hearings
            ->flatMap(fn ($hearing): array => [$hearing->hearing_date?->toDateString(), $hearing->next_hearing_date?->toDateString()])
            ->concat($case->causeListAppearances->map(fn (CauseListAppearance $appearance): ?string => $appearance->courtDocument?->listing_date?->toDateString()))
            ->filter()
            ->values();
    }

    /**
     * @return array{0: string, 1: string} sort prefix and heading
     */
    private function dateGroup(LegalCase $case, string $list, ?string $upcoming): array
    {
        if ($list === 'disposed') {
            return $case->disposed_on
                ? ['0', 'Disposed in '.$case->disposed_on->format('F Y')]
                : ['1', 'Date of disposal not recorded'];
        }

        if (! $upcoming) {
            return ['1', 'No date ahead · by last update'];
        }

        $day = Carbon::parse($upcoming);
        $prefix = $day->isToday() ? 'Today · ' : ($day->isTomorrow() ? 'Tomorrow · ' : '');

        return ['0', $prefix.$day->format('D d M Y')];
    }

    /**
     * Supreme Court first, then each High Court bench, then district courts by district, then the rest.
     *
     * @return array{0: string, 1: string} sort prefix and heading
     */
    private function courtGroup(LegalCase $case, ?string $upcoming): array
    {
        $level = $case->court_level;
        $court = CourtIdentity::label($level, $case->state_code, $case->district);

        return match ($level) {
            'supreme' => ['0', 'Supreme Court'],
            'high' => ['1', $court.' · '.$this->bench($case, $upcoming)],
            'district' => ['2', $court ?? 'District court'],
            default => ['3', $court ?? ($case->court_name ?: 'Court not set')],
        };
    }

    /**
     * The bench a High Court case is before: court number and judge from its cause list listing (the coming one,
     * else the latest), else from its hearings or the case itself.
     */
    private function bench(LegalCase $case, ?string $upcoming): string
    {
        $listings = $case->causeListAppearances
            ->filter(fn (CauseListAppearance $appearance): bool => $appearance->court_room || $appearance->judge_name)
            ->sortBy(fn (CauseListAppearance $appearance): string => $appearance->courtDocument?->listing_date?->toDateString() ?? '0000');
        $listing = ($upcoming ? $listings->first(fn (CauseListAppearance $appearance): bool => $appearance->courtDocument?->listing_date?->toDateString() === $upcoming) : null)
            ?? $listings->last();
        $hearing = $case->hearings->first(fn ($hearing): bool => filled($hearing->court_room) || filled($hearing->judge_name));

        $room = $listing?->court_room ?: ($hearing?->court_room ?: $case->court_room);
        $judge = $listing?->judge_name ?: ($hearing?->judge_name ?: $case->judge_name);

        $parts = array_filter([
            $room ? 'Court '.preg_replace('/^\s*(court\s*(no\.?)?\s*)/i', '', (string) $room) : null,
            $judge ? Str::title(Str::lower((string) $judge)) : null,
        ]);

        return $parts ? implode(' · ', $parts) : ($case->court_complex ?: 'Bench not listed yet');
    }
}
