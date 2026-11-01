<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SendReminderSms;
use App\Models\Profile;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendReminderSmsTest extends TestCase
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

    private function reminderFor(?string $cellphone): Reminder
    {
        $user = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $user->id, 'cellphone' => $cellphone]);

        return Reminder::create([
            'user_id' => $user->id,
            'title' => 'جلسهٔ هفتگی',
            'due_at' => now(),
            'channels' => ['sms'],
        ]);
    }

    public function test_it_texts_the_owner_through_kavenegar_with_the_reminder_title(): void
    {
        config(['services.kavenegar.key' => 'SECRETKEY']);
        Http::fake(['api.kavenegar.com/*' => Http::response(['return' => ['status' => 200]])]);
        $reminder = $this->reminderFor('09120000000');

        app()->call([new SendReminderSms($reminder->id), 'handle']);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sms/send.json')
            && str_contains($request['message'], 'جلسهٔ هفتگی')
            && $request['receptor'] === '09120000000');
    }

    public function test_it_is_a_no_op_without_a_mobile_number_or_when_sms_is_switched_off(): void
    {
        config(['services.kavenegar.key' => 'SECRETKEY']);
        Http::fake();

        app()->call([new SendReminderSms($this->reminderFor(null)->id), 'handle']);
        Http::assertNothingSent();

        config(['app.off' => ['notify:sms']]);
        app()->call([new SendReminderSms($this->reminderFor('09120000000')->id), 'handle']);
        Http::assertNothingSent();
    }
}
