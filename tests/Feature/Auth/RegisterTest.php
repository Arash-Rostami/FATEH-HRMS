<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Register;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class RegisterTest extends TestCase
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

    public function test_valid_registration_creates_user_logs_in_and_redirects_to_dashboard(): void
    {
        $email = fake()->unique()->safeEmail();

        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', $email)
            ->set('password', 'supersecret')
            ->set('password_confirmation', 'supersecret')
            ->call('register')
            ->assertRedirect('/dashboard')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => $email]);

        $user = User::where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('supersecret', $user->fresh()->password));
    }

    public function test_duplicate_email_adds_unique_error(): void
    {
        $existing = User::factory()->create(['status' => 'active']);

        Livewire::test(Register::class)
            ->set('name', 'Another User')
            ->set('email', $existing->email)
            ->set('password', 'supersecret')
            ->set('password_confirmation', 'supersecret')
            ->call('register')
            ->assertHasErrors(['email']);
    }

    public function test_password_confirmation_mismatch_adds_error(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', fake()->unique()->safeEmail())
            ->set('password', 'supersecret')
            ->set('password_confirmation', 'differentpass')
            ->call('register')
            ->assertHasErrors(['password']);
    }

    public function test_password_shorter_than_eight_adds_error(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', fake()->unique()->safeEmail())
            ->set('password', 'short')
            ->set('password_confirmation', 'short')
            ->call('register')
            ->assertHasErrors(['password']);
    }

    public function test_name_required_adds_error(): void
    {
        Livewire::test(Register::class)
            ->set('name', '')
            ->set('email', fake()->unique()->safeEmail())
            ->set('password', 'supersecret')
            ->set('password_confirmation', 'supersecret')
            ->call('register')
            ->assertHasErrors(['name']);
    }

    public function test_throttle_blocks_after_three_attempts(): void
    {
        $email = fake()->unique()->safeEmail();
        $key = 'register:' . Str::lower($email) . '|' . request()->ip();

        RateLimiter::hit($key, 300);
        RateLimiter::hit($key, 300);
        RateLimiter::hit($key, 300);

        $component = Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', $email)
            ->set('password', 'supersecret')
            ->set('password_confirmation', 'supersecret')
            ->call('register')
            ->assertHasErrors(['email']);

        $this->assertStringContainsString('تلاش‌های ثبت‌نام', $component->instance()->getErrorBag()->first('email'));
    }
}