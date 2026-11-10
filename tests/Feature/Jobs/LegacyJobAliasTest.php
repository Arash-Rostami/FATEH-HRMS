<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ReconcileEdgeJob;
use App\Jobs\ReconcileNudgeJob;
use App\Jobs\ReconcileReminderDueJob;
use App\Jobs\SendNotifyChunkJob;
use App\Jobs\SendNotifyRuleJob;
use App\Jobs\SendReminderSmsJob;
use App\Services\Notify\Message;
use ReflectionProperty;
use Tests\TestCase;

class LegacyJobAliasTest extends TestCase
{
    private const PAYLOADS = [
        'ReconcileEdge' => 'TzoyMjoiQXBwXEpvYnNcUmVjb25jaWxlRWRnZSI6Mzp7czo3OiJydWxlS2V5IjtzOjE6ImsiO3M6MTI6InN1YmplY3RDbGFzcyI7czoxNToiQXBwXE1vZGVsc1xVc2VyIjtzOjk6InN1YmplY3RJZCI7aTowO30=',
        'ReconcileNudge' => 'TzoyMzoiQXBwXEpvYnNcUmVjb25jaWxlTnVkZ2UiOjM6e3M6NzoicnVsZUtleSI7czoxOiJrIjtzOjEyOiJzdWJqZWN0Q2xhc3MiO3M6MTU6IkFwcFxNb2RlbHNcVXNlciI7czo5OiJzdWJqZWN0SWQiO2k6MDt9',
        'ReconcileReminderDue' => 'TzoyOToiQXBwXEpvYnNcUmVjb25jaWxlUmVtaW5kZXJEdWUiOjE1OntzOjU6InRyaWVzIjtpOjM7czo3OiJiYWNrb2ZmIjthOjI6e2k6MDtpOjEwO2k6MTtpOjMwO31zOjEwOiJyZW1pbmRlcklkIjtpOjA7czozOiJqb2IiO047czoxMDoiY29ubmVjdGlvbiI7TjtzOjU6InF1ZXVlIjtOO3M6MTI6Im1lc3NhZ2VHcm91cCI7TjtzOjEyOiJkZWR1cGxpY2F0b3IiO047czo1OiJkZWxheSI7TjtzOjExOiJhZnRlckNvbW1pdCI7TjtzOjEwOiJtaWRkbGV3YXJlIjthOjA6e31zOjc6ImNoYWluZWQiO2E6MDp7fXM6MTU6ImNoYWluQ29ubmVjdGlvbiI7TjtzOjEwOiJjaGFpblF1ZXVlIjtOO3M6MTk6ImNoYWluQ2F0Y2hDYWxsYmFja3MiO047fQ==',
        'SendNotifyChunk' => 'TzoyNDoiQXBwXEpvYnNcU2VuZE5vdGlmeUNodW5rIjoyMzp7czo1OiJ0cmllcyI7aToxO3M6NzoidGltZW91dCI7aTo2MDtzOjEzOiJmYWlsT25UaW1lb3V0IjtiOjE7czo2OiJydWxlSWQiO2k6MDtzOjU6InJ1bklkIjtzOjM6InJ1biI7czoxMDoiY2hhbm5lbEtleSI7czo0OiJtYWlsIjtzOjc6Im1lc3NhZ2UiO086Mjc6IkFwcFxTZXJ2aWNlc1xOb3RpZnlcTWVzc2FnZSI6OTp7czo3OiJzdWJqZWN0IjtzOjE6InMiO3M6NToibGluZXMiO2E6MTp7aTowO3M6MToibCI7fXM6ODoicnVsZU5hbWUiO3M6MToiciI7czoxMDoibW9kZWxMYWJlbCI7czoxOiJtIjtzOjU6ImV2ZW50IjtzOjE6ImUiO3M6ODoidGVtcGxhdGUiO3M6NToicGxhaW4iO3M6MzoidXJsIjtOO3M6NjoiYWN0aW9uIjtOO3M6MTE6ImF0dGFjaG1lbnRzIjthOjA6e319czo5OiJhZGRyZXNzZXMiO2E6MTp7aTowO3M6NToiYUBiLmMiO31zOjg6ImRldGFpbGVkIjtiOjA7czo1OiJldmVudCI7czo3OiJjcmVhdGVkIjtzOjg6InJlY29yZElkIjtzOjE6IjAiO3M6Mzoiam9iIjtOO3M6MTA6ImNvbm5lY3Rpb24iO047czo1OiJxdWV1ZSI7TjtzOjEyOiJtZXNzYWdlR3JvdXAiO047czoxMjoiZGVkdXBsaWNhdG9yIjtOO3M6NToiZGVsYXkiO047czoxMToiYWZ0ZXJDb21taXQiO047czoxMDoibWlkZGxld2FyZSI7YTowOnt9czo3OiJjaGFpbmVkIjthOjA6e31zOjE1OiJjaGFpbkNvbm5lY3Rpb24iO047czoxMDoiY2hhaW5RdWV1ZSI7TjtzOjE5OiJjaGFpbkNhdGNoQ2FsbGJhY2tzIjtOO30=',
        'SendNotifyRule' => 'TzoyMzoiQXBwXEpvYnNcU2VuZE5vdGlmeVJ1bGUiOjE5OntzOjU6InRyaWVzIjtpOjE7czo3OiJ0aW1lb3V0IjtpOjYwO3M6MTM6ImZhaWxPblRpbWVvdXQiO2I6MTtzOjU6InJ1bklkIjtzOjI2OiIwMU03MlFOR0MzQUREOVBZVEFHOTQ0TjVXSCI7czozMToiAEFwcFxKb2JzXFNlbmROb3RpZnlSdWxlAHJlY29yZCI7TjtzOjY6InJ1bGVJZCI7aTowO3M6NzoicGF5bG9hZCI7YToxOntzOjE6IngiO2k6MTt9czozOiJqb2IiO047czoxMDoiY29ubmVjdGlvbiI7TjtzOjU6InF1ZXVlIjtOO3M6MTI6Im1lc3NhZ2VHcm91cCI7TjtzOjEyOiJkZWR1cGxpY2F0b3IiO047czo1OiJkZWxheSI7TjtzOjExOiJhZnRlckNvbW1pdCI7TjtzOjEwOiJtaWRkbGV3YXJlIjthOjA6e31zOjc6ImNoYWluZWQiO2E6MDp7fXM6MTU6ImNoYWluQ29ubmVjdGlvbiI7TjtzOjEwOiJjaGFpblF1ZXVlIjtOO3M6MTk6ImNoYWluQ2F0Y2hDYWxsYmFja3MiO047fQ==',
        'SendReminderSms' => 'TzoyNDoiQXBwXEpvYnNcU2VuZFJlbWluZGVyU21zIjoxNDp7czo1OiJ0cmllcyI7aToxO3M6MTA6InJlbWluZGVySWQiO2k6MDtzOjM6ImpvYiI7TjtzOjEwOiJjb25uZWN0aW9uIjtOO3M6NToicXVldWUiO047czoxMjoibWVzc2FnZUdyb3VwIjtOO3M6MTI6ImRlZHVwbGljYXRvciI7TjtzOjU6ImRlbGF5IjtOO3M6MTE6ImFmdGVyQ29tbWl0IjtOO3M6MTA6Im1pZGRsZXdhcmUiO2E6MDp7fXM6NzoiY2hhaW5lZCI7YTowOnt9czoxNToiY2hhaW5Db25uZWN0aW9uIjtOO3M6MTA6ImNoYWluUXVldWUiO047czoxOToiY2hhaW5DYXRjaENhbGxiYWNrcyI7Tjt9',
    ];

