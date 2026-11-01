<?php

namespace Tests\Feature\Models;

use App\Models\Credential;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CredentialModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
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

    public function test_fillable_lists_the_six_credential_columns(): void
    {
        $credential = new Credential();

        $this->assertSame(
            ['user_id', 'app_name', 'username', 'password', 'link', 'note'],
            $credential->getFillable(),
        );
    }

    public function test_password_mutator_encrypts_non_null_value(): void
    {
        $credential = new Credential();
        $credential->password = 's3cret-pass';

        $stored = $credential->getAttributes()['password'];

        $this->assertNotSame('s3cret-pass', $stored);
        $this->assertSame('s3cret-pass', Crypt::decryptString($stored));
    }

    public function test_password_mutator_returns_null_for_null(): void
    {
        $credential = new Credential();
        $credential->password = null;

        $this->assertNull($credential->getAttributes()['password']);
    }

    public function test_password_accessor_decrypts_encrypted_value(): void
    {
        $encrypted = Crypt::encryptString('plain-value');
        $credential = new Credential();
        $credential->setRawAttributes(['password' => $encrypted]);

        $this->assertSame('plain-value', $credential->password);
    }

    public function test_password_accessor_returns_plain_string_when_not_encrypted(): void
    {
        $credential = new Credential(['password' => 'not-encrypted']);

        $this->assertSame('not-encrypted', $credential->password);
    }

    public function test_password_accessor_returns_null_when_attribute_is_null(): void
    {
        $credential = new Credential();

        $this->assertNull($credential->password);
    }

    public function test_password_round_trips_through_db(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $credential = Credential::factory()->create([
            'user_id' => $user->id,
            'password' => 'round-trip-secret',
        ]);

        $fresh = $credential->fresh();

        $this->assertSame('round-trip-secret', $fresh->password);
        $this->assertNotSame('round-trip-secret', $fresh->getRawOriginal('password'));
    }

    public function test_password_null_fails_at_db_level_due_to_not_null_constraint(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Credential::factory()->create([
            'user_id' => $user->id,
            'password' => null,
        ]);
    }

    public function test_user_relation_returns_owning_user(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $credential = Credential::factory()->create(['user_id' => $user->id]);

        $this->assertSame($user->id, $credential->user->id);
    }

    public function test_decrypt_or_plain_returns_null_for_null(): void
    {
        $credential = new Credential();

        $method = new \ReflectionMethod($credential, 'decryptOrPlain');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($credential, null));
    }

    public function test_decrypt_or_plain_returns_input_when_decryption_fails(): void
    {
        $credential = new Credential();

        $method = new \ReflectionMethod($credential, 'decryptOrPlain');
        $method->setAccessible(true);

        $this->assertSame('garbage-payload', $method->invoke($credential, 'garbage-payload'));
    }

    public function test_encrypt_or_null_returns_null_for_null(): void
    {
        $credential = new Credential();

        $method = new \ReflectionMethod($credential, 'encryptOrNull');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($credential, null));
    }
}