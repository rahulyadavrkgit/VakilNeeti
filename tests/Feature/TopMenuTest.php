<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TopMenuTest extends TestCase
{
    use RefreshDatabase;

    private function topMenu(string $html): string
    {
        return Str::of($html)->after('data-top-nav>')->before('</nav>')->toString();
    }

    public function test_the_top_menu_has_direct_links_and_second_menus(): void
    {
        $this->actingAs(User::factory()->create());

        $html = $this->get(route('cases.index'))->assertOk()->getContent();
        $menu = $this->topMenu($html);

        $this->assertSame(2, substr_count($menu, 'data-top-menu'), 'Only Courts and More open a second menu');
        $this->assertMatchesRegularExpression('/<a href="'.preg_quote(route('cases.index'), '/').'"\s+aria-current="page"/', $menu);
        $this->assertStringContainsString('href="'.route('billing.index').'"', $menu);
        $this->assertStringNotContainsString('Account administration', $menu);
        $this->assertLessThan(strpos($menu, 'Delhi High Court'), strpos($menu, 'Supreme Court'), 'Courts start with the Supreme Court');

        // The other Cases pages sit in the bar under the header.
        $sectionBar = Str::of($html)->after('class="vn-section-nav')->before('</nav>')->toString();
        foreach (['My cases', 'Calendar', 'Court day', 'Tasks', 'Find &amp; import'] as $label) {
            $this->assertStringContainsString($label, $sectionBar);
        }
        $this->assertStringNotContainsString('Overview', $sectionBar);
        $this->assertStringNotContainsString('Hearing history', $sectionBar);
    }

    public function test_billing_tabs_sit_under_the_header_and_admins_find_administration_in_settings(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $billingBar = Str::of($this->get(route('billing.overdues'))->assertOk()->getContent())->after('class="vn-section-nav')->before('</nav>')->toString();
        foreach (['Overview', 'Invoices', 'Payments', 'Overdues', 'Time sheet', 'Expenses'] as $label) {
            $this->assertStringContainsString($label, $billingBar);
        }
        $this->assertMatchesRegularExpression('/aria-current="page"[^>]*>\s*Overdues/', $billingBar);

        $settings = $this->get(route('profile.index'))->assertOk()->getContent();
        $this->assertStringContainsString('Account administration', Str::of($settings)->after('class="vn-section-nav')->before('</nav>')->toString());
        $this->assertStringContainsString('Account administration', Str::of($settings)->after('aria-label="Account menu"')->before('</details>')->toString());
    }

    public function test_team_members_and_visitors_see_only_their_sections(): void
    {
        $owner = User::factory()->create();
        $this->actingAs(User::factory()->create(['team_owner_id' => $owner->id, 'team_role' => 'clerk']));
        $this->assertStringNotContainsString('Invoices', $this->topMenu($this->get(route('dashboard'))->getContent()));

        auth()->logout();
        $visitor = $this->topMenu($this->get(route('courts.index'))->assertOk()->getContent());
        $this->assertStringNotContainsString('Calendar', $visitor);
        $this->assertStringContainsString('Supreme Court', $visitor);
    }

    public function test_one_header_holds_the_menu_calendar_and_account(): void
    {
        $this->actingAs(User::factory()->create());
        $html = $this->get(route('cases.index'))->assertOk()->getContent();
        $header = Str::of($html)->after('<header class="vn-main-header')->before('</header>')->toString();

        $this->assertSame(1, substr_count($html, 'data-top-nav'), 'The menu appears once, inside the header');
        $this->assertStringContainsString('data-top-nav', $header);
        $this->assertMatchesRegularExpression('/<a href="'.preg_quote(route('cases.calendar'), '/').'" aria-label="Calendar"/', $header);
        $this->assertStringContainsString('aria-label="Account menu"', $header);
        $this->assertStringContainsString('vn-page-trail', $html);
        $this->assertStringNotContainsString('Calendar</a>', Str::of($header)->after('data-top-nav>')->before('</nav>')->toString());
    }
}
