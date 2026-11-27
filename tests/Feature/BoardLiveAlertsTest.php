<?php

namespace Tests\Feature;

use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\User;
use App\Notifications\Channels\WebPushChannel;
use App\Notifications\ReminderNotification;
use App\Services\LiveCourtBoard;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardLiveAlertsTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_person_hears_once_when_the_board_of_a_room_with_their_matter_today_goes_live(): void
    {
        $this->travelTo('2026-11-27 10:31:00');
        $board = ['23' => 'not_in_session', '5' => 'running', '12' => 'running'];
        $this->partialMock(LiveCourtBoard::class, function ($mock) use (&$board): void {
            $mock->shouldReceive('board')->andReturnUsing(function (string $court) use (&$board): array {
                return ['court' => $court, 'short' => 'Delhi High Court', 'ok' => true,
                    'rooms' => collect($board)->map(fn (string $status, string|int $room): array => ['room' => (string) $room, 'label' => 'Court '.$room, 'status' => $status, 'item' => $status === 'running' ? '4' : null])->values()->all()];
            });
        });
        $alerts = fn (User $user): array => $user->notifications()->get()->map(fn ($alert): array => [$alert->data['title'], $alert->data['body'], $alert->data['url']])->all();

        // Asha has two matters in Court 23 today, Ravi one in Court 5; a matter tomorrow and one in a district court do not count.
        $asha = User::factory()->create();
        $this->actingAs($asha);
        foreach (['RSA/141/2026' => '66', 'RSA/70/2026' => '12'] as $number => $item) {
            $case = LegalCase::create(['case_title' => 'Matter '.$number, 'case_number' => $number, 'court_name' => 'High Court of Delhi', 'case_status' => 'pending']);
            CourtHearing::create(['case_id' => $case->id, 'hearing_date' => '2026-11-27', 'court_room' => '23', 'item_number' => $item]);
        }
        $tomorrow = LegalCase::create(['case_title' => 'Tomorrow matter', 'court_name' => 'High Court of Delhi', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $tomorrow->id, 'hearing_date' => '2026-11-28', 'court_room' => '12']);
        $ravi = User::factory()->create();
        $this->actingAs($ravi);
        $one = LegalCase::create(['case_title' => 'Ravi vs Union', 'case_number' => 'W.P.(C)/5/2026', 'court_name' => 'Delhi High Court', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $one->id, 'hearing_date' => '2026-11-27', 'court_room' => 'Court No. 5', 'item_number' => '9']);
        $district = LegalCase::create(['case_title' => 'Saket matter', 'court_name' => 'Saket Courts', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $district->id, 'hearing_date' => '2026-11-27', 'court_room' => '12']);
        $nobody = User::factory()->create();
        // The check runs on its own (no one signed in), seeing everyone's cases.
        auth()->forgetGuards();

        // Court 5 is sitting, Court 23 not yet: only Ravi hears, in Alerts and as a push.
        $this->artisan('courts:board-live-alerts')->expectsOutputToContain('Sent 1 board alerts.')->assertSuccessful();
        $this->assertSame([['Court 5 is live · Delhi High Court', 'Item 4 now · your item 9 · Ravi vs Union · W.P.(C)/5/2026', route('cases.show', $one, false)]], $alerts($ravi));
        $this->assertSame([[], []], [$alerts($asha), $alerts($nobody)]);
        $this->assertContains(WebPushChannel::class, (new ReminderNotification('hearing', 't', 'b', '/', 'k'))->via($ravi));

        // Court 23 starts: Asha gets one alert for both matters; nothing is repeated.
        $board['23'] = 'running';
        $this->artisan('courts:board-live-alerts')->expectsOutputToContain('Sent 1 board alerts.')->assertSuccessful();
        $this->artisan('courts:board-live-alerts')->expectsOutputToContain('Sent 0 board alerts.')->assertSuccessful();
        $this->assertSame([['Court 23 is live · Delhi High Court', 'Item 4 now · your items 12, 66 · 2 of your matters', route('dashboard', absolute: false)]], $alerts($asha));
        $this->assertCount(1, $alerts($ravi));
    }

    public function test_it_runs_every_minute_in_court_hours(): void
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains((string) $event->command, 'courts:board-live-alerts'));

        $this->assertSame('* * * * 1,2,3,4,5,6', $event->expression);
    }
}
