<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        $this->app->setLocale('fa');
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function useMysql(): void
    {
        $env = base_path('.env');
        if (is_file($env)) {
            $vals = [];
            foreach (explode("\n", (string) file_get_contents($env)) as $line) {
                if (preg_match('/^\s*(DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|DB_PASSWORD)\s*=\s*(.*)$/', $line, $m)) {
                    $vals[$m[1]] = trim(preg_replace('/\s+#.*$/', '', trim($m[2])), "\"' \t");
                }
            }
            $map = ['DB_HOST' => 'host', 'DB_PORT' => 'port', 'DB_DATABASE' => 'database', 'DB_USERNAME' => 'username', 'DB_PASSWORD' => 'password'];
            foreach ($map as $envKey => $cfgKey) {
                if (isset($vals[$envKey])) {
                    config(['database.connections.mysql.' . $cfgKey => $vals[$envKey]]);
                }
            }
        }
        DB::purge('mysql');
        config(['database.default' => 'mysql']);
    }

    public function test_valid_credentials_authenticate_and_redirect_to_dashboard(): void
    {
        $user = User::factory()->create();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertSet('outcome', 'success')
            ->assertSee('ورود موفق');

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_remembered_launchpad_address_never_becomes_the_landing_page_after_login(): void
    {
        $user = User::factory()->create();
        session(['url.intended' => url('/launchpad')]);

        $component = Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertSet('outcome', 'success');

        $effects = json_encode($component->effects);
        $this->assertStringContainsString('dashboard', $effects);
        $this->assertStringNotContainsString('launchpad', $effects);
        $this->assertNull(session('url.intended'));
    }

    public function test_a_launchpad_sso_entry_address_still_brings_the_user_back_after_login(): void
    {
        $user = User::factory()->create();
        session(['url.intended' => url('/launchpad/12/go')]);

        $component = Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertSet('outcome', 'success');

        $this->assertStringContainsString('launchpad', json_encode($component->effects));
    }

    public function test_another_remembered_address_still_brings_the_user_back_after_login(): void
    {
        $user = User::factory()->create();
        session(['url.intended' => url('/channels')]);

        $component = Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertSet('outcome', 'success');

        $this->assertStringContainsString('channels', json_encode($component->effects));
    }

    public function test_failed_login_flags_the_button_as_failed(): void
    {
        $user = User::factory()->create();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertSet('outcome', 'fail')
            ->assertSee('ورود ناموفق');
    }

    public function test_invalid_password_adds_email_error_and_does_not_authenticate(): void
    {
        $user = User::factory()->create();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest();
    }

    public function test_nonexistent_email_adds_email_error(): void
    {
        Livewire::test(Login::class)
            ->set('email', 'nobody-' . uniqid() . '@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest();
    }

    public function test_throttle_blocks_after_four_failed_attempts(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 4; $i++) {
            Livewire::test(Login::class)
                ->set('email', $user->email)
                ->set('password', 'wrong-password')
                ->call('login')
                ->assertHasErrors(['email']);
        }

        $component = Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('login');

        $component->assertHasErrors(['email']);
        $this->assertStringContainsString('تلاش‌های ورود', $component->errors()->first('email'));
        $this->assertGuest();
    }

    public function test_validation_requires_email(): void
    {
        Livewire::test(Login::class)
            ->set('email', '')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['email']);
    }

    public function test_validation_requires_password(): void
    {
        $user = User::factory()->create();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', '')
            ->call('login')
            ->assertHasErrors(['password']);
    }

    public function test_password_shorter_than_eight_adds_error(): void
    {
        $user = User::factory()->create();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'short')
            ->call('login')
            ->assertHasErrors(['password']);
    }

    public function test_invalid_email_format_adds_error(): void
    {
        Livewire::test(Login::class)
            ->set('email', 'not-an-email')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['email']);
    }

    public function test_inactive_user_is_blocked_from_logging_in(): void
    {
        $user = User::factory()->create(['status' => 'inactive']);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest();
    }

    public function test_inactive_user_with_active_session_is_booted_on_next_request(): void
    {
        $user = User::factory()->create(['status' => 'inactive']);

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    private int $smsStatus = 200;

    private function otpOn(?string &$code = null): void
    {
        $this->smsStatus = 200;

        config([
            'app.off' => [],
            'services.sms.provider' => 'kavenegar',
            'services.kavenegar.key' => 'KEY',
        ]);

        Http::fake(function ($request) use (&$code) {
            preg_match('/\d{5}/', (string) ($request['message'] ?? ''), $match);
            $code = $match[0] ?? null;

            return Http::response(['return' => ['status' => $this->smsStatus, 'message' => 'x'], 'entries' => []]);
        });
    }

    private function otpUser(string $cellphone = '09190000123', bool $active = true): User
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id, 'cellphone' => $cellphone]);

        if (! $active) {
            $user->forceFill(['status' => 'inactive'])->save();
        }

        return $user;
    }

    public function test_sms_code_login_is_hidden_and_refused_when_the_tenant_has_sms_off(): void
    {
        $this->otpOn();
        config(['app.off' => ['notify:sms']]);
        $this->otpUser();

        Livewire::test(Login::class)
            ->assertDontSee('ورود با پیامک')
            ->set('smsLogin', true)
            ->assertSet('smsLogin', false)
            ->set('phone', '09190000123')
            ->call('sendCode')
            ->assertHasErrors('phone');

        Http::assertNothingSent();
    }

    public function test_sms_code_logs_in_a_matching_active_user_once_and_accepts_persian_digits(): void
    {
        $code = null;
        $this->otpOn($code);
        $user = $this->otpUser();

        $component = Livewire::test(Login::class)
            ->assertSee('ورود با پیامک')
            ->set('smsLogin', true)
            ->set('phone', '۰۹۱۹۰۰۰۰۱۲۳')
            ->call('sendCode')
            ->assertSet('codeSent', true)
            ->assertDispatched('login-code-sent', seconds: 60);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'sms/send.json') && str_contains($request['message'], (string) $code));

        $component->set('code', (string) $code)->call('verifyCode')->assertSet('outcome', 'success');
        $this->assertAuthenticatedAs($user);

        Auth::logout();

        Livewire::test(Login::class)
            ->set('smsLogin', true)
            ->set('phone', '09190000123')
            ->set('code', (string) $code)
            ->call('verifyCode')
            ->assertHasErrors('code');
        $this->assertGuest();
    }

    public function test_sms_code_is_refused_with_a_clear_message_for_unknown_and_inactive_numbers_without_any_sms(): void
    {
        $this->otpOn();
        $this->otpUser('09127777777', active: false);

        foreach (['09128888888', '09127777777'] as $number) {
            Livewire::test(Login::class)
                ->set('smsLogin', true)
                ->set('phone', $number)
                ->call('sendCode')
                ->assertSet('codeSent', false)
                ->assertHasErrors('phone');
        }

        Http::assertNothingSent();
    }

    public function test_failed_sms_sends_do_not_use_up_the_hourly_cap(): void
    {
        $code = null;
        $this->otpOn($code);
        $this->otpUser();
        $this->smsStatus = 418;

        $component = Livewire::test(Login::class)->set('smsLogin', true)->set('phone', '09190000123');

        foreach (range(1, 6) as $ignored) {
            $component->call('sendCode')->assertSet('codeSent', false)->assertHasErrors('phone');
        }

        $this->smsStatus = 200;

        $component->call('sendCode')->assertSet('codeSent', true);
    }

    public function test_five_wrong_codes_burn_the_code_and_the_resend_waits_out_the_cooldown(): void
    {
        $code = null;
        $this->otpOn($code);
        $this->otpUser();

        $component = Livewire::test(Login::class)
            ->set('smsLogin', true)
            ->set('phone', '09190000123')
            ->call('sendCode');

        foreach (range(1, 5) as $ignored) {
            $component->set('code', '00000')->call('verifyCode')->assertHasErrors('code');
        }

        $component->set('code', (string) $code)->call('verifyCode')->assertHasErrors('code');
        $this->assertGuest();

        $component->call('sendCode')->assertHasErrors('phone');
        Http::assertSentCount(1);
    }

    public function test_forgot_password_link_follows_the_tenant_password_reset_switch(): void
    {
        config(['app.off' => []]);
        Livewire::test(Login::class)->assertSee('فراموشی رمز عبور؟');

        config(['app.off' => ['password-reset']]);
        Livewire::test(Login::class)->assertDontSee('فراموشی رمز عبور؟');
    }

    public function test_each_card_face_is_inert_while_hidden_and_only_the_login_page_gets_the_back_face()
    {
        $siblings = [['name' => 'BMS', 'title' => 'سامانه بی‌ام‌اس', 'url' => 'https://bms.example.test', 'logo' => null]];
        config(['app.siblings' => $siblings, 'app.off' => []]);

        Livewire::test(Login::class)
            ->assertSee(':inert="flipped"', false)
            ->assertSee(':inert="!flipped"', false)
            ->assertSee('flip_to_front')
            ->assertSee('north_east');

        Livewire::test(ForgotPassword::class)
            ->assertDontSee('سامانه‌های مرتبط')
            ->assertDontSee('flip_to_front')
            ->assertDontSee('bms.example.test');
    }

    public function test_sibling_back_face_follows_the_tenant_auth_sibling_switch_and_opens_in_a_new_tab(): void
    {
        $siblings = [
            ['name' => 'BMS', 'title' => 'سامانه بی‌ام‌اس', 'url' => 'https://bms.example.test', 'logo' => 'build/assets/img/persol/sibling/bms.png'],
            ['name' => 'CRM', 'title' => 'سامانه مشتریان', 'url' => 'https://crm.example.test', 'logo' => null],
        ];

        config(['app.siblings' => $siblings, 'app.off' => ['auth:sibling']]);
        Livewire::test(Login::class)->assertDontSee('سامانه‌های مرتبط')->assertDontSee('bms.example.test');

        config(['app.siblings' => [], 'app.off' => []]);
        Livewire::test(Login::class)->assertDontSee('سامانه‌های مرتبط');

        config(['app.siblings' => $siblings, 'app.off' => []]);
        Livewire::test(Login::class)
            ->assertSee('سامانه‌های مرتبط')
            ->assertSee('سامانه بی‌ام‌اس')
            ->assertSee('https://bms.example.test', false)
            ->assertSee('target="_blank"', false)
            ->assertSee('sibling', false)->assertSee('bms.png', false)
            ->assertSee('web_asset');
    }
}