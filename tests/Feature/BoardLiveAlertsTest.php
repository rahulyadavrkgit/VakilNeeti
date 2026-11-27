<?php

namespace Tests\Feature;

use App\Models\CourtDocument;
use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\User;
use App\Notifications\Channels\WebPushChannel;
use App\Notifications\ReminderNotification;
use App\Services\CauseLists\CauseListRecorder;
use App\Services\LiveCourtBoard;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Sleep;
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
        $this->artisan('courts:board-live-alerts', ['--passes' => 1])->expectsOutputToContain('Sent 1 board alerts.')->assertSuccessful();
        $this->assertSame([['Court 5 is live · Delhi High Court', 'Item 4 now · your item 9 · Ravi vs Union · W.P.(C)/5/2026', route('cases.show', $one, false)]], $alerts($ravi));
        $this->assertSame([[], []], [$alerts($asha), $alerts($nobody)]);
        $this->assertContains(WebPushChannel::class, (new ReminderNotification('hearing', 't', 'b', '/', 'k'))->via($ravi));

        // Court 23 starts: Asha gets one alert for both matters (Ravi, 5 items from his, a count-down); nothing is repeated.
        $board['23'] = 'running';
        $this->artisan('courts:board-live-alerts', ['--passes' => 1])->expectsOutputToContain('Sent 2 board alerts.')->assertSuccessful();
        $this->artisan('courts:board-live-alerts', ['--passes' => 1])->expectsOutputToContain('Sent 0 board alerts.')->assertSuccessful();
        $this->assertSame([['Court 23 is live · Delhi High Court', 'Item 4 now · your items 12, 66 · 2 of your matters', route('dashboard', absolute: false)]], $alerts($asha));
        $this->assertContains('Court 5 at item 4 · your item 9', array_column($alerts($ravi), 0));
        $this->assertCount(2, $alerts($ravi));
    }

    public function test_then_every_ten_items_and_every_two_items_once_their_item_is_seven_away(): void
    {
        $this->travelTo('2026-11-27 10:31:00');
        $current = null;
        $this->partialMock(LiveCourtBoard::class, function ($mock) use (&$current): void {
            $mock->shouldReceive('board')->andReturnUsing(function (string $court) use (&$current): array {
                return ['court' => $court, 'short' => 'Delhi High Court', 'ok' => true,
                    'rooms' => [['room' => '23', 'label' => 'Court 23', 'status' => $current === null ? 'not_in_session' : 'running', 'item' => $current]]];
            });
        });
        $asha = User::factory()->create();
        $this->actingAs($asha);
        $case = LegalCase::create(['case_title' => 'Nawab vs Anjuman', 'case_number' => 'RSA/141/2026', 'court_name' => 'High Court of Delhi', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => '2026-11-27', 'court_room' => '23', 'item_number' => '36']);
        auth()->forgetGuards();

        // The board moves through the morning; one check each time (the site checks every minute).
        foreach (['3', '4', '9', '10', '12', 'A20', '21', '28', '29', '30', '31', '32', '33', '34', '35', '36', '37', '40'] as $item) {
            $current = $item;
            $this->artisan('courts:board-live-alerts', ['--passes' => 1])->assertSuccessful();
        }

        $this->assertEqualsCanonicalizing([
            'Court 23 is live · Delhi High Court',
            'Court 23 at item 10 · Delhi High Court',
            'Court 23 at item A20 · Delhi High Court',
            'Court 23 at item 29 · your item 36',
            'Court 23 at item 31 · your item 36',
            'Court 23 at item 33 · your item 36',
            'Court 23 at item 35 · your item 36',
            'Your item 36 is on now · Court 23',
        ], $asha->notifications()->get()->pluck('data')->pluck('title')->all());
        $this->assertSame('7 to go · Nawab vs Anjuman · RSA/141/2026', $asha->notifications()->get()->firstWhere('data.title', 'Court 23 at item 29 · your item 36')->data['body']);
    }

    public function test_the_supplementary_list_comes_first_and_its_end_is_told(): void
    {
        $this->travelTo('2026-11-27 10:31:00');
        $current = null;
        $this->partialMock(LiveCourtBoard::class, function ($mock) use (&$current): void {
            $mock->shouldReceive('board')->andReturnUsing(function (string $court) use (&$current): array {
                return ['court' => $court, 'short' => 'Delhi High Court', 'ok' => true,
                    'rooms' => [['room' => '23', 'label' => 'Court 23', 'status' => 'running', 'item' => $current]]];
            });
        });
        // Court 23 today: items 1–69, then supplementary items 70–85, which the court takes up first.
        app(CauseListRecorder::class)->ensureSource('delhi-high-court', 'High Court of Delhi', 'https://example.test');
        CourtDocument::create(['provider_key' => 'delhi-high-court', 'document_key' => 'c27', 'listing_date' => '2026-11-27', 'list_type' => 'cause_list',
            'document_label' => 'Cause List of Sitting of Benches for 27.11.2026', 'source_url' => 'https://example.test/c.pdf',
            'metadata' => ['supplementary_items' => ['23' => array_map('strval', range(70, 85))]]]);
        $asha = User::factory()->create();
        $this->actingAs($asha);
        $case = LegalCase::create(['case_title' => 'Nawab vs Anjuman', 'case_number' => 'RSA/141/2026', 'court_name' => 'High Court of Delhi', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => '2026-11-27', 'court_room' => '23', 'item_number' => '66']);
        auth()->forgetGuards();

        // Supplementary items 70–85 first: item 72 is not "past" item 66, and there is no count-down meanwhile.
        foreach (['A70', 'A72', '80', '85', '1', '9', '10', '58', '59', '61', '66'] as $item) {
            $current = $item;
            $this->artisan('courts:board-live-alerts', ['--passes' => 1])->assertSuccessful();
        }

        $alerts = $asha->notifications()->get()->mapWithKeys(fn ($alert): array => [$alert->data['title'] => $alert->data['body']]);
        $this->assertEqualsCanonicalizing([
            'Court 23 is live · Delhi High Court',
            'Court 23: supplementary list over · Delhi High Court',
            'Court 23 at item 10 · Delhi High Court',
            'Court 23 at item 58 · Delhi High Court',
            'Court 23 at item 59 · your item 66',
            'Court 23 at item 61 · your item 66',
            'Your item 66 is on now · Court 23',
        ], $alerts->keys()->all());
        $this->assertSame('Item A70 now (supplementary 70–85) · your item 66 · Nawab vs Anjuman · RSA/141/2026', $alerts['Court 23 is live · Delhi High Court']);
        $this->assertSame('Item 1 now · your item 66 · Nawab vs Anjuman · RSA/141/2026', $alerts['Court 23: supplementary list over · Delhi High Court']);
    }

    public function test_each_run_looks_at_the_boards_four_times_fifteen_seconds_apart(): void
    {
        Sleep::fake();
        $this->partialMock(LiveCourtBoard::class, fn ($mock) => $mock->shouldReceive('board')->times(4)->andReturn(['court' => 'delhi-high-court', 'short' => 'Delhi High Court', 'ok' => true, 'rooms' => []]));
        $this->actingAs(User::factory()->create());
        $case = LegalCase::create(['case_title' => 'A vs B', 'court_name' => 'High Court of Delhi', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => today()->toDateString(), 'court_room' => '23']);
        auth()->forgetGuards();

        $this->artisan('courts:board-live-alerts')->assertSuccessful();

        Sleep::assertSleptTimes(3);
        Sleep::assertSequence([Sleep::for(15)->seconds(), Sleep::for(15)->seconds(), Sleep::for(15)->seconds()]);
    }

    public function test_it_runs_every_minute_in_court_hours(): void
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains((string) $event->command, 'courts:board-live-alerts'));

        $this->assertSame('* * * * 1,2,3,4,5,6', $event->expression);
    }
}
