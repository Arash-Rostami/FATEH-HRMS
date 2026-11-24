<?php

namespace Tests\Feature\Services\Messaging;

use App\Models\Channel;
use App\Models\ChannelMessage;
use App\Models\Message;
use App\Services\Messaging\MessageEnhancer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpClientFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MessageEnhancerTest extends TestCase
{
    private const USER = 987001;

    private array $channel = ['config' => 'ai.channel.enhance', 'client' => 'channel', 'stem' => 'channel', 'quota' => ChannelMessage::class];

    private array $contact = ['config' => 'ai.contact', 'client' => 'contact', 'stem' => 'contact', 'quota' => Message::class];

    protected function setUp(): void
    {
        parent::setUp();
        config(['ai.gateway.key' => 'test-key', 'ai.gateway.url' => 'https://gateway.test/v1', 'ai.gateway.model' => 'Gemini-3-Flash-Preview', 'ai.channel.enhance.cooldown' => 0, 'ai.contact.cooldown' => 0]);
        Http::swap(new HttpClientFactory);
        $this->forgetKeys();
    }

    protected function tearDown(): void
    {
        $this->forgetKeys();
        parent::tearDown();
    }

    private function forgetKeys(): void
    {
        foreach (['channel', 'contact'] as $stem) {
            Cache::forget("{$stem}:ai:busy:" . self::USER);
            Cache::forget("{$stem}:ai:cooldown:" . self::USER);
            Cache::forget("{$stem}:ai:down");
        }

        Cache::forget(ChannelMessage::quotaKey(self::USER));
        Cache::forget(Message::quotaKey(self::USER));
    }

    private function reply(mixed $answer, string $finish = 'stop'): array
    {
        return [
            'choices' => [['finish_reason' => $finish, 'message' => ['content' => json_encode(['answer' => $answer], JSON_UNESCAPED_UNICODE)]]],
            'usage' => ['total_tokens' => 42],
        ];
    }

    private function enhance(array $module, string $text = 'salam khoobi'): array
    {
        return app(MessageEnhancer::class)->enhance($module, 7, self::USER, $text);
    }

    public function test_it_returns_the_rewritten_text_and_charges_one_unit_to_the_module_counter(): void
    {
        Http::fake(['*' => Http::response($this->reply('سلام، امیدوارم حالتون خوب باشه'))]);

        $result = $this->enhance($this->channel);

        $this->assertSame('سلام، امیدوارم حالتون خوب باشه', $result['answer']);
        $this->assertNull($result['error']);
        $this->assertFalse($result['soft']);
        $this->assertSame(config('ai.channel.enhance.applied_note'), $result['note']);
        $this->assertStringContainsString('سهمیهٔ امروز', $result['meter']);
        $this->assertSame(1, ChannelMessage::quotaUsed(self::USER));
        $this->assertSame(0, Message::quotaUsed(self::USER));
        Http::assertSent(fn ($request) => $request['response_format']['json_schema']['name'] === 'channel'
            && $request['messages'][0]['content'] === config('ai.messaging.rules')
            && str_contains($request['messages'][1]['content'], 'salam khoobi'));
    }

    public function test_the_contact_descriptor_uses_its_own_counter_and_config(): void
    {
        Http::fake(['*' => Http::response($this->reply('ok'))]);

        $result = $this->enhance($this->contact);

        $this->assertSame('ok', $result['answer']);
        $this->assertSame(1, Message::quotaUsed(self::USER));
        $this->assertSame(0, ChannelMessage::quotaUsed(self::USER));
        Http::assertSent(fn ($request) => $request['response_format']['json_schema']['name'] === 'contact');
    }

    public function test_the_channel_enhance_counter_is_separate_from_the_summary_counter(): void
    {
        $this->assertNotSame(Channel::quotaKey(self::USER), ChannelMessage::quotaKey(self::USER));
        $this->assertSame((int) config('ai.channel.enhance.daily_cap'), ChannelMessage::quotaCap());
    }

    public function test_hard_gateway_refusals_map_to_their_codes_and_refund_the_unit(): void
    {
        Http::fake(['*' => Http::sequence()
            ->push($this->reply('x', 'content_filter'))
            ->push($this->reply('x', 'length'))]);
        $this->assertSame('blocked', $this->enhance($this->channel)['error']);
        $this->assertSame(0, ChannelMessage::quotaUsed(self::USER));

        $this->assertSame('too_large', $this->enhance($this->channel)['error']);
        $this->assertSame(0, ChannelMessage::quotaUsed(self::USER));
    }

    public function test_an_unusable_answer_is_retried_once_then_reported_as_empty_and_refunded(): void
    {
        Http::fake(['*' => Http::response($this->reply('   '))]);

        $result = $this->enhance($this->channel);

        $this->assertSame('empty', $result['error']);
        $this->assertSame('', $result['answer']);
        $this->assertSame(config('ai.channel.enhance.errors.empty'), $result['message']);
        Http::assertSentCount(2);
        $this->assertSame(0, ChannelMessage::quotaUsed(self::USER));
    }

    public function test_a_server_error_trips_the_breaker_and_refunds_the_unit(): void
    {
        Http::fake(['*' => Http::response('', 503)]);

        $result = $this->enhance($this->channel);

        $this->assertSame('unreachable', $result['error']);
        $this->assertTrue(Cache::has('channel:ai:down'));
        $this->assertSame(0, ChannelMessage::quotaUsed(self::USER));

        Http::fake();
        $second = $this->enhance($this->channel);

        $this->assertSame('down', $second['error']);
        Http::assertNothingSent();
    }

    public function test_a_dead_connection_is_unreachable_and_trips_the_breaker(): void
    {
        Http::fake(fn () => throw new ConnectionException('down'));

        $this->assertSame('unreachable', $this->enhance($this->contact)['error']);
        $this->assertTrue(Cache::has('contact:ai:down'));
        $this->assertSame(0, Message::quotaUsed(self::USER));
    }

    public function test_a_spent_quota_returns_the_soft_quota_error_without_calling_the_gateway(): void
    {
        Http::fake();
        Cache::put(ChannelMessage::quotaKey(self::USER), ChannelMessage::quotaCap(), now()->endOfDay());

        $result = $this->enhance($this->channel);

        $this->assertSame('quota', $result['error']);
        $this->assertTrue($result['soft']);
        $this->assertSame(config('ai.channel.enhance.errors.quota'), $result['message']);
        $this->assertSame(ChannelMessage::quotaCap(), ChannelMessage::quotaUsed(self::USER));
        Http::assertNothingSent();
    }

    public function test_a_held_lock_or_a_fresh_cooldown_answers_busy_without_calling_the_gateway(): void
    {
        Http::fake();
        $lock = Cache::lock('channel:ai:busy:' . self::USER, 60);
        $this->assertTrue($lock->get());

        $this->assertSame('busy', $this->enhance($this->channel)['error']);

        $lock->release();
        Cache::put('channel:ai:cooldown:' . self::USER, 1, 60);

        $result = $this->enhance($this->channel);

        $this->assertSame('busy', $result['error']);
        $this->assertTrue($result['soft']);
        Http::assertNothingSent();
        $this->assertSame(0, ChannelMessage::quotaUsed(self::USER));
    }

    public function test_the_cooldown_is_armed_only_after_a_gateway_call(): void
    {
        config(['ai.channel.enhance.cooldown' => 60]);
        Http::fake(['*' => Http::response($this->reply('ok'))]);

        $this->enhance($this->channel);

        $this->assertTrue(Cache::has('channel:ai:cooldown:' . self::USER));

        Cache::forget('channel:ai:cooldown:' . self::USER);
        Cache::put(ChannelMessage::quotaKey(self::USER), ChannelMessage::quotaCap(), now()->endOfDay());
        $this->enhance($this->channel);

        $this->assertFalse(Cache::has('channel:ai:cooldown:' . self::USER));
    }

    public function test_the_answer_loses_invisible_and_bidi_control_characters_but_keeps_digits_and_links(): void
    {
        $dirty = "\u{200B}زنگ\u{202E} بزن\u{200F} 0912-345-6789 https://example.com/a?b=1\u{FEFF}\x07";
        Http::fake(['*' => Http::response($this->reply($dirty))]);

        $this->assertSame('زنگ بزن 0912-345-6789 https://example.com/a?b=1', $this->enhance($this->channel)['answer']);
    }

    public function test_an_overlong_answer_is_cut_at_the_cap_and_flagged_in_the_note(): void
    {
        config(['ai.channel.enhance.answer_max' => 10]);
        Http::fake(['*' => Http::response($this->reply(str_repeat('الف ', 20)))]);

        $result = $this->enhance($this->channel);

        $this->assertLessThanOrEqual(10, mb_strlen($result['answer']));
        $this->assertTrue(str_ends_with($result['answer'], '…'));
        $this->assertSame(trim(config('ai.channel.enhance.cut_note') . ' ' . config('ai.channel.enhance.applied_note')), $result['note']);
    }

    public function test_the_pre_checks_fail_helper_returns_the_soft_error_shape_with_the_meter(): void
    {
        $result = app(MessageEnhancer::class)->fail($this->channel, 7, self::USER, 'expired');

        $this->assertSame('expired', $result['error']);
        $this->assertSame('', $result['answer']);
        $this->assertTrue($result['soft']);
        $this->assertSame(config('ai.channel.enhance.errors.expired'), $result['message']);
        $this->assertNull($result['note']);
        $this->assertStringContainsString('سهمیهٔ امروز', $result['meter']);
    }
}
