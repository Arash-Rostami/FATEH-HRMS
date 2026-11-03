<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
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