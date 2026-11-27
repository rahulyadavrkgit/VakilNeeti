<?php

namespace Tests\Feature;

use App\Models\CourtBench;
use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JudgePhotosAndBoardPickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_official_judge_portraits_are_saved_and_used_in_court_rooms_and_case_timelines(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Http::fake([
            'www.sci.gov.in/*' => Http::response('<div><img src="https://cdn.example/sk.jpg" alt="Judges"><strong class="judge-name">Justice Surya Kant</strong></div>'),
            'delhihighcourt.nic.in/web/CJ_Sitting_Judges?page=1' => Http::response('<img src="/files/2025-04/justice_amit_bansal.jpg" alt="justice_amit_bansal"><img src="/web/themes/logo.png" alt="emblem">'),
            'delhihighcourt.nic.in/web/CJ_Sitting_Judges?page=2' => Http::response('<img src="/files/2025-04/justice_amit_bansal.jpg" alt="justice_amit_bansal">'),
            'delhihighcourt.nic.in/web/CJ_Sitting_Judges' => Http::response('<img src="/files/2025-11/hmj_dinesh_mehta.jpg" alt="JUSTICE DINESH MEHTA">'),
            'cdn.example/*' => Http::response('jpeg-bytes', 200, ['Content-Type' => 'image/jpeg']),
            'delhihighcourt.nic.in/files/*' => Http::response('jpeg-bytes', 200, ['Content-Type' => 'image/jpeg']),
            'raigad.dcourts.gov.in/list-of-judges/' => Http::response(<<<'HTML'
                <table>
                    <tr><th><span>Profile Picture</span></th><th>Name</th><th>Designation</th></tr>
                    <tr><td><img src="https://cdnbbsr.s3waas.gov.in/uploads/jadhav.jpg" alt="Shri. Jadhav"></td><td>Shri.Uday Mahadev Jadhav</td><td>2nd Jt. Civil Judge</td></tr>
                    <tr><td><img src="https://cdnbbsr.s3waas.gov.in/uploads/placeholder.jpg" alt="No Image"></td><td>Shri. Sample Judge</td><td>Civil Judge</td></tr>
                </table>
                HTML),
            'cdnbbsr.s3waas.gov.in/uploads/jadhav.jpg' => Http::response('district-jpeg-bytes', 200, ['Content-Type' => 'image/jpeg']),
            '*.dcourts.gov.in/*' => Http::response('', 404),
        ]);

        $this->artisan('courts:judge-photos')->expectsOutputToContain('Saved 4 photos.')->assertSuccessful();

        Storage::disk('public')->assertExists(['judges/suryakant.jpg', 'judges/dineshmehta.jpg', 'judges/amitbansal.jpg', 'judges/raigad-udaymahadevjadhav.jpg']);
        $this->assertSame(['AMITBANSAL', 'DINESHMEHTA', 'SURYAKANT', 'raigad-UDAYMAHADEVJADHAV'], collect(json_decode(Storage::disk('local')->get(CourtBench::PHOTOS_FILE), true))->keys()->sort()->values()->all());
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'placeholder.jpg'));

        $bench = CourtBench::create(['court_key' => 'delhi-high-court', 'court_room' => '1', 'judges' => [['name' => "Hon'ble Mr. Justice Dinesh Mehta"], ['name' => 'Mr. Arun Goel']]]);
        [$judge, $registrar] = $bench->judgeList();
        // The address carries the photo's version, so browsers may keep it for a year.
        $this->assertMatchesRegularExpression('#/judges/dineshmehta\.jpg\?v=[0-9a-z]+$#', (string) $judge['photo']);
        $this->assertNull($registrar['photo']);
        $this->get(route('courts.judge-photo', ['photo' => 'dineshmehta.jpg', 'v' => 'abc']))->assertOk()->assertStreamedContent('jpeg-bytes')
            ->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');
        $this->get(route('courts.judge-photo', ['photo' => 'dineshmehta.jpg']))->assertOk()->assertHeader('Cache-Control', 'max-age=3600, public');
        $this->get(route('courts.judge-photo', ['photo' => 'missing.jpg']))->assertNotFound();

        $this->actingAs(User::factory()->create());
        $case = LegalCase::create(['case_title' => 'Applicant vs Respondent', 'court_name' => 'Delhi High Court', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => today()->addDay(), 'judge_name' => 'Hon’ble Mr. Justice Dinesh Mehta']);
        // If the photo cannot load, the page shows the judge's initials instead.
        $this->get(route('cases.show', $case))->assertOk()->assertSee('src="/courts/judges/dineshmehta.jpg?v=', false)
            ->assertSee('alt="" loading="lazy" width="28" height="28" data-initials="DM"', false);
        $this->assertSame(['RG', 'PS', 'AG'], [CourtBench::initials("Hon'ble Mr. Justice Rajneesh Kumar Gupta"), CourtBench::initials('Hon’ble Ms. Justice Prathiba M. Singh'), CourtBench::initials('Mr. Arun Goel')]);
        $districtCase = LegalCase::create(['case_title' => 'Mahad case', 'court_name' => '2nd Jt C.J.J.D. and J.M.F.C. Mahad', 'court_level' => 'district', 'district' => 'Raigad', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $districtCase->id, 'hearing_date' => today()->addDay(), 'judge_name' => 'Sh. Uday Mahadev Jadhav']);
        $this->get(route('cases.show', $districtCase))->assertOk()->assertSee('src="/courts/judges/raigad-udaymahadevjadhav.jpg?v=', false);
        $this->assertNull(CourtBench::photoFor('Uday Mahadev Jadhav', 'other district'));

        $this->assertSame('PRATHIBAMSINGH', CourtBench::judgeKey("Hon'ble Ms. Justice Prathiba M. Singh"));
        $this->assertSame('0 5 * * 0', collect(app(Schedule::class)->events())->first(fn ($event) => str_contains((string) $event->command, 'courts:judge-photos'))?->expression);
    }

    public function test_the_board_picker_supports_typing_a_board_name_and_filtering_court_numbers(): void
    {
        Http::fake(['delhihighcourt.nic.in/app/courtdata' => Http::response(['all_courts' => ['1' => '12']])]);
        $this->actingAs(User::factory()->create());

        $this->get(route('courts.display-boards'))->assertOk()
            ->assertSeeInOrder(['data-board-rooms-picker', 'data-board-rooms-search', 'data-board-combo-toggle', 'data-board-search role="combobox"'], false)
            ->assertDontSee('data-board-rooms-list', false)
            ->assertSee('Type a board name');

        $this->get(route('courts.live-board', 'delhi-high-court'))->assertOk()
            ->assertSee("type === 'vn-board-rooms'", false)->assertSee('data-label="', false)
            ->assertSee('vn-court-board', false)->assertSee('data-live-room-search', false);
    }

    public function test_a_court_badge_is_not_repeated_by_a_court_name_chip(): void
    {
        $this->actingAs(User::factory()->create());
        LegalCase::create(['case_title' => 'High vs Court', 'court_name' => 'Allahabad High Court', 'court_room' => '90', 'case_status' => 'pending']);
        LegalCase::create(['case_title' => 'Bench vs Named', 'court_name' => '2nd Jt C.J.J.D. and J.M.F.C. Mahad', 'court_room' => '4', 'case_status' => 'pending']);

        $html = $this->get(route('cases.index'))->assertOk()->getContent();

        $this->assertStringContainsString('text-slate-600">2nd Jt C.J.J.D. and J.M.F.C. Mahad</p>', $html);
        $this->assertStringNotContainsString('text-slate-600">Allahabad High Court</p>', $html);
        $this->assertStringNotContainsString('Join VC', $html);
    }
}
