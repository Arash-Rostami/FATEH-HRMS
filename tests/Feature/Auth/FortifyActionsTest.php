<?php

namespace Tests\Feature\Auth;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FortifyActionsTest extends TestCase
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

    public function test_create_new_user_creates_user_with_hashed_password(): void
    {
        $action = new CreateNewUser;

        $user = $action->create([
            'name' => 'Fortify Test User',
            'email' => 'fortify-test@example.com',
            'password' => 'supersecret123',
            'password_confirmation' => 'supersecret123',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', ['email' => 'fortify-test@example.com']);
        $this->assertTrue(Hash::check('supersecret123', $user->fresh()->password));
    }

    public function test_create_new_user_rejects_duplicate_email(): void
    {
        $existing = User::factory()->create(['email' => 'duplicate@example.com']);

        $this->expectException(ValidationException::class);

        (new CreateNewUser)->create([
            'name' => 'Another User',
            'email' => $existing->email,
            'password' => 'supersecret123',
            'password_confirmation' => 'supersecret123',
        ]);
    }

    public function test_create_new_user_rejects_short_password(): void
    {
        $this->expectException(ValidationException::class);

        (new CreateNewUser)->create([
            'name' => 'Short Pwd User',
            'email' => 'short-pwd@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);
    }

    public function test_create_new_user_rejects_unconfirmed_password(): void
    {
        $this->expectException(ValidationException::class);

        (new CreateNewUser)->create([
            'name' => 'Mismatch Pwd User',
            'email' => 'mismatch-pwd@example.com',
            'password' => 'supersecret123',
            'password_confirmation' => 'mismatch',
        ]);
    }

    public function test_reset_user_password_sets_new_hashed_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        (new ResetUserPassword)->reset($user, [
            'password' => 'newsupersecret123',
            'password_confirmation' => 'newsupersecret123',
        ]);

        $fresh = $user->fresh();
        $this->assertTrue(Hash::check('newsupersecret123', $fresh->password));
        $this->assertFalse(Hash::check('password', $fresh->password));
    }

    public function test_reset_user_password_rejects_short_password(): void
    {
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        (new ResetUserPassword)->reset($user, [
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);
    }
}