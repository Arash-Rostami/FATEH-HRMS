<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\ResetPassword;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
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

    public function test_mount_sets_token_and_email_from_query(): void
    {
        Livewire::withQueryParams(['email' => 'q@example.com'])
            ->test(ResetPassword::class, ['token' => 'tok-abc'])
            ->assertSet('token', 'tok-abc')
            ->assertSet('email', 'q@example.com');
    }

    public function test_submit_reset_with_valid_token_resets_password_and_redirects_to_login(): void
    {
        $user = User::factory()->create();
        $originalRememberToken = $user->remember_token;
        $token = Password::broker()->createToken($user);

        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', $user->email)
            ->set('password', 'newsupersecret')
            ->set('password_confirmation', 'newsupersecret')
            ->call('submitReset')
            ->assertRedirect(route('login'))
            ->assertSessionHas('statusSwitcher')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('newsupersecret', $user->fresh()->password));
        $this->assertNotSame($originalRememberToken, $user->fresh()->remember_token);
    }

    public function test_submit_reset_with_invalid_token_adds_error(): void
    {
        $user = User::factory()->create();

        Livewire::test(ResetPassword::class, ['token' => 'bad-token'])
            ->set('email', $user->email)
            ->set('password', 'newsupersecret')
            ->set('password_confirmation', 'newsupersecret')
            ->call('submitReset')
            ->assertHasErrors(['email'])
            ->assertSessionMissing('statusSwitcher');
    }

    public function test_submit_reset_validation_requires_password_min_eight(): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', $user->email)
            ->set('password', 'short')
            ->set('password_confirmation', 'short')
            ->call('submitReset')
            ->assertHasErrors(['password']);
    }

    public function test_submit_reset_password_confirmation_mismatch_adds_error(): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', $user->email)
            ->set('password', 'supersecret')
            ->set('password_confirmation', 'differentpass')
            ->call('submitReset')
            ->assertHasErrors(['password']);
    }
}