    private function legacy(string $name): object
    {
        return unserialize(base64_decode(self::PAYLOADS[$name]));
    }

    public function test_old_edge_payload_resolves_to_new_class(): void
    {
        $job = $this->legacy('ReconcileEdge');

        $this->assertInstanceOf(ReconcileEdgeJob::class, $job);
        $this->assertSame(['k', 'App\Models\User', 0], [$job->ruleKey, $job->subjectClass, $job->subjectId]);
    }

    public function test_old_nudge_payload_resolves_to_new_class(): void
    {
        $job = $this->legacy('ReconcileNudge');

        $this->assertInstanceOf(ReconcileNudgeJob::class, $job);
        $this->assertSame(['k', 'App\Models\User', 0], [$job->ruleKey, $job->subjectClass, $job->subjectId]);
    }

    public function test_old_reminder_due_payload_resolves_to_new_class(): void
    {
        $job = $this->legacy('ReconcileReminderDue');

        $this->assertInstanceOf(ReconcileReminderDueJob::class, $job);
        $this->assertSame(0, $job->reminderId);
    }

    public function test_old_notify_chunk_payload_resolves_to_new_class(): void
    {
        $job = $this->legacy('SendNotifyChunk');

        $this->assertInstanceOf(SendNotifyChunkJob::class, $job);
        $this->assertSame([0, 'run', 'mail', ['a@b.c'], false, 'created', '0'], [$job->ruleId, $job->runId, $job->channelKey, $job->addresses, $job->detailed, $job->event, $job->recordId]);
        $this->assertInstanceOf(Message::class, $job->message);
        $this->assertSame('s', $job->message->subject);
    }

    public function test_old_notify_rule_payload_resolves_to_new_class(): void
    {
        $job = $this->legacy('SendNotifyRule');

        $this->assertInstanceOf(SendNotifyRuleJob::class, $job);
        $this->assertSame([0, ['x' => 1]], [$job->ruleId, $job->payload]);
        $this->assertSame(26, strlen($job->runId));
        $this->assertNull((new ReflectionProperty(SendNotifyRuleJob::class, 'record'))->getValue($job));
    }

    public function test_old_reminder_sms_payload_resolves_to_new_class(): void
    {
        $job = $this->legacy('SendReminderSms');

        $this->assertInstanceOf(SendReminderSmsJob::class, $job);
        $this->assertSame(0, $job->reminderId);
    }

    public function test_every_old_name_still_resolves(): void
    {
        $classes = [
            ReconcileEdgeJob::class,
            ReconcileNudgeJob::class,
            ReconcileReminderDueJob::class,
            SendNotifyChunkJob::class,
            SendNotifyRuleJob::class,
            SendReminderSmsJob::class,
        ];

        foreach ($classes as $class) {
            $this->assertTrue(class_exists(substr($class, 0, -3)));
        }
    }
}
