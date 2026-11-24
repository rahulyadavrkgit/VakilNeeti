<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CalendarEventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Calendar sync: a private subscription link (webcal) that Google Calendar and the iPhone calendar
 * read on their own, so hearings and appointments appear there and stay up to date. The link is the
 * only key: whoever has it can read the calendar, so Settings can replace it with a new one.
 */
class CalendarFeedController extends Controller
{
    public function show(string $token, CalendarEventService $calendar): Response
    {
        $user = User::where('calendar_token', $token)->firstOrFail();

        $events = $calendar->between(today()->subDays(30), today()->addYear(), [
            'groups' => ['hearing', 'appointment'],
            'user_id' => $user->workspaceId(),
        ]);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//VakilNiti//Hearings//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:VakilNiti hearings',
            'X-WR-TIMEZONE:Asia/Kolkata',
            'REFRESH-INTERVAL;VALUE=DURATION:PT1H',
            'X-PUBLISHED-TTL:PT1H',
        ];

        foreach ($events as $event) {
            $day = Carbon::parse($event['date'], 'Asia/Kolkata');
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:'.$event['group'].'-'.$event['id'].'-'.$day->format('Ymd').'@vakilniti.com';
            $lines[] = 'DTSTAMP:'.now('UTC')->format('Ymd\THis\Z');

            if ($event['time']) {
                $start = Carbon::parse($event['date'].' '.$event['time'], 'Asia/Kolkata');
                $lines[] = 'DTSTART;TZID=Asia/Kolkata:'.$start->format('Ymd\THis');
                $lines[] = 'DTEND;TZID=Asia/Kolkata:'.$start->copy()->addHour()->format('Ymd\THis');
            } else {
                $lines[] = 'DTSTART;VALUE=DATE:'.$day->format('Ymd');
                $lines[] = 'DTEND;VALUE=DATE:'.$day->copy()->addDay()->format('Ymd');
            }

            $summary = trim(($event['group'] === 'hearing' ? '' : 'Meeting: ').$event['title']);
            $place = collect([$event['court'] ?? null, ($event['court_room'] ?? null) ? 'Room '.$event['court_room'] : null])->filter()->implode(', ');
            $notes = collect([
                $event['case_number'] ?? null,
                ($event['details'] ?? null) ? 'Purpose: '.$event['details'] : null,
                ($event['judge_name'] ?? null) ? 'Judge: '.$event['judge_name'] : null,
                ($event['vc_link'] ?? null) ? 'VC: '.$event['vc_link'] : null,
                $event['url'] ?? null,
            ])->filter()->implode("\n");

            $lines[] = 'SUMMARY:'.$this->text($summary);

            if ($place !== '') {
                $lines[] = 'LOCATION:'.$this->text($place);
            }

            if ($notes !== '') {
                $lines[] = 'DESCRIPTION:'.$this->text($notes);
            }

            if ($event['vc_link'] ?? null) {
                $lines[] = 'URL:'.$event['vc_link'];
            }

            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return response(implode("\r\n", array_map([$this, 'fold'], $lines))."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="vakilniti.ics"',
            'Cache-Control' => 'private, max-age=900',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /**
     * Settings → Calendar sync: make (or replace) the private link. The old link stops working.
     */
    public function reset(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['calendar_token' => Str::random(40)])->save();

        return redirect()->to(route('profile.index').'#calendar-sync')
            ->with('calendar_success', 'Your calendar link is ready. Any older link no longer works.');
    }

    /**
     * Text values: backslash, semicolon, comma and new lines escaped as the calendar format requires.
     */
    private function text(string $value): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $value);
    }

    /**
     * Lines longer than 75 bytes continue on the next line, starting with a space.
     */
    private function fold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $parts = [];

        while (strlen($line) > 75) {
            $cut = 75;

            // Never split a multi-byte character (Hindi names, ₹).
            while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
                $cut--;
            }

            $parts[] = substr($line, 0, $cut);
            $line = substr($line, $cut);
        }

        $parts[] = $line;

        return implode("\r\n ", $parts);
    }
}
