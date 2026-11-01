<?php

namespace Tests\Feature\Services;

use App\Models\Department;
use App\Models\NotifyRule;
use App\Models\Profile;
use App\Models\User;
use App\Services\Notify\RecipientResolver;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecipientResolverTest extends TestCase
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

    private function user(?string $cellphone, string $status = 'active'): User
    {
        $user = User::factory()->create(['status' => $status]);
        Profile::factory()->create(['user_id' => $user->id, 'cellphone' => $cellphone]);

        return $user;
    }

    private function rule(array $overrides): NotifyRule
    {
        return NotifyRule::create(array_merge([
            'name' => 'resolver test rule',
            'model' => \App\Models\Link::class,
            'events' => ['created'],
            'channels' => ['mail', 'sms'],
            'subject' => 's',
            'lines' => ['l'],
            'is_active' => true,
        ], $overrides));
    }

    public function test_the_same_mobile_written_two_ways_is_one_sms_and_keeps_the_user_link(): void
    {
        $user = $this->user('09120000000');
        $rule = $this->rule(['user_ids' => [$user->id], 'extra' => ['contacts' => ['+989120000000', '0912 000 0000']]]);

        $sms = (new RecipientResolver)->resolve($rule)['sms'];

        $this->assertSame(['09120000000' => $user->id], $sms);
    }

    public function test_a_malformed_profile_number_an_inactive_user_and_a_duplicate_email_are_handled(): void
    {
        $good = $this->user('09121111111');
        $malformed = $this->user('12-34');
        $inactive = $this->user('09122222222', 'inactive');
        $rule = $this->rule(['user_ids' => [$good->id, $malformed->id, $inactive->id], 'extra' => ['contacts' => [mb_strtoupper($good->email)]]]);

        $resolved = (new RecipientResolver)->resolve($rule);

        $this->assertSame([$good->id], array_values($resolved['sms']));
        $this->assertSame(1, count(array_filter(array_keys($resolved['mail']), fn ($e) => $e === mb_strtolower($good->email))));
        $this->assertArrayNotHasKey(mb_strtolower($inactive->email), $resolved['mail']);
        $this->assertArrayHasKey(mb_strtolower($malformed->email), $resolved['mail']);
    }

    public function test_audience_all_reaches_every_active_user_and_merges_with_hand_picked_without_duplicates(): void
    {
        $a = $this->user('09123000001');
        $b = $this->user('09123000002');
        $inactive = $this->user('09123000003', 'inactive');
        $guest = $this->user('09123000004');
        $guest->update(['type' => \App\Filament\Resources\UserResource\Enums\UserType::Guest->value]);
        $rule = $this->rule(['user_ids' => [$a->id], 'extra' => ['audience' => 'all']]);

        [$resolved, $skipped] = (new RecipientResolver)->resolveWithSkips($rule);

        $this->assertArrayNotHasKey('09123000004', $resolved['sms']);

        $this->assertSame($a->id, $resolved['sms']['09123000001']);
        $this->assertSame($b->id, $resolved['sms']['09123000002']);
        $this->assertArrayNotHasKey('09123000003', $resolved['sms']);
        $this->assertArrayHasKey(mb_strtolower($b->email), $resolved['mail']);
        $this->assertNotContains($inactive->id, array_column($skipped['sms'], 'user_id'));
    }

    public function test_audience_departments_reaches_only_the_chosen_departments_plus_hand_picked(): void
    {
        $dept = Department::factory()->create();
        $other = Department::factory()->create();
        $member = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $member->id, 'department_id' => $dept->code, 'cellphone' => '09124000001']);
        $outsider = User::factory()->create(['status' => 'active']);
        Profile::factory()->create(['user_id' => $outsider->id, 'department_id' => $other->code, 'cellphone' => '09124000002']);
        $picked = $this->user('09124000003');
        $rule = $this->rule(['user_ids' => [$picked->id], 'extra' => ['audience' => 'departments', 'departments' => [$dept->code]]]);

        $resolved = (new RecipientResolver)->resolve($rule);

        $this->assertEquals(['09124000001' => $member->id, '09124000003' => $picked->id], $resolved['sms']);
        $this->assertEqualsCanonicalizing([$member->id, $picked->id], array_values($resolved['mail']));
    }

    public function test_unreachable_people_are_reported_with_the_reason_per_channel(): void
    {
        $good = $this->user('09125000001');
        $noMobile = $this->user(null);
        $badMobile = $this->user('12-34');
        $inactive = $this->user('09125000002', 'inactive');
        $rule = $this->rule(['user_ids' => [$good->id, $noMobile->id, $badMobile->id, $inactive->id]]);

        [$resolved, $skipped] = (new RecipientResolver)->resolveWithSkips($rule);

        $reasons = collect($skipped['sms'])->keyBy('user_id');
        $this->assertSame(__('resources/notify_rule/strings.batch.skip_missing', ['channel' => 'پیامک']), $reasons[$noMobile->id]['reason']);
        $this->assertSame(__('resources/notify_rule/strings.batch.skip_invalid', ['channel' => 'پیامک']), $reasons[$badMobile->id]['reason']);
        $this->assertSame(__('resources/notify_rule/strings.batch.skip_inactive'), $reasons[$inactive->id]['reason']);
        $this->assertSame($noMobile->name, $reasons[$noMobile->id]['name']);
        $this->assertCount(3, $skipped['sms']);
        $this->assertEqualsCanonicalizing([$good->id, $noMobile->id, $badMobile->id], array_values($resolved['mail']));
        $this->assertSame([$inactive->id], array_column($skipped['mail'], 'user_id'));
    }

    public function test_resolve_returns_exactly_the_resolved_half_of_resolve_with_skips(): void
    {
        $user = $this->user('09126000001');
        $rule = $this->rule(['user_ids' => [$user->id], 'extra' => ['contacts' => ['ext@example.com']]]);
        $resolver = new RecipientResolver;

        $this->assertSame($resolver->resolveWithSkips($rule)[0], $resolver->resolve($rule));
        $this->assertNull($resolver->resolve($rule)['mail']['ext@example.com']);
    }

    public function test_a_channel_the_tenant_switched_off_is_skipped_and_the_other_still_resolves(): void
    {
        $user = $this->user('09120000000');
        $rule = $this->rule(['user_ids' => [$user->id]]);

        config(['app.off' => ['notify:sms']]);

        $resolved = (new RecipientResolver)->resolve($rule);

        $this->assertSame(['mail'], array_keys($resolved));
    }
}
