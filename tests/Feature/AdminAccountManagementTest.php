<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CourtHearing;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_first_administrator_gets_a_generated_password_and_there_is_only_one(): void
    {
        $this->artisan('vakilneeti:make-admin', ['email' => 'admin@vakilniti.com', '--name' => 'VakilNiti Admin', '--generate-password' => true])
            ->expectsOutputToContain('Password (shown only now, keep it safe):')
            ->assertSuccessful();

        $admin = User::query()->where('email', 'admin@vakilniti.com')->firstOrFail();
        $this->assertTrue((bool) $admin->is_admin);
        $this->assertNotNull($admin->password_set_at);
        $this->assertFalse(Hash::check('password', $admin->password));

        // A second administrator only through the first one, in Account administration.
        User::factory()->create(['email' => 'rahul@example.test']);
        $this->artisan('vakilneeti:make-admin', ['email' => 'rahul@example.test', '--yes' => true])
            ->expectsOutputToContain('There is already an administrator')
            ->assertFailed();
        $this->artisan('vakilneeti:make-admin', ['email' => 'second@vakilniti.com', '--name' => 'Second', '--generate-password' => true])->assertFailed();
        $this->assertSame(1, User::query()->where('is_admin', true)->count());
    }

    public function test_an_administrator_can_suspend_and_delete_a_practice_with_its_workspace(): void
    {
        $admin = $this->signedInAdmin();
        $owner = User::factory()->create(['email' => 'owner@example.test']);
        $junior = User::factory()->create(['team_owner_id' => $owner->id, 'team_role' => 'junior']);
        $other = User::factory()->create();
        $this->actingAs($owner);
        $case = LegalCase::create(['case_title' => 'Owner v. State', 'court_name' => 'Saket']);
        CourtHearing::create(['case_id' => $case->id, 'hearing_date' => today()->addDay()->toDateString()]);
        Client::create(['first_name' => 'Ravi']);
        $this->actingAs($other);
        $kept = LegalCase::create(['case_title' => 'Other v. State', 'court_name' => 'Saket']);

        $this->actingAs($admin)->withSession($this->adminSession($admin));
        $this->post(route('admin.accounts.access', $owner), ['action' => 'suspend', 'reason' => 'Payment dispute'])->assertSessionHasNoErrors();
        $this->assertNotNull($owner->fresh()->suspended_at);

        $this->get(route('admin.accounts.show', $owner))->assertOk()->assertSee('Delete account');
        $this->delete(route('admin.accounts.destroy', $owner), ['confirm_email' => 'wrong@example.test', 'delete_reason' => 'Asked to close'])
            ->assertSessionHasErrors('confirm_email');
        $this->assertNotNull($owner->fresh());

        $this->delete(route('admin.accounts.destroy', $owner), ['confirm_email' => 'OWNER@example.test', 'delete_reason' => 'Asked to close the account'])
            ->assertRedirect(route('admin.accounts'));

        $this->assertNull($owner->fresh());
        $this->assertNull($junior->fresh());
        $this->assertDatabaseMissing('legal_cases', ['id' => $case->id]);
        $this->assertDatabaseMissing('court_hearings', ['case_id' => $case->id]);
        $this->assertDatabaseMissing('clients', ['first_name' => 'Ravi']);
        $this->assertDatabaseHas('legal_cases', ['id' => $kept->id]);
        $this->assertDatabaseHas('admin_audit_entries', ['account_id' => $owner->id, 'action' => 'delete-account']);
    }

    public function test_administrators_cannot_be_deleted_and_cannot_delete_themselves(): void
    {
        $admin = $this->signedInAdmin();
        $otherAdmin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->withSession($this->adminSession($admin));
        $this->delete(route('admin.accounts.destroy', $otherAdmin), ['confirm_email' => $otherAdmin->email, 'delete_reason' => 'Testing removal'])
            ->assertSessionHasErrors('confirm_email');
        $this->delete(route('admin.accounts.destroy', $admin), ['confirm_email' => $admin->email, 'delete_reason' => 'Testing removal'])->assertForbidden();
        $this->assertNotNull($otherAdmin->fresh());
        $this->assertNotNull($admin->fresh());
    }

    private function signedInAdmin(): User
    {
        return User::factory()->create(['is_admin' => true, 'password_set_at' => now()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function adminSession(User $admin): array
    {
        return ['admin_verified' => ['id' => $admin->id, 'version' => (int) $admin->admin_access_version, 'expires' => now()->addHour()->timestamp]];
    }
}
