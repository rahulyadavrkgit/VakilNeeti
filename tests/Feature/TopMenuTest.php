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

        $menu = $this->topMenu($this->get(route('cases.index'))->assertOk()->getContent());

        $this->assertSame(4, substr_count($menu, 'data-top-menu'), 'Cases, Billing, Courts and More open a second menu');
        foreach (['Home', 'Calendar', 'Clients', 'Court day', 'Hearing history', 'Invoices', 'Supreme Court', 'Bare Acts'] as $label) {
            $this->assertStringContainsString($label, $menu);
        }
        $this->assertMatchesRegularExpression('/<summary[^>]*aria-current="true"[^>]*>\s*Cases/', $menu);
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
}
