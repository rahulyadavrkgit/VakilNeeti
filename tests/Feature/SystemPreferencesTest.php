<?php

namespace Tests\Feature;

use App\Models\CourtHearing;
use App\Models\FirmProfile;
use App\Models\LegalCase;
use App\Models\Task;
use App\Models\User;
use App\Services\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemPreferencesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-03-10 09:00:00');
    }

    public function test_settings_page_shows_the_system_preferences_form_with_defaults(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('profile.index'))
            ->assertOk()
            ->assertSee('id="system-preferences"', false)
            ->assertSee(route('profile.system-preferences.update'), false)
            ->assertSee('Save system preferences')
            ->assertDontSee('Planned');
    }

    public function test_saving_system_preferences_stores_every_choice(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('profile.system-preferences.update'), [
                'calendar_view' => 'week',
                'week_starts_on' => 'monday',
                'home_display_boards' => '0',
                'remind_hearings' => 'today',
                'remind_appointments' => '1',
            ])
            ->assertRedirect(route('profile.index').'#system-preferences')
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('week', $user->preference('calendar_view'));
        $this->assertSame('monday', $user->preference('week_starts_on'));
        $this->assertFalse($user->preference('home_display_boards'));
        $this->assertSame('today', $user->preference('remind_hearings'));
        $this->assertTrue($user->preference('remind_appointments'));
        $this->assertFalse($user->preference('remind_tasks'));
        $this->assertFalse($user->preference('remind_invoices'));
    }

    public function test_rejects_options_that_are_not_offered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('profile.system-preferences.update'), [
                'calendar_view' => 'year',
                'week_starts_on' => 'friday',
                'remind_hearings' => 'always',
            ])
            ->assertSessionHasErrors(['calendar_view', 'week_starts_on', 'remind_hearings']);

        $this->assertNull($user->fresh()->preferences);
    }

    public function test_calendar_opens_on_the_chosen_view_and_week_start(): void
    {
        $this->actingAs(User::factory()->create(['preferences' => ['calendar_view' => 'week', 'week_starts_on' => 'monday']]));

        $response = $this->get(route('cases.calendar', ['date' => '2026-03-10']))->assertOk();

        $this->assertSame('week', $response->viewData('viewMode'));
        $this->assertSame('2026-03-09', $response->viewData('start')->toDateString());
        $this->assertSame('2026-03-15', $response->viewData('end')->toDateString());
        $this->assertSame('Mon', $response->viewData('weekdayLabels')[0]);

        $this->assertSame('month', $this->get(route('cases.calendar', ['view' => 'month']))->viewData('viewMode'));
    }

    public function test_home_hides_display_boards_when_turned_off(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-board-picker', false);

        $this->actingAs(User::factory()->create(['preferences' => ['home_display_boards' => false]]))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('data-board-picker', false);
    }

    public function test_reminders_follow_the_chosen_preferences(): void
    {
        $user = User::factory()->create(['preferences' => ['remind_hearings' => 'today', 'remind_tasks' => false]]);
        $this->actingAs($user);
        $case = LegalCase::create(['case_title' => 'Asha vs State', 'court_name' => 'District Court Lucknow', 'case_status' => 'pending']);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => '2026-03-10']);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => '2026-03-11']);
        Task::create(['title' => 'File reply', 'due_at' => '2026-03-08 10:00:00', 'priority' => 'high', 'status' => 'pending']);

        $this->assertSame(1, app(ReminderService::class)->sendFor($user));
        $this->assertSame('Hearing today', $user->notifications()->first()->data['title']);

        $user->forceFill(['preferences' => ['remind_hearings' => 'off', 'remind_tasks' => true]])->save();
        $user->notifications()->delete();

        $this->assertSame(1, app(ReminderService::class)->sendFor($user->fresh()));
        $this->assertSame('Task overdue since 08 Mar', $user->notifications()->first()->data['title']);
    }

    public function test_tds_settings_are_saved_by_a_button_after_the_tds_section(): void
    {
        $this->actingAs(User::factory()->create());
        FirmProfile::create(['firm_name' => 'Sharma Legal', 'is_active' => true]);

        $html = $this->get(route('profile.invoice-settings.index'))
            ->assertOk()
            ->getContent();

        $tds = strpos($html, 'TDS / Tax Deducted at Source');
        $professionalTax = strpos($html, 'Professional Tax Compliance');
        $saveButton = strpos($html, 'Save Invoice, GST &amp; TDS Settings');

        $this->assertNotFalse($tds);
        $this->assertGreaterThan($tds, $saveButton);
        $this->assertGreaterThan($professionalTax, $saveButton);
    }
}
