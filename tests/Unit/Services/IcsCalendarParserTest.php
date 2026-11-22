<?php

namespace Tests\Unit\Services;

use App\Services\IcsCalendarParser;
use Tests\TestCase;

class IcsCalendarParserTest extends TestCase
{
    public function test_reads_timed_all_day_and_folded_events_in_india_time(): void
    {
        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'BEGIN:VEVENT',
            'UID:one@google.com',
            'DTSTART:20261203T043000Z',
            'SUMMARY:Asha vs Ravi - CS/12/2024',
            'LOCATION:Tis Hazari Courts\, Delhi',
            'DESCRIPTION:Bring the reply\nand vakalatnama',
            'END:VEVENT',
            'BEGIN:VEVENT',
            'UID:two@google.com',
            'DTSTART;VALUE=DATE:20261201',
            'SUMMARY:Bail hearing for a very long matter title that Google folds onto',
            '  the next line',
            'END:VEVENT',
            'BEGIN:VEVENT',
            'UID:three@google.com',
            'DTSTART;TZID=Asia/Kolkata:20261202T140000',
            'SUMMARY:Cancelled meeting',
            'STATUS:CANCELLED',
            'END:VEVENT',
            'END:VCALENDAR',
        ]);

        $events = (new IcsCalendarParser)->parse($ics);

        $this->assertCount(2, $events);
        $this->assertSame(['2026-12-01', null], [$events[0]['date'], $events[0]['time']]);
        $this->assertSame('Bail hearing for a very long matter title that Google folds onto the next line', $events[0]['title']);
        $this->assertSame(['2026-12-03', '10:00'], [$events[1]['date'], $events[1]['time']]);
        $this->assertSame('Tis Hazari Courts, Delhi', $events[1]['location']);
        $this->assertSame("Bring the reply\nand vakalatnama", $events[1]['description']);
    }

    public function test_rejects_a_file_that_is_not_a_calendar(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new IcsCalendarParser)->parse('name,date');
    }
}
