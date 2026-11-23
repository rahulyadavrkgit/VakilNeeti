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
        'delhi-district-central' => 'As per the Central District VC links list dated 21.11.2026.',
        'delhi-district-central-rouse-avenue-courts' => 'As per the Rouse Avenue Court Complex VC links list as on 21.09.2026 (published 09.10.2026).',
        'delhi-district-west' => 'As per the West District VC links list as on 17.11.2026.',
        'delhi-district-east' => 'As per the East District VC links list dated 19.09.2026.',
        'delhi-district-north-east' => 'As per the North-East District VC links list dated 06.10.2026.',
        'delhi-district-shahdara' => 'As per the Shahdara District VC links list dated 06.10.2026.',
        'delhi-district-new-delhi' => 'As per the New Delhi District VC links list w.e.f. 17.11.2026.',
        'delhi-district-north-west' => 'As per the North-West District VC links list dated 12.10.2026.',
        'delhi-district-north' => 'As per the North District VC links list dated 12.10.2026.',
        'delhi-district-south-west' => 'As per the South-West District VC links list as on 17.11.2026.',
        'delhi-district-south-east' => 'As per the South-East District VC links list dated 14.11.2026.',
        'delhi-district-south' => 'As per the South District VC links list dated 28.10.2026.',
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
     * The court room split into a short number for the badge and the place it is in:
     * "111, Main court building (3rd floor)" → 111 + "Main court building (3rd floor)", "G-08-NCB" → G-08-NCB.
     *
     * @return array{number: ?string, place: ?string}
     */
    public function roomParts(): array
    {
        $room = trim((string) Str::of((string) $this->court_room)->after('Court No. ')->replaceMatches('/^Room\s+/i', ''));
        $code = '(?:[A-Z]{1,3}-)?\d+(?:-?[A-Z]+)?(?:-[A-Z]+)*';

        if ($room === '') {
            return ['number' => null, 'place' => null];
        }

        if (preg_match('/^'.$code.'$/i', $room)) {
            return ['number' => $room, 'place' => null];
        }

        if (preg_match('/^('.$code.')(?:\s*,\s*|\s+)(.+)$/i', $room, $parts) && ! preg_match('/^\d+(st|nd|rd|th)$/i', $parts[1])) {
            return ['number' => $parts[1], 'place' => Str::ucfirst(trim($parts[2]))];
        }

        return mb_strlen($room) <= 9 ? ['number' => $room, 'place' => null] : ['number' => null, 'place' => $room];
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
