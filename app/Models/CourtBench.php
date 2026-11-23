<?php

namespace App\Models;

use App\Http\Controllers\CourtLinksController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * One court room in the public directory: the judge(s) sitting there and how to join it by VC.
 */
class CourtBench extends Model
{
    /**
     * Which official order a court's list follows, shown above the list.
     *
     * @var array<string, string>
     */
    public const SOURCES = [
        'delhi-high-court' => 'As per the Roster of Sitting effective 07.10.2026, as amended on 09.10.2026 and 19.11.2026.',
    ];

    /**
     * Official cause list for each court's benches.
     *
     * @var array<string, string>
     */
    public const CAUSE_LISTS = [
        'supreme-court' => 'https://www.sci.gov.in/cause-list/',
        'delhi-high-court' => 'https://delhihighcourt.nic.in/web/cause-lists',
    ];

    protected $fillable = [
        'court_key',
        'section',
        'court_room',
        'bench',
        'location',
        'judges',
        'roster',
        'vc_link',
        'vc_meeting_id',
        'email',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'judges' => 'array',
            'roster' => 'array',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Every court with a room directory, keyed by court_key: its page title and where it belongs.
     *
     * @return array<string, array{title: string, court: string, list: string, parent_route: string}>
     */
    public static function courts(): array
    {
        $courts = [
            'supreme-court' => ['title' => 'Court list', 'court' => 'Supreme Court of India', 'list' => 'Court list', 'parent_route' => 'courts.supreme-court'],
            'delhi-high-court' => ['title' => 'Judges list', 'court' => 'High Court of Delhi', 'list' => 'Judges list', 'parent_route' => 'courts.delhi-high-court'],
        ];

        foreach (CourtLinksController::DELHI_DISTRICT_COMPLEXES as $complex => $districts) {
            foreach ($districts as $district) {
                $courts[self::districtKey($district['district'])] = [
                    'title' => 'Judges list',
                    'court' => $district['district'].' District, Delhi ('.$complex.')',
                    'list' => 'Judges list',
                    'parent_route' => 'courts.delhi-district-courts',
                ];
            }
        }

        return $courts;
    }

    public static function districtKey(string $district): string
    {
        return 'delhi-district-'.Str::slug($district);
    }

    /**
     * Each judge as name and designation, whatever shape was imported.
     *
     * @return list<array{name: string, designation: ?string, photo: ?string}>
     */
    public function judgeList(): array
    {
        return array_values(array_filter(array_map(fn ($judge) => is_array($judge)
            ? ['name' => (string) ($judge['name'] ?? ''), 'designation' => $judge['designation'] ?? null, 'photo' => $judge['photo'] ?? null]
            : ['name' => (string) $judge, 'designation' => null, 'photo' => null], (array) $this->judges), fn ($judge) => $judge['name'] !== ''));
    }
}
