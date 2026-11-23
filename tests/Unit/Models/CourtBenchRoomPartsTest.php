<?php

namespace Tests\Unit\Models;

use App\Models\CourtBench;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CourtBenchRoomPartsTest extends TestCase
{
    /**
     * @return array<string, array{?string, ?string, ?string}>
     */
    public static function rooms(): array
    {
        return [
            'number with building' => ['111, Main court building (3rd floor)', '111', 'Main court building (3rd floor)'],
            'number with block' => ['018, block-I', '018', 'Block-I'],
            'number and place without comma' => ['500 main building', '500', 'Main building'],
            'block code' => ['G-08-NCB', 'G-08-NCB', null],
            'family court code' => ['FC-45', 'FC-45', null],
            'number with letter' => ['310-A', '310-A', null],
            'supreme court prefix' => ['Court No. 12', '12', null],
            'room prefix' => ['Room 201 Ex Bl', '201', 'Ex Bl'],
            'short floor' => ['3rd Floor', '3rd Floor', null],
            'long place without number' => ['3rd Floor, Admin Block', null, '3rd Floor, Admin Block'],
            'empty' => [null, null, null],
        ];
    }

    #[DataProvider('rooms')]
    public function test_room_is_split_into_badge_number_and_place(?string $courtRoom, ?string $number, ?string $place): void
    {
        $bench = new CourtBench(['court_room' => $courtRoom]);

        $this->assertSame(['number' => $number, 'place' => $place], $bench->roomParts());
    }
}
