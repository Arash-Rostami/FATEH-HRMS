<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SendChatPushJob;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\SubscriptionInterface;
use Minishlink\WebPush\WebPush;
use App\Models\Channel;
use App\Models\ChannelMessage;
use App\Models\Message;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendChatPushJobTest extends TestCase
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

    private function subscribe(User $user, string $scope): PushSubscription
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/' . $user->id . '-' . uniqid();

        return PushSubscription::create([
            'user_id' => $user->id,
            'endpoint_hash' => hash('sha256', $endpoint),
            'endpoint' => $endpoint,
            'p256dh' => 'p256dh',
            'auth' => 'auth',
            'contact' => $scope === 'contact',
            'channel' => $scope === 'channel',
        ]);
    }

    private function fakeSender(): WebPush
    {
        $fake = new class extends WebPush {
            public array $sent = [];

            public array $expired = [];

            public array $exploding = [];

            public bool $transportDown = false;

            public function __construct() {}

            public function sendOneNotification(SubscriptionInterface $subscription, ?string $payload = null, array $options = [], array $auth = []): MessageSentReport
            {
                $endpoint = $subscription->getEndpoint();
                $request = new Request('POST', $endpoint);

                if (in_array($endpoint, $this->exploding, true)) {
                    throw new \RuntimeException('encryption failed');
                }

                $this->sent[] = ['endpoint' => $endpoint, 'payload' => json_decode($payload, true)];

                if ($this->transportDown) {
                    return new MessageSentReport($request, null, false, 'timeout');
                }

                if (in_array($endpoint, $this->expired, true)) {
                    return new MessageSentReport($request, new Response(410), false, 'Gone');
                }

                return new MessageSentReport($request, new Response(201));
            }
        };

        $this->app->bind(WebPush::class, fn () => $fake);

        return $fake;
    }

    private function member(Channel $channel, User $user, ?ChannelMessage $readUpTo = null): void
    {
        DB::table('channel_members')->updateOrInsert(
            ['channel_id' => $channel->id, 'user_id' => $user->id],
            ['joined_at' => now(), 'entered_at' => now(), 'last_read_message_id' => $readUpTo?->id, 'created_at' => now(), 'updated_at' => now()],
        );
    }

    public function test_fire_dispatches_nothing_without_a_private_key_and_one_job_with_it(): void
    {
        Queue::fake();

        config(['services.webpush.private' => null]);
        SendChatPushJob::fire('contact', 1, 2);
        Queue::assertNothingPushed();

        config(['services.webpush.private' => 'test-key', 'services.webpush.public' => 'test-public', 'services.webpush.subject' => 'mailto:test@example.test']);
        SendChatPushJob::fire('contact', 1, 2);
        Queue::assertPushed(SendChatPushJob::class, 1);
    }

    public function test_a_new_dm_and_a_new_channel_message_each_dispatch_one_push_job(): void
    {
        Queue::fake();
        config(['services.webpush.private' => 'test-key', 'services.webpush.public' => 'test-public', 'services.webpush.subject' => 'mailto:test@example.test']);
        $sender = User::factory()->create(['status' => 'active']);
        $recipient = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->create(['owner_id' => $sender->id]);

        Message::factory()->create(['sender_id' => $sender->id, 'recipient_id' => $recipient->id, 'read_at' => null]);
        ChannelMessage::factory()->inChannel($channel)->fromUser($sender)->create();

        Queue::assertPushed(SendChatPushJob::class, fn (SendChatPushJob $job) => $job->scope === 'contact' && $job->subjectId === $recipient->id && $job->senderId === $sender->id);
        Queue::assertPushed(SendChatPushJob::class, fn (SendChatPushJob $job) => $job->scope === 'channel' && $job->subjectId === $channel->id && $job->senderId === $sender->id);
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

    public function test_job_is_unique_until_processing_with_a_scope_subject_sender_id(): void
    {
        $job = new SendChatPushJob('channel', 4, 9);

        $this->assertInstanceOf(ShouldBeUniqueUntilProcessing::class, $job);
        $this->assertSame('channel|4|9|0', $job->uniqueId());
        $this->assertSame(1, $job->maxExceptions);
        $this->assertSame(60, $job->uniqueFor);
    }

    public function test_pushes_run_one_at_a_time_and_a_waiting_job_survives_being_released(): void
    {
        $job = new SendChatPushJob('contact', 1, 2);
        $guard = $job->middleware()[0];

        $this->assertInstanceOf(WithoutOverlapping::class, $guard);
        $this->assertSame(2, $guard->releaseAfter);
        $this->assertGreaterThan($job->timeout, $guard->expiresAfter);
        $this->assertGreaterThan(now()->getTimestamp(), $job->retryUntil()->getTimestamp());
    }

    public function test_contact_pushes_only_to_the_recipient_and_never_carries_message_text(): void
    {
        $sender = User::factory()->create(['status' => 'active']);
        $recipient = User::factory()->create(['status' => 'active']);
        $recipientSub = $this->subscribe($recipient, 'contact');
        $this->subscribe($sender, 'contact');
        Message::factory()->create(['sender_id' => $sender->id, 'recipient_id' => $recipient->id, 'read_at' => null, 'body' => 'سری محرمانه']);
        $fake = $this->fakeSender();

        (new SendChatPushJob('contact', $recipient->id, $sender->id))->handle();

        $this->assertSame([$recipientSub->endpoint], array_column($fake->sent, 'endpoint'));
        $payload = $fake->sent[0]['payload'];
        $this->assertSame('chat', $payload['type']);
        $this->assertSame('contact-' . $sender->id, $payload['tag']);
        $this->assertSame('contact', $payload['scope']);
        $this->assertIsInt($payload['badge']);
        $this->assertSame(1, $payload['badge']);
        $this->assertSame($recipientSub->endpoint, $payload['endpoint']);
        $this->assertStringContainsString($sender->name, $payload['title']);
        $this->assertStringNotContainsString('سری محرمانه', json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    public function test_contact_with_only_read_messages_sends_nothing(): void
    {
        $sender = User::factory()->create(['status' => 'active']);
        $recipient = User::factory()->create(['status' => 'active']);
        $this->subscribe($recipient, 'contact');
        Message::factory()->create(['sender_id' => $sender->id, 'recipient_id' => $recipient->id, 'read_at' => now()]);
        $fake = $this->fakeSender();

        (new SendChatPushJob('contact', $recipient->id, $sender->id))->handle();

        $this->assertSame([], $fake->sent);
    }

    public function test_a_subscription_without_the_contact_flag_is_not_used_for_a_contact_push(): void
    {
        $sender = User::factory()->create(['status' => 'active']);
        $recipient = User::factory()->create(['status' => 'active']);
        $this->subscribe($recipient, 'channel');
        Message::factory()->create(['sender_id' => $sender->id, 'recipient_id' => $recipient->id, 'read_at' => null]);
        $fake = $this->fakeSender();

        (new SendChatPushJob('contact', $recipient->id, $sender->id))->handle();

        $this->assertSame([], $fake->sent);
    }

    public function test_confirm_sends_one_silent_confirmation_to_that_subscription_only(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $target = $this->subscribe($user, 'contact');
        $this->subscribe($user, 'contact');
        $fake = $this->fakeSender();

        (new SendChatPushJob('confirm', $target->id, 0))->handle();

        $this->assertSame([$target->endpoint], array_column($fake->sent, 'endpoint'));
        $this->assertSame('confirm', $fake->sent[0]['payload']['type']);
        $this->assertArrayNotHasKey('badge', $fake->sent[0]['payload']);
        $this->assertSame($target->endpoint, $fake->sent[0]['payload']['endpoint']);
    }

    public function test_a_gone_endpoint_is_deleted_and_a_throwing_one_does_not_stop_the_rest(): void
    {
        $sender = User::factory()->create(['status' => 'active']);
        [$a, $b, $c] = User::factory()->count(3)->create(['status' => 'active'])->all();
        $channel = Channel::factory()->create(['owner_id' => $sender->id]);
        $this->member($channel, $sender);
        ChannelMessage::factory()->inChannel($channel)->fromUser($sender)->create();
        foreach ([$a, $b, $c] as $user) {
            $this->member($channel, $user);
        }
        $subA = $this->subscribe($a, 'channel');
        $subB = $this->subscribe($b, 'channel');
        $subC = $this->subscribe($c, 'channel');
        $fake = $this->fakeSender();
        $fake->expired = [$subA->endpoint];
        $fake->exploding = [$subB->endpoint];

        (new SendChatPushJob('channel', $channel->id, $sender->id))->handle();

        $this->assertEqualsCanonicalizing([$subA->endpoint, $subC->endpoint], array_column($fake->sent, 'endpoint'));
        $this->assertNull(PushSubscription::find($subA->id));
        $this->assertNotNull(PushSubscription::find($subB->id));
        $this->assertNotNull(PushSubscription::find($subC->id));
    }

    public function test_a_down_push_host_is_tried_once_then_skipped_for_the_rest_of_the_run(): void
    {
        Cache::forget('push-down:fcm.googleapis.com');
        Queue::fake();
        $sender = User::factory()->create(['status' => 'active']);
        [$a, $b] = User::factory()->count(2)->create(['status' => 'active'])->all();
        $channel = Channel::factory()->create(['owner_id' => $sender->id]);
        $this->member($channel, $sender);
        ChannelMessage::factory()->inChannel($channel)->fromUser($sender)->create();
        $this->member($channel, $a);
        $this->member($channel, $b);
        $this->subscribe($a, 'channel');
        $this->subscribe($b, 'channel');
        $fake = $this->fakeSender();
        $fake->transportDown = true;

        (new SendChatPushJob('channel', $channel->id, $sender->id))->handle();

        $this->assertCount(1, $fake->sent);
        $this->assertTrue(Cache::has('push-down:fcm.googleapis.com'));
        Queue::assertPushed(SendChatPushJob::class, fn (SendChatPushJob $job) => $job->round === 1 && count($job->only) === 2);
        Cache::forget('push-down:fcm.googleapis.com');
    }

    public function test_channel_pushes_only_to_members_with_unread_and_never_to_the_sender(): void
    {
        $sender = User::factory()->create(['status' => 'active']);
        $unread = User::factory()->create(['status' => 'active']);
        $caughtUp = User::factory()->create(['status' => 'active']);
        $channel = Channel::factory()->create(['owner_id' => $sender->id]);
        $this->member($channel, $sender);
        $message = ChannelMessage::factory()->inChannel($channel)->fromUser($sender)->create(['body' => 'متن محرمانه']);
        $this->member($channel, $unread);
        $this->member($channel, $caughtUp, $message);
        $this->subscribe($sender, 'channel');
        $unreadSub = $this->subscribe($unread, 'channel');
        $this->subscribe($caughtUp, 'channel');
        $fake = $this->fakeSender();

        (new SendChatPushJob('channel', $channel->id, $sender->id))->handle();

        $this->assertSame([$unreadSub->endpoint], array_column($fake->sent, 'endpoint'));
        $payload = $fake->sent[0]['payload'];
        $this->assertSame('channel-' . $channel->id, $payload['tag']);
        $this->assertStringContainsString($channel->name, $payload['title']);
        $this->assertStringNotContainsString('متن محرمانه', json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
}
