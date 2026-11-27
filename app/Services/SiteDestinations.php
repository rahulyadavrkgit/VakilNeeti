<?php

namespace App\Services;

use App\Http\Controllers\CourtLinksController;
use App\Models\CourtBench;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The places in VakilNiti a search can take someone straight to: display boards (each court's board, opened
 * already chosen), court pages, judges lists and the main pages. A search for "delhi high court board" or
 * "saket judges" lands on that page, not on a list of links to it.
 */
class SiteDestinations
{
    /**
     * The places whose name has every word of the search (most specific first), at most $limit.
     *
     * @return Collection<int, array{title: string, detail: string, url: string, icon: string}>
     */
    public function matching(string $query, int $limit = 6): Collection
    {
        $words = collect(preg_split('/\s+/u', Str::lower(trim($query)), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn (string $word): string => self::singular($word))
            ->reject(fn (string $word): bool => in_array($word, ['the', 'of', 'in', 'for', 'and', 'page'], true));

        if ($words->isEmpty() || mb_strlen($query) < 2) {
            return collect();
        }

        return $this->all()
            ->filter(function (array $place) use ($words): bool {
                $haystack = Str::lower($place['title'].' '.$place['detail'].' '.$place['keywords']);
                $haystackWords = collect(preg_split('/[^\p{L}\p{N}]+/u', $haystack, -1, PREG_SPLIT_NO_EMPTY))->map(fn (string $word): string => self::singular($word));

                return $words->every(fn (string $word): bool => $haystackWords->contains(fn (string $have): bool => str_starts_with($have, $word)));
            })
            ->sortBy(fn (array $place): int => mb_strlen($place['title']))
            ->take($limit)
            ->map(fn (array $place): array => collect($place)->except('keywords')->all())
            ->values();
    }

    /**
     * Every place, with words people may search for it by.
     *
     * @return Collection<int, array{title: string, detail: string, url: string, icon: string, keywords: string}>
     */
    private function all(): Collection
    {
        $places = collect();
        $board = 'display board live board current item running';

        foreach (CourtLinksController::DISPLAY_BOARDS as $court) {
            $short = self::shortName($court['court']);

            if (isset($court['live'])) {
                $places->push(['title' => $short.' · Live display board', 'detail' => 'Item running in each court, in VakilNiti', 'url' => route('courts.live-board', $court['live']), 'icon' => 'screen', 'keywords' => $court['court'].' '.$board]);
            }

            foreach ($court['boards'] as $official) {
                $places->push(['title' => $short.' · '.$official['title'], 'detail' => 'Display boards', 'url' => route('courts.display-boards', ['board' => $official['url']]), 'icon' => 'screen', 'keywords' => $court['court'].' '.$board]);
            }
        }

        $courts = [
            ['Supreme Court services', 'Cause lists, case status, display board', route('courts.supreme-court'), 'Supreme Court of India SC sci cause list case status'],
            ['Supreme Court · Court list', 'Judges sitting in each court today, VC links', route('courts.supreme-court.court-list'), 'Supreme Court SC sci judges bench court room VC'],
            ['Delhi High Court services', 'Cause lists, case status, display board', route('courts.delhi-high-court'), 'High Court of Delhi DHC cause list case status'],
            ['Delhi High Court · Judges list', 'Roster, court rooms, VC links', route('courts.delhi-high-court.judges'), 'High Court of Delhi DHC judges roster bench court room VC'],
            ['High Courts', 'Every High Court', route('courts.high-courts'), 'HC state high court'],
            ['Delhi District Courts', 'District courts of Delhi', route('courts.delhi-district-courts'), 'district court Delhi Saket Tis Hazari Karkardooma Rohini Dwarka Patiala House Rouse Avenue'],
            ['Tribunals', 'NCLT, NCLAT, ITAT, consumer commissions and more', route('tribunals.index'), 'tribunal NCLT NCLAT ITAT NGT CAT DRT consumer'],
            ['eCourts services', 'Case status, orders, cause lists', route('courts.ecourts-services'), 'ecourts case status order cause list'],
            ['Display boards', 'Every court\'s display board', route('courts.display-boards'), $board],
        ];

        foreach ($courts as [$title, $detail, $url, $keywords]) {
            $places->push(['title' => $title, 'detail' => $detail, 'url' => $url, 'icon' => 'gavel', 'keywords' => $keywords]);
        }

        foreach (CourtBench::courts() as $courtKey => $court) {
            if (! str_starts_with($courtKey, 'delhi-district-')) {
                continue;
            }

            $places->push(['title' => Str::before($court['court'], ',').' · Judges list', 'detail' => $court['court'], 'url' => route('courts.delhi-district-courts.judges', Str::after($courtKey, 'delhi-district-')), 'icon' => 'gavel', 'keywords' => 'district judges court rooms VC '.$court['court']]);
        }

        $pages = [
            ['My cases', 'Every case in your diary', route('cases.index'), 'briefcase', 'cases diary matters'],
            ['Calendar', 'Hearings, tasks and appointments', route('cases.calendar'), 'calendar', 'calendar dates hearings diary'],
            ['Tasks', 'To-dos and deadlines', route('tasks.index'), 'check', 'tasks todo deadline'],
            ['Clients', 'Your clients', route('clients.index'), 'people', 'clients contacts'],
            ['Billing', 'Invoices, payments and expenses', route('billing.index'), 'receipt', 'billing invoices payments fees expenses'],
            ['Alerts', 'Notifications', route('notifications.index'), 'bell', 'alerts notifications'],
            ['Bare acts', 'Acts and sections', route('bare-acts.index'), 'book', 'bare acts law sections ipc bns crpc'],
        ];

        foreach ($pages as [$title, $detail, $url, $icon, $keywords]) {
            $places->push(['title' => $title, 'detail' => $detail, 'url' => $url, 'icon' => $icon, 'keywords' => $keywords]);
        }

        return $places;
    }

    /**
     * "Supreme Court of India" → "Supreme Court", "High Court of Delhi" → "Delhi High Court".
     */
    private static function shortName(string $court): string
    {
        return match (true) {
            $court === 'Supreme Court of India' => 'Supreme Court',
            (bool) preg_match('/^High Court of (.+)$/', $court, $match) => $match[1].' High Court',
            default => $court,
        };
    }

    /**
     * "boards" and "board", "judges" and "judge" match each other.
     */
    private static function singular(string $word): string
    {
        return mb_strlen($word) > 3 && str_ends_with($word, 's') && ! str_ends_with($word, 'ss') ? mb_substr($word, 0, -1) : $word;
    }
}
