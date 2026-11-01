<?php

namespace Tests\Feature\Services;

use App\Services\Notify\Channels\SmsChannel;
use App\Services\Notify\Message;
use App\Services\Notify\NotifyRegistry;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class SmsChannelTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_text_returns_true_on_the_happy_path_with_the_log_provider(): void
    {
        config(['services.sms.provider' => 'log']);

        $this->assertTrue(app(SmsChannel::class)->text('09120000000', 'hello'));
    }

    public function test_text_returns_false_when_the_log_facade_throws_and_does_not_propagate(): void
    {
        config(['services.sms.provider' => 'log']);
        Log::shouldReceive('info')->andThrow(new \Exception('log unavailable'));
        Log::shouldReceive('error');

        $this->assertFalse(app(SmsChannel::class)->text('09120000000', 'hello'));
    }

    public function test_the_log_provider_makes_no_http_call_and_is_only_configured_when_chosen_explicitly(): void
    {
        Http::fake();
        config(['services.sms.provider' => 'log']);

        $this->assertTrue((new SmsChannel)->isConfigured());
        $this->assertTrue((new SmsChannel)->send(new Message('Subject line', ['body'], 'rule', 'Model', 'created'), '09120000000'));
        Http::assertNothingSent();
    }

    public function test_an_unconfigured_or_unknown_provider_falls_back_to_the_log_provider_and_reports_not_configured(): void
    {
        $registry = \App\Services\Notify\Channels\Sms\SmsProviderRegistry::class;
        Http::fake();

        config(['services.sms.provider' => 'kavenegar', 'services.kavenegar.key' => 'KEY']);
        $this->assertSame('kavenegar', $registry::active()->key());
        $this->assertTrue((new SmsChannel)->isConfigured());

        foreach ([['kavenegar', null], ['typo-provider', 'KEY']] as [$name, $key]) {
            config(['services.sms.provider' => $name, 'services.kavenegar.key' => $key]);

            $this->assertSame('log', $registry::active()->key(), $name);
            $this->assertFalse((new SmsChannel)->isConfigured(), $name);
            $this->assertTrue((new SmsChannel)->send(new Message('s', ['b'], 'rule', 'Model', 'created'), '09120000000'));
        }

        Http::assertNothingSent();

        $keys = array_map(fn ($class) => (new $class)->key(), $registry::PROVIDERS);
        $this->assertSame($keys, array_values(array_unique($keys)));
    }

    public function test_send_posts_to_kavenegar_with_the_key_in_the_path_and_strips_direction_marks(): void
    {
        config(['services.kavenegar.key' => 'SECRETKEY', 'services.kavenegar.sender' => '10004346']);
        Http::fake(['api.kavenegar.com/*' => Http::response(['return' => ['status' => 200, 'message' => 'تایید شد'], 'entries' => []])]);

        $this->assertTrue((new SmsChannel)->send(new Message("\u{202B}سلام\u{202C}", ['خط دوم'], 'rule', 'Model', 'created'), '09120000000'));

        Http::assertSent(fn($request) => $request->url() === 'https://api.kavenegar.com/v1/SECRETKEY/sms/send.json'
            && $request['receptor'] === '09120000000'
            && $request['sender'] === '10004346'
            && $request['message'] === "سلام\nخط دوم");
    }

    public function test_a_gateway_error_throws_its_code_and_message_without_the_key_and_text_reports_false(): void
    {
        config(['services.kavenegar.key' => 'SECRETKEY']);
        Http::fake(['api.kavenegar.com/*' => Http::response(['return' => ['status' => 418, 'message' => 'اعتبار کافی نیست']])]);

        try {
            (new SmsChannel)->send(new Message('s', ['b'], 'rule', 'Model', 'created'), '09120000000');
            $this->fail('expected a RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertSame('کاوه‌نگار 418: اعتبار کافی نیست', $e->getMessage());
        }

        $this->assertFalse((new SmsChannel)->text('09120000000', 'hello'));
    }

    public function test_a_single_send_to_a_number_the_gateway_blocks_reports_false_instead_of_a_false_success(): void
    {
        config(['services.sms.provider' => 'kavenegar', 'services.kavenegar.key' => 'SECRETKEY']);
        Http::fake(['api.kavenegar.com/*' => Http::response([
            'return' => ['status' => 200, 'message' => 'ok'],
            'entries' => [['receptor' => '09120000000', 'status' => 14]],
        ])]);

        $this->assertFalse((new SmsChannel)->text('09120000000', 'hello'));
    }

    public function test_a_connection_failure_never_leaks_the_key_into_the_error(): void
    {
        config(['services.kavenegar.key' => 'SECRETKEY']);
        Http::fake(fn() => throw new ConnectionException('cURL error 6 for https://api.kavenegar.com/v1/SECRETKEY/sms/send.json'));
        Log::shouldReceive('warning')->once()->withArgs(fn ($message, $context) => ! str_contains(json_encode($context), 'SECRETKEY')
            && str_contains(json_encode($context), 'cURL error 6'));

        try {
            (new SmsChannel)->send(new Message('s', ['b'], 'rule', 'Model', 'created'), '09120000000');
            $this->fail('expected a RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertStringNotContainsString('SECRETKEY', $e->getMessage());
        }
    }

    public function test_a_long_message_is_capped_at_the_configured_sms_size(): void
    {
        config(['services.sms.provider' => 'kavenegar', 'services.kavenegar.key' => 'SECRETKEY', 'notify.sms_max' => 20]);
        Http::fake(['api.kavenegar.com/*' => Http::response(['return' => ['status' => 200, 'message' => 'ok'], 'entries' => []])]);

        (new SmsChannel)->sendMany(new Message('s', [str_repeat('a', 100)], 'rule', 'Model', 'created'), ['09121111111']);

        Http::assertSent(fn($request) => mb_strlen($request['message']) <= 20);
    }

    public function test_send_many_makes_one_gateway_call_with_comma_joined_receptors_and_maps_entry_failures_by_the_last_ten_digits(): void
    {
        config(['services.sms.provider' => 'kavenegar', 'services.kavenegar.key' => 'SECRETKEY', 'services.kavenegar.sender' => '10004346']);
        Http::fake(['api.kavenegar.com/*' => Http::response([
            'return' => ['status' => 200, 'message' => 'تایید شد'],
            'entries' => [
                ['receptor' => '09121111111', 'status' => 1],
                ['receptor' => '989122222222', 'status' => 14],
                ['receptor' => '09123333333', 'status' => 100],
                ['receptor' => '09124444444', 'status' => 10],
            ],
        ])]);
        $numbers = ['09121111111', '09122222222', '09123333333', '09124444444', '09125555555'];

        $results = (new SmsChannel)->sendMany(new Message("\u{202B}سلام\u{202C}", ['خط دوم'], 'rule', 'Model', 'created'), $numbers);

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.kavenegar.com/v1/SECRETKEY/sms/send.json'
            && $request['receptor'] === implode(',', $numbers)
            && $request['message'] === "سلام\nخط دوم"
            && $request['sender'] === '10004346');
        $this->assertSame($numbers, array_keys($results));
        $this->assertSame([
            '09121111111' => null,
            '09122222222' => 'شمارهٔ گیرنده مسدود است.',
            '09123333333' => 'شمارهٔ گیرنده نامعتبر است.',
            '09124444444' => null,
            '09125555555' => null,
        ], $results);
    }

    public function test_send_many_gives_every_number_the_gateway_error_when_the_gateway_refuses_the_request(): void
    {
        config(['services.kavenegar.key' => 'SECRETKEY']);
        Http::fake(['api.kavenegar.com/*' => Http::response(['return' => ['status' => 418, 'message' => 'اعتبار کافی نیست']])]);
        $numbers = ['09121111111', '09122222222'];

        $results = (new \App\Services\Notify\Channels\Sms\Providers\KavenegarProvider)->sendMany($numbers, 'hello');

        $this->assertSame(array_fill_keys($numbers, 'کاوه‌نگار 418: اعتبار کافی نیست'), $results);
    }

    public function test_send_many_transport_failure_gives_every_number_a_fixed_message_without_the_key(): void
    {
        config(['services.kavenegar.key' => 'SECRETKEY']);
        Http::fake(fn () => throw new ConnectionException('cURL error 6 for https://api.kavenegar.com/v1/SECRETKEY/sms/send.json'));
        Log::shouldReceive('warning')->once()->withArgs(fn ($message, $context) => ! str_contains(json_encode($context), 'SECRETKEY'));
        $numbers = ['09121111111', '09122222222'];

        $results = (new \App\Services\Notify\Channels\Sms\Providers\KavenegarProvider)->sendMany($numbers, 'hello');

        $this->assertSame(array_fill_keys($numbers, 'اتصال به سرویس پیامک برقرار نشد.'), $results);
    }

    public function test_the_log_provider_send_many_delivers_nothing_and_reports_every_number_as_accepted(): void
    {
        Http::fake();
        config(['services.sms.provider' => 'log']);

        $results = (new SmsChannel)->sendMany(new Message('s', ['b'], 'rule', 'Model', 'created'), ['09121111111', '09122222222']);

        $this->assertSame(['09121111111' => null, '09122222222' => null], $results);
        Http::assertNothingSent();
    }

    public function test_mail_send_many_keeps_going_after_one_address_fails_and_reports_it_by_address(): void
    {
        Mail::fake();
        $message = new Message('s', ['b'], 'rule', 'Model', 'created');

        $results = (new \App\Services\Notify\Channels\MailChannel)->sendMany($message, ['a@example.com', 'b@example.com']);

        $this->assertSame(['a@example.com' => null, 'b@example.com' => null], $results);
        Mail::assertSent(\App\Mail\NotifyMail::class, 2);

        $mailer = Mockery::mock();
        $mailer->shouldReceive('send')->once();
        Mail::shouldReceive('to')->with('bad@example.com')->once()->andThrow(new \RuntimeException(str_repeat('é', 600)));
        Mail::shouldReceive('to')->with('good@example.com')->once()->andReturn($mailer);

        $results = (new \App\Services\Notify\Channels\MailChannel)->sendMany($message, ['bad@example.com', 'good@example.com']);

        $this->assertSame(__('resources/notify_rule/strings.batch.mail_failed'), $results['bad@example.com']);
        $this->assertNull($results['good@example.com']);
    }

    public function test_accepts_only_iranian_mobile_numbers(): void
    {
        $channel = new SmsChannel;

        foreach (['09120000000', '+989120000000', '9120000000'] as $valid) {
            $this->assertTrue($channel->accepts($valid), $valid);
        }

        foreach (['a@b.com', '0212000000', '091200000', '091200000001', ''] as $invalid) {
            $this->assertFalse($channel->accepts($invalid), $invalid);
        }
    }

    public function test_it_is_configured_only_when_a_gateway_key_exists_and_mail_always_is(): void
    {
        config(['services.kavenegar.key' => null]);
        $this->assertFalse((new SmsChannel)->isConfigured());

        config(['services.kavenegar.key' => 'KEY']);
        $this->assertTrue((new SmsChannel)->isConfigured());
        $this->assertTrue((new \App\Services\Notify\Channels\MailChannel)->isConfigured());
    }

    public function test_registry_exposes_the_channel_under_the_sms_key(): void
    {
        $this->assertInstanceOf(SmsChannel::class, NotifyRegistry::channel('sms'));
    }
}
