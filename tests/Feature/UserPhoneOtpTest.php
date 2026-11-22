<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserPhoneOtpTest extends TestCase
{
    use RefreshDatabase;

    private static string $otp;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.twilio.account_sid' => 'AC00000000000000000000000000000000',
            'services.twilio.auth_token' => 'test-token',
            'services.twilio.from' => '+15005550006',
        ]);

        Storage::fake('public');
        Http::fake(function ($request) {
            preg_match('/OTP is ([0-9]{4})/', $request->body(), $matches);
            self::$otp = $matches[1] ?? '';

            return Http::response(['sid' => 'test-message'], 201);
        });
    }

    public function test_registration_verifies_phone_then_saves_the_advocate_profile(): void
    {
        RateLimiter::clear('registration-otp:+919876543210');

        $this->post(route('register.send-otp'), [
            'mobile' => '9876543210',
        ])->assertRedirect(route('register.verify'));

        $this->assertNotEmpty(self::$otp);

        $this->post(route('register.verify.submit'), ['otp' => self::$otp])
            ->assertRedirect(route('register.profile'));

        $this->assertGuest();

        $this->post(route('register.profile.submit'), [
            'account_role' => 'independent_practitioner',
            'onboarding_features' => ['personalized_hearings', 'judgment_research'],
            'full_name' => 'Asha Advocate',
            'email' => 'asha@example.test',
            'gender' => 'Female',
            'photo' => UploadedFile::fake()->create('advocate.jpg', 20, 'image/jpeg'),
            'years_experience' => 6,
            'graduation_year' => 2017,
            'languages_spoken' => 'Hindi, English',
            'specializations' => ['Civil', 'Family Law'],
            'practice_areas' => 'Civil disputes and family matters',
            'bar_state' => 'Uttar Pradesh',
            'bar_state_code' => 'up',
            'bar_number' => '1234',
            'bar_registration_year' => 2017,
            'city' => 'Lucknow',
            'district' => 'Lucknow',
            'region' => 'Uttar Pradesh',
            'state_code' => 'UP',
            'pincode' => '226001',
            'court' => 'Lucknow Bench',
        ])->assertRedirect(route('cases.find'));

        $user = User::where('mobile', '+919876543210')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('asha@example.test', $user->email);
        $this->assertNotNull($user->mobile_verified_at);
        $this->assertDatabaseHas('advocate_profiles', [
            'user_id' => $user->id,
            'bar_council_registration_number' => 'UP/1234/2017',
            'bar_state_code' => 'UP',
            'bar_number' => '1234',
            'city' => 'Lucknow',
        ]);

        $profile = DB::table('advocate_profiles')->where('user_id', $user->id)->first();
        $this->assertSame(['Hindi', 'English'], json_decode($profile->languages_spoken, true));
        $this->assertSame(['Civil', 'Family Law'], json_decode($profile->specializations, true));
        Storage::disk('public')->assertExists($profile->photo_path);
    }

    public function test_company_registration_saves_firm_details_without_an_advocate_profile(): void
    {
        RateLimiter::clear('registration-otp:+919876543210');

        $this->post(route('register.send-otp'), ['mobile' => '9876543210'])
            ->assertRedirect(route('register.verify'));
        $this->post(route('register.verify.submit'), ['otp' => self::$otp])
            ->assertRedirect(route('register.profile'));

        $this->post(route('register.profile.submit'), [
            'account_role' => 'law_firm',
            'onboarding_features' => ['invoices_expenses_documents'],
            'full_name' => 'Asha Sharma',
            'email' => 'asha@firm.example',
            'firm_name' => 'Sharma Legal Associates',
            'proprietor_name' => 'Asha Sharma',
            'address' => '12 Court Road',
            'city' => 'Lucknow',
            'district' => 'Lucknow',
            'region' => 'Uttar Pradesh',
            'state_code' => '09',
            'pincode' => '226001',
            'firm_email' => 'office@sharmalegal.example',
            'firm_phone' => '9876543210',
        ])->assertRedirect('/');

        $user = User::where('mobile', '+919876543210')->firstOrFail();

        $this->assertSame('company', $user->account_type);
        $this->assertSame('law_firm', $user->account_role);
        $this->assertSame(['invoices_expenses_documents'], $user->onboarding_features);
        $this->assertDatabaseHas('firm_profiles', [
            'user_id' => $user->id,
            'firm_name' => 'Sharma Legal Associates',
            'email' => 'office@sharmalegal.example',
        ]);
        $this->assertDatabaseMissing('advocate_profiles', ['user_id' => $user->id]);
    }

    public function test_existing_users_can_log_in_with_phone_otp_only(): void
    {
        RateLimiter::clear('login-otp:+919876543210');

        $user = User::create([
            'name' => 'Asha Advocate',
            'email' => 'asha@example.test',
            'mobile' => '+919876543210',
            'mobile_verified_at' => now(),
            'password' => Hash::make('unused-password'),
        ]);

        $this->post(route('login.submit'), ['mobile' => '9876543210'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('show_login_otp', true);

        $this->assertNotEmpty(self::$otp);

        $this->post(route('login.verify.submit'), ['otp' => self::$otp])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseMissing('user_registration_otps', ['mobile' => '+919876543210']);
    }
}
