<?php

namespace Tests\Feature\Services\Ticket;

use App\Models\Department;
use App\Models\Reply;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use App\Services\Ticket\TicketInsightsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class TicketInsightsServiceTest extends TestCase
{
    private const GHOST = 999999999;

    private User $requester;
    private string $dept;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        app()->setLocale('fa');
        Cache::flush();
        $this->requester = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->dept = 'T' . Str::upper(Str::random(4));
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Cache::flush();
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

    private function service(): TicketInsightsService
    {
        return app(TicketInsightsService::class);
    }

    private function ticket(string $type = 'support', string $area = 'vpn', array $row = [], ?string $dept = null): Ticket
    {
        $ticket = Ticket::factory()->create([
            'requester_id' => $this->requester->id,
            'request_type' => $type,
            'request_area' => $area,
            'status' => 'open',
            'assigned_to' => null,
            'extra' => ['target_department' => $dept ?? $this->dept],
        ]);

        DB::table('tickets')->where('id', $ticket->id)->update([
            'assigned_to' => null,
            'status' => 'open',
            'completion_date' => null,
            'completion_deadline' => null,
            'action_result' => null,
            'created_at' => now(),
            ...$row,
        ]);
        ModelCacheVersion::bump(Ticket::class);

        return $ticket->fresh();
    }

    private function closed(string $type, string $area, float $days, int $age = 5, array $row = [], ?string $dept = null): Ticket
    {
        $created = now()->subDays($age);

        return $this->ticket($type, $area, [
            'status' => 'closed',
            'created_at' => $created,
            'completion_date' => $created->copy()->addSeconds((int)($days * 86400)),
            ...$row,
        ], $dept);
    }

    private function reply(Ticket $ticket, int $userId, int $hoursAfterCreation): void
    {
        $reply = Reply::create(['repliable_type' => Ticket::class, 'repliable_id' => $ticket->id, 'user_id' => $userId, 'body' => 'x']);
        DB::table('replies')->where('id', $reply->id)->update(['created_at' => $ticket->created_at->copy()->addHours($hoursAfterCreation)]);
    }

    private function card(array $cards, string $key): array
    {
        return collect($cards)->firstWhere('key', $key);
    }

    private function summaryCard(array $summary, string $key): array
    {
        return $this->card(collect($summary['sections'])->flatMap(fn($s) => $s['cards'])->all(), $key);
    }

    private function fa(mixed $value): string
    {
        return convertToPersian((string)$value);
    }

    public function test_typical_close_map_uses_median_per_level_and_falls_back_to_type_then_department_then_hides(): void
    {
        foreach ([1, 2, 3, 4, 5] as $days) {
            $this->closed('support', 'vpn', $days);
            $this->closed('support', 'printer_scanner', 10);
        }
        $this->closed('access', 'email', 20);
        $this->closed('access', 'email', 20);

        $map = $this->service()->typicalCloseMap($this->dept);

        $this->assertSame(3, $this->service()->typicalCloseDays($map, 'support', 'vpn'));
        $this->assertSame(10, $this->service()->typicalCloseDays($map, 'support', 'printer_scanner'));
        $this->assertSame(8, $this->service()->typicalCloseDays($map, 'support', 'windows_office'));
        $this->assertSame(10, $this->service()->typicalCloseDays($map, 'access', 'email'));

        $thin = 'T' . Str::upper(Str::random(4));
        foreach (range(1, 4) as $i) {
            $this->closed('support', 'vpn', 2, 5, [], $thin);
        }
        $this->assertNull($this->service()->typicalCloseDays($this->service()->typicalCloseMap($thin), 'support', 'vpn'));
    }

    public function test_typical_close_map_ignores_old_and_withdrawn_tickets_and_uses_the_default_department_for_null(): void
    {
        foreach (range(1, 5) as $i) {
            $this->closed('support', 'vpn', 2, 120);
            $this->closed('support', 'vpn', 2, 5, ['action_result' => Ticket::WITHDRAWN_RESULT]);
        }

        $this->assertSame([], $this->service()->typicalCloseMap($this->dept));

        $default = Ticket::defaultTargetDepartment();

        if ($default === null) {
            $this->markTestSkipped('No default target department in this database.');
        }

        $this->assertSame($this->service()->typicalCloseMap($default), $this->service()->typicalCloseMap(null));
    }

    public function test_ticket_timeline_reports_first_response_deleted_assignee_and_typical_only_while_open(): void
    {
        foreach (range(1, 5) as $i) {
            $this->closed('support', 'vpn', 3);
        }
        $staff = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $open = $this->ticket('support', 'vpn', ['assigned_to' => self::GHOST, 'created_at' => now()->subDays(2)]);

        $timeline = $this->service()->ticketTimeline($open);

        $this->assertSame(__('resources/ths/strings.insight.timeline.awaiting'), $timeline['response']);
        $this->assertNull($timeline['assignee']);
        $this->assertStringContainsString($this->fa(3), $timeline['typical']);

        $this->reply($open, $this->requester->id, 1);
        $this->assertSame(__('resources/ths/strings.insight.timeline.awaiting'), $this->service()->ticketTimeline($open)['response']);

        $this->reply($open, $staff->id, 5);
        $this->assertStringContainsString($this->fa(5), $this->service()->ticketTimeline($open)['response']);
        $this->assertStringContainsString(__('resources/ths/strings.insight.units.hour'), $this->service()->ticketTimeline($open)['response']);

        $closed = $this->closed('support', 'vpn', 1);
        $closedTimeline = $this->service()->ticketTimeline($closed);
        $this->assertNull($closedTimeline['typical']);
        $this->assertSame(__('resources/ths/strings.insight.timeline.no_response'), $closedTimeline['response']);
    }

    public function test_for_ticket_compares_with_the_median_of_the_same_area_then_type_and_flags_ownership_gaps(): void
    {
        foreach (range(1, 3) as $i) {
            $this->closed('support', 'vpn', 1);
        }
        $slow = $this->ticket('support', 'vpn', ['assigned_to' => self::GHOST, 'created_at' => now()->subDays(3), 'completion_deadline' => now()->subDays(2)]);

        $cards = $this->service()->forTicket($slow)['cards'];

        $duration = $this->card($cards, 'duration');
        $this->assertStringContainsString($this->fa('3') . '٫', $duration['value']);
        $this->assertSame('danger', $duration['color']);
        $this->assertStringContainsString(__('resources/ths/strings.insight.basis.type_area'), $duration['hint']);
        $this->assertSame(__('resources/ths/strings.insight.deleted_user'), $this->card($cards, 'owner')['value']);
        $this->assertSame('danger', $this->card($cards, 'deadline')['color']);
        $this->assertSame(__('resources/ths/strings.insight.no_reply'), $this->card($cards, 'first-response')['value']);

        $otherArea = $this->ticket('support', 'zz_unseen', ['created_at' => now()->subDays(1)]);
        $this->assertStringContainsString(
            __('resources/ths/strings.insight.basis.type'),
            $this->card($this->service()->forTicket($otherArea)['cards'], 'duration')['hint'],
        );

        $alone = $this->ticket('development', 'api_integration', [], 'T' . Str::upper(Str::random(4)));
        $aloneCards = $this->service()->forTicket($alone)['cards'];
        $this->assertSame(__('resources/ths/strings.insight.no_baseline'), $this->card($aloneCards, 'duration')['hint']);
        $this->assertSame(__('resources/ths/strings.insight.no_deadline'), $this->card($aloneCards, 'deadline')['value']);
        $this->assertSame(__('resources/ths/strings.fields.unassigned'), $this->card($aloneCards, 'owner')['value']);
    }

    public function test_for_ticket_lists_up_to_five_similar_tickets_excluding_itself_and_counts_other_open_ones(): void
    {
        $tickets = collect(range(1, 7))->map(fn() => $this->ticket('support', 'vpn'));
        $current = $tickets->last();

        $result = $this->service()->forTicket($current);

        $this->assertCount(5, $result['similar']);
        $this->assertNotContains($current->id, array_column($result['similar'], 'id'));
        $this->assertSame($this->fa(6), $this->card($result['cards'], 'requester-open')['value']);
    }

    public function test_legacy_ticket_without_stored_department_is_analysed_against_the_default_department(): void
    {
        $default = Ticket::defaultTargetDepartment();

        if ($default === null) {
            $this->markTestSkipped('No default target department in this database.');
        }

        $legacy = $this->ticket('support', 'vpn');
        DB::table('tickets')->where('id', $legacy->id)->update(['extra' => null]);

        $result = $this->service()->forTicket($legacy->fresh());

        $this->assertCount(6, $result['cards']);
        $this->assertSame($default, $legacy->fresh()->effectiveTargetDepartment());
    }

    public function test_department_summary_computes_volume_speed_backlog_labels_and_workload(): void
    {
        $staff = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->ticket('support', 'vpn', ['created_at' => now()->subDays(10)]);
        $this->ticket('support', 'vpn', ['status' => 'in-progress', 'assigned_to' => self::GHOST, 'created_at' => now()->subDays(2)]);
        $first = $this->closed('support', 'vpn', 1, 20);
        $this->closed('support', 'vpn', 3, 20);
        $this->closed('support', 'vpn', 3, 120);
        $this->reply($first, $staff->id, 2);

        $summary = $this->service()->forDepartment($this->dept);

        $this->assertFalse($summary['empty']);
        $this->assertSame($this->fa(1), $this->summaryCard($summary, 'open')['value']);
        $this->assertSame($this->fa(1), $this->summaryCard($summary, 'in_progress')['value']);
        $this->assertSame($this->fa(2), $this->summaryCard($summary, 'closed')['value']);
        $this->assertStringContainsString($this->fa(2), $this->summaryCard($summary, 'median_close')['value']);
        $this->assertStringContainsString($this->fa(3), $this->summaryCard($summary, 'p90_close')['value']);
        $this->assertStringContainsString($this->fa(2), $this->summaryCard($summary, 'median_first')['value']);
        $this->assertSame($this->fa(1), $this->summaryCard($summary, 'stale')['value']);
        $this->assertSame($this->fa(1), $this->summaryCard($summary, 'unassigned')['value']);

        $topCount = collect($summary['tables'])->firstWhere('key', 'top-count')['rows'];
        $this->assertStringContainsString(Ticket::$requestAreaOptions['support']['vpn'], $topCount[0][0]);
        $this->assertStringNotContainsString('vpn', $topCount[0][0]);

        $workload = collect($summary['tables'])->firstWhere('key', 'workload')['rows'];
        $this->assertSame(__('resources/ths/strings.insight.deleted_user'), $workload[0][0]);
    }

    public function test_statistics_labels_and_related_list_never_mix_two_departments_sharing_an_area_key(): void
    {
        $a = $this->dept;
        $b = 'T' . Str::upper(Str::random(4));
        foreach ([[$a, 'برچسب واحد الف'], [$b, 'برچسب واحد ب']] as [$code, $label]) {
            Department::create([
                'code' => $code,
                'name' => $code,
                'description' => $code,
                'ticket_options' => [['request_type' => 'zz_shared', 'area_key' => 'shared_key', 'area_label' => $label]],
            ]);
        }

        foreach (range(1, 3) as $i) {
            $this->closed('zz_shared', 'shared_key', 1, 5, [], $a);
            $this->closed('zz_shared', 'shared_key', 10, 20, [], $b);
        }
        $open = $this->ticket('zz_shared', 'shared_key', ['created_at' => now()->subDays(2)], $a);
        $foreign = $this->ticket('zz_shared', 'shared_key', [], $b);

        $result = $this->service()->forTicket($open);
        $ratio = $this->card($result['cards'], 'duration')['value'];
        $this->assertStringContainsString($this->fa(2) . '٫', $ratio);
        $this->assertNotContains($foreign->id, array_column($result['similar'], 'id'));
        $this->assertCount(3, $result['similar']);

        $summaryA = $this->service()->forDepartment($a);
        $rowsA = collect($summaryA['tables'])->firstWhere('key', 'top-count')['rows'];
        $this->assertCount(1, $rowsA);
        $this->assertStringContainsString('برچسب واحد الف', $rowsA[0][0]);
        $this->assertStringNotContainsString('برچسب واحد ب', $rowsA[0][0]);
        $this->assertSame($this->fa(4), $rowsA[0][1]);

        $rowsB = collect($this->service()->forDepartment($b)['tables'])->firstWhere('key', 'top-count')['rows'];
        $this->assertStringContainsString('برچسب واحد ب', $rowsB[0][0]);
        $this->assertSame($this->fa(4), $rowsB[0][1]);
    }

    public function test_department_summary_has_safe_empty_states(): void
    {
        $this->assertNull($this->service()->forDepartment(null));

        $summary = $this->service()->forDepartment($this->dept);

        $this->assertTrue($summary['empty']);
        $this->assertSame('—', $this->summaryCard($summary, 'median_close')['value']);
        $this->assertSame('—', $this->summaryCard($summary, 'avg_satisfaction')['value']);
        $this->assertSame([], collect($summary['tables'])->firstWhere('key', 'workload')['rows']);
    }

    public function test_department_summary_cache_is_invalidated_by_ticket_and_reply_saves(): void
    {
        $staff = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $ticket = $this->closed('support', 'vpn', 1);

        $warm = $this->service()->forDepartment($this->dept);
        $this->assertSame($this->fa(1), $this->summaryCard($warm, 'closed')['value']);
        $this->assertSame('—', $this->summaryCard($warm, 'median_first')['value']);

        $this->reply($ticket, $staff->id, 4);
        $afterReply = $this->service()->forDepartment($this->dept);
        $this->assertNotSame('—', $this->summaryCard($afterReply, 'median_first')['value']);

        $this->closed('support', 'vpn', 2);
        $afterTicket = $this->service()->forDepartment($this->dept);
        $this->assertSame($this->fa(2), $this->summaryCard($afterTicket, 'closed')['value']);
    }
}
