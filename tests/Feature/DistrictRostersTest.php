<?php

namespace Tests\Feature;

use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DistrictRostersTest extends TestCase
{
    use RefreshDatabase;

    private const CDN = 'https://cdnbbsr.s3waas.gov.in/s3ec02bd85282513da4089c441926e1975/uploads';

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-27 10:00:00');
        Cache::flush();
        Storage::fake('local');
        Http::fake([
            'southdelhi.dcourts.gov.in/?s=bail+roster' => Http::response('<ul>'
                .'<li><a href="'.self::CDN.'/2026/10/2026100193.pdf">Order regarding Bail Roster of South District w.e.f 01.10.2026. (PDF 757KB)</a></li>'
                .'<li><a href="'.self::CDN.'/2026/11/2026110651.pdf">Order regarding Bail Roster w.e.f. 05.11.2026 till further orders. (PDF 725KB)</a></li>'
                .'<li><a href="'.self::CDN.'/2026/11/2026111999.pdf">Notice regarding winter vacation (PDF 100KB)</a></li>'
                .'<li><a href="https://elsewhere.example/2026112000.pdf">Bail Roster copy elsewhere</a></li>'
                .'</ul>'),
            'southdelhi.dcourts.gov.in/duty-magistrate/' => Http::response('down', 503),
            'cdnbbsr.s3waas.gov.in/*' => Http::response('%PDF-1.7 roster', 200, ['Content-Type' => 'application/pdf']),
        ]);
    }

    public function test_the_latest_bail_roster_opens_inside_vakilniti_with_share_back_and_download(): void
    {
        $page = $this->get(route('courts.delhi-district-courts.document', ['south', 'bail-roster']))->assertOk()
            ->assertSee('Bail roster · South')
            // The newest roster by its upload date, not other notices or files on other sites.
            ->assertSee('Order regarding Bail Roster w.e.f. 05.11.2026 till further orders')
            ->assertSee('Uploaded 06 Nov 2026')
            ->assertSee('data-pdf-viewer data-src="'.route('courts.delhi-district-courts.document.pdf', ['south', 'bail-roster']).'"', false)
            ->assertSee('href="'.route('courts.delhi-district-courts', ['complex' => 'saket']).'" data-vn-back', false)
            ->assertSee('href="https://southdelhi.dcourts.gov.in/?s=bail+roster"', false)
            ->assertDontSee('winter vacation');
        $this->assertStringContainsString(e('PDF: '.self::CDN.'/2026/11/2026110651.pdf'), $page->getContent());

        // The PDF itself comes from VakilNiti's copy, readable inside the page on any phone.
        $pdf = $this->get(route('courts.delhi-district-courts.document.pdf', ['south', 'bail-roster']))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame('%PDF-1.7 roster', $pdf->streamedContent());
        Http::assertSentCount(2);
    }

    public function test_a_district_site_that_does_not_answer_offers_its_own_page_and_unknown_addresses_are_not_found(): void
    {
        $this->get(route('courts.delhi-district-courts.document', ['south', 'duty-magistrate']))->assertOk()
            ->assertSee("The court's website is not answering just now", false)
            ->assertSee('href="https://southdelhi.dcourts.gov.in/duty-magistrate/"', false)
            ->assertDontSee('data-pdf-viewer', false);

        $this->get('/courts/delhi-district-courts/nowhere/bail-roster')->assertNotFound();
        $this->get('/courts/delhi-district-courts/south/something-else')->assertNotFound();
    }

    public function test_your_matters_this_week_show_in_their_complex_and_that_complex_opens_first(): void
    {
        $this->actingAs(User::factory()->create());
        $case = LegalCase::create(['case_title' => 'Neelofer vs Hafiz', 'court_name' => 'District Judge - 05', 'court_complex' => 'District and Sessions Judge, South-East, Saket, Delhi',
            'court_room' => '612', 'judge_name' => 'Sh. Akash Jain', 'vc_link' => 'https://vc.example/612', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => '2026-11-28']);

        $this->get(route('courts.delhi-district-courts'))->assertOk()
            ->assertSee('data-complex-pick="saket" aria-pressed="true"', false)
            ->assertSeeInOrder(['Your matters in Saket', 'Neelofer vs Hafiz', 'Tomorrow', 'Room 612', 'Sh. Akash Jain', 'href="https://vc.example/612"'], false);
    }

    public function test_rosters_are_refreshed_morning_and_evening(): void
    {
        $this->artisan('courts:district-documents')->assertSuccessful();
        Storage::disk('local')->assertExists('district-documents/south-bail-roster-'.md5(self::CDN.'/2026/11/2026110651.pdf').'.pdf');

        $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains((string) $event->command, 'courts:district-documents'));
        $this->assertSame('30 6,18 * * *', $event->expression);
    }
}
