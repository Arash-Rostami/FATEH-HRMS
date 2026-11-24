<?php

namespace Tests\Feature\Http;

use App\Jobs\SendChatPushJob;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PushSubscriptionControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        $this->runAfterCommitNow();
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

    private function runAfterCommitNow(): void
    {
        DB::connection()->setTransactionManager(new class extends DatabaseTransactionsManager {
            public function addCallback($callback)
            {
                $callback();
            }
        });
    }

    private function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            '_token' => 'tok',
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc',
            'keys' => ['p256dh' => $this->b64("\x04" . str_repeat('a', 64)), 'auth' => $this->b64(str_repeat('b', 16))],
            'scopes' => ['contact' => true, 'channel' => false],
        ], $overrides);
    }

    private function submit(User $user, array $overrides = []): TestResponse
    {
        return $this->actingAs($user)->withSession(['_token' => 'tok'])->postJson(route('push.subscription'), $this->payload($overrides));
    }

    public function test_a_guest_gets_a_json_401(): void
    {
        $this->postJson(route('push.subscription'), $this->payload())->assertUnauthorized();
    }

    public function test_a_missing_or_wrong_body_token_is_a_419(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)->withSession(['_token' => 'tok'])
            ->postJson(route('push.subscription'), array_diff_key($this->payload(), ['_token' => 1]))
            ->assertStatus(419);

        $this->submit($user, ['_token' => 'other'])->assertStatus(419);
        $this->assertSame(0, PushSubscription::where('user_id', $user->id)->count());
    }

    public function test_endpoints_outside_the_push_service_allowlist_are_rejected(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        foreach ([
            'http://fcm.googleapis.com/fcm/send/abc',
            'https://evil.example.com/fcm/send/abc',
            'https://fcm.googleapis.com.evil.com/abc',
            'https://evilfcm.googleapis.com/abc',
            'https://user@fcm.googleapis.com/abc',
            'https://fcm.googleapis.com:8443/abc',
            'https://updates.push.services.mozilla.com.evil.com/abc',
            'https://127.0.0.1/abc',
        ] as $endpoint) {
            $this->submit($user, ['endpoint' => $endpoint])->assertStatus(422);
        }

        $this->assertSame(0, PushSubscription::where('user_id', $user->id)->count());
    }

    public function test_every_supported_push_service_host_is_accepted(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        foreach ([
            'https://fcm.googleapis.com/fcm/send/abc',
            'https://updates.push.services.mozilla.com/wpush/v2/abc',
            'https://web.push.apple.com/abc',
            'https://wns2-par02p.notify.windows.com/w/?token=abc',
        ] as $endpoint) {
            $this->submit($user, ['endpoint' => $endpoint])->assertNoContent();
        }

        $this->assertSame(4, PushSubscription::where('user_id', $user->id)->count());
    }

    public function test_keys_that_are_not_a_real_p256dh_and_auth_are_rejected(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->submit($user, ['keys' => ['p256dh' => 'abc']])->assertStatus(422);
        $this->submit($user, ['keys' => ['p256dh' => $this->b64("\x05" . str_repeat('a', 64))]])->assertStatus(422);
        $this->submit($user, ['keys' => ['auth' => $this->b64(str_repeat('b', 15))]])->assertStatus(422);
        $this->assertSame(0, PushSubscription::where('user_id', $user->id)->count());
    }

    public function test_a_valid_subscription_is_stored_and_moves_to_the_next_user_on_the_same_endpoint(): void
    {
        $first = User::factory()->create(['status' => 'active']);
        $second = User::factory()->create(['status' => 'active']);

        $this->submit($first, ['scopes' => ['contact' => true, 'channel' => true]])->assertNoContent();
        $this->assertDatabaseHas('push_subscriptions', ['user_id' => $first->id, 'contact' => true, 'channel' => true]);

        $this->submit($second)->assertNoContent();

        $this->assertSame(1, PushSubscription::where('endpoint_hash', hash('sha256', 'https://fcm.googleapis.com/fcm/send/abc'))->count());
        $this->assertDatabaseHas('push_subscriptions', ['user_id' => $second->id, 'contact' => true, 'channel' => false]);
    }

    public function test_turning_both_scopes_off_deletes_the_row(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->submit($user)->assertNoContent();

        $this->submit($user, ['scopes' => ['contact' => false, 'channel' => false]])->assertNoContent();

        $this->assertSame(0, PushSubscription::where('user_id', $user->id)->count());
    }

    public function test_the_confirm_job_is_dispatched_only_when_asked_and_when_web_push_is_configured(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Queue::fake();
        config(['services.webpush.private' => null, 'services.webpush.public' => null, 'services.webpush.subject' => null]);

        $this->submit($user, ['confirm' => true])->assertNoContent();

        Queue::assertNothingPushed();

        config(['services.webpush.private' => 'k', 'services.webpush.public' => 'p', 'services.webpush.subject' => 'mailto:a@example.test']);

        $this->submit($user)->assertNoContent();

        Queue::assertNothingPushed();

        $this->submit($user, ['confirm' => true])->assertNoContent();

        $row = PushSubscription::where('user_id', $user->id)->first();
        Queue::assertPushed(SendChatPushJob::class, fn (SendChatPushJob $job) => $job->scope === 'confirm' && $job->subjectId === $row->id);
    }
}
