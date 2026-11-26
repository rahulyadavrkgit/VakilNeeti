<?php

namespace App\Models;

use App\Http\Controllers\CourtLinksController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
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
     * The words in this room's web address: court, room and first judge, e.g. "saket-room-031-shreya-arora-mehta".
     */
    public function pageSlug(): string
    {
        $court = self::courts()[$this->court_key]['court'] ?? $this->court_key;
        $judge = $this->judgeList()[0]['name'] ?? '';
        $judge = (string) preg_replace('/^(hon.?ble\s+)?(mr|ms|mrs|dr|sh|shri|smt)\.?\s+(justice\s+)?/i', '', $judge);

        return Str::limit(Str::slug(collect([$court, ($this->roomParts()['number'] ?? null) ? 'room '.$this->roomParts()['number'] : null, $judge])->filter()->implode(' ')), 120, '');
    }

    /**
     * The public page for this room (judge, room, VC link), for people searching Google.
     */
    public function pageUrl(): string
    {
        return route('courts.room', [$this->id, $this->pageSlug()]);
    }

    /**
     * Each judge as name and designation, whatever shape was imported.
     *
     * @return list<array{name: string, designation: ?string, photo: ?string}>
     */
    public function judgeList(): array
    {
        $district = str_starts_with((string) $this->court_key, 'delhi-district-')
            ? Str::before(Str::after($this->court_key, 'delhi-district-'), '-rouse-avenue-courts')
            : null;

        return array_values(array_filter(array_map(fn ($judge) => is_array($judge)
            ? ['name' => (string) ($judge['name'] ?? ''), 'designation' => $judge['designation'] ?? null, 'photo' => $judge['photo'] ?? self::photoFor((string) ($judge['name'] ?? ''), $district)]
            : ['name' => (string) $judge, 'designation' => null, 'photo' => self::photoFor((string) $judge, $district)], (array) $this->judges), fn ($judge) => $judge['name'] !== ''));
    }

    /**
     * Everyone sitting in the room, in one line: "Hon'ble Mr. Justice A & Hon'ble Ms. Justice B" for a division bench.
     */
    public function judgeNames(): ?string
    {
        return collect($this->judgeList())->pluck('name')->implode(' & ') ?: null;
    }

    /**
     * The file with the judges' photos saved from the courts' own judges pages (`php artisan courts:judge-photos`).
     */
    public const PHOTOS_FILE = 'judge-photos.json';

    /**
     * "Hon'ble Ms. Justice Prathiba M. Singh" and "JUSTICE PRATHIBA M SINGH" → "PRATHIBAMSINGH".
     */
    public static function judgeKey(string $name): string
    {
        $name = mb_strtoupper(str_replace('’', "'", html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $name = (string) preg_replace("/\b(HON'?BLE|HONOURABLE|THE|CHIEF|ACTING|MR|MRS|MS|DR|SHRI|SH|SMT|JUSTICE|JUDGE)\b\.?/u", ' ', $name);

        return (string) preg_replace('/[^A-Z]/', '', $name);
    }

    /**
     * The saved photo of a judge, as a web address, or null.
     */
    public static function photoFor(string $name, ?string $district = null): ?string
    {
        static $photos = null;
        $photos ??= rescue(fn () => json_decode((string) Storage::disk('local')->get(self::PHOTOS_FILE), true), [], false) ?: [];
        $key = self::judgeKey($name);
        $slug = Str::slug((string) $district);
        if ($slug !== 'new-delhi') {
            $slug = Str::beforeLast(Str::after($slug, 'delhi-'), '-delhi');
        }
        $path = $photos[$slug !== '' ? $slug.'-'.$key : $key] ?? null;

        return $path ? route('courts.judge-photo', ['photo' => basename($path)], absolute: false) : null;
    }
}
