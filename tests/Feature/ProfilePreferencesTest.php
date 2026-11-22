<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilePreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_preferences_stores_the_chosen_currency(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('profile.preferences.update'), [
                'onboarding_features' => ['daily_orders'],
                'currency' => 'USD',
            ])
            ->assertRedirect(route('profile.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('USD', $user->fresh()->currency);
    }

    public function test_rejects_a_currency_that_is_not_offered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('profile.preferences.update'), [
                'onboarding_features' => ['daily_orders'],
                'currency' => 'XYZ',
            ])
            ->assertSessionHasErrors('currency');

        $this->assertSame('INR', $user->fresh()->currency);
    }

    public function test_amounts_are_shown_in_rupees_by_default(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('client-management.index'))
            ->assertOk()
            ->assertSee('Rs. 0.00')
            ->assertDontSee('₹');
    }
}
