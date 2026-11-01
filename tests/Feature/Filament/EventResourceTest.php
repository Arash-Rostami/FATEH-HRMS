<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\EventResource\Pages\CreateEvent;
use App\Filament\Resources\EventResource\Pages\EditEvent;
use App\Filament\Resources\EventResource\Pages\ListEvents;
use App\Models\Event;
use App\Models\Permission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Morilog\Jalali\Jalalian;
use Tests\TestCase;

class EventResourceTest extends TestCase
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

    private function developer(): User
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $user->id, 'is_super_admin' => true]);

        return $user;
    }

    public function test_list_renders_and_shows_factory_event_row()
    {
        $admin = $this->developer();
        $event = Event::factory()->create(['user_id' => $admin->id, 'title' => 'List Render Probe']);

        Livewire::actingAs($admin)->test(ListEvents::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$event]);
    }

    public function test_create_event_persists_title_description_and_date()
    {
        $admin = $this->developer();

        $targetGregorian = '2026-08-15';
        $jalali = Jalalian::fromCarbon(Carbon::parse($targetGregorian));

        Livewire::actingAs($admin)->test(CreateEvent::class)
            ->fillForm([
                'title' => 'Quarterly Kickoff',
                'description' => 'All-hands sync for the new quarter.',
                'date_jalali' => $targetGregorian,
                'date_jalali_year' => (string) $jalali->getYear(),
                'date_jalali_month' => (string) $jalali->getMonth(),
                'date_jalali_day' => (string) $jalali->getDay(),
                'date_time_part' => '10:30',
                'private' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('events', [
            'title' => 'Quarterly Kickoff',
            'private' => 0,
        ]);

        $event = Event::where('title', 'Quarterly Kickoff')->first();
        $this->assertNotNull($event);
        $this->assertSame($targetGregorian, $event->date->format('Y-m-d'));
        $this->assertSame('10:30', $event->date->format('H:i'));
    }

    public function test_create_event_persists_remind_hours()
    {
        $admin = $this->developer();

        $targetGregorian = '2026-08-15';
        $jalali = Jalalian::fromCarbon(Carbon::parse($targetGregorian));

        Livewire::actingAs($admin)->test(CreateEvent::class)
            ->fillForm([
                'title' => 'Reminder Probe',
                'date_jalali' => $targetGregorian,
                'date_jalali_year' => (string) $jalali->getYear(),
                'date_jalali_month' => (string) $jalali->getMonth(),
                'date_jalali_day' => (string) $jalali->getDay(),
                'date_time_part' => '10:30',
                'private' => false,
                'remind_hours' => 3,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('events', [
            'title' => 'Reminder Probe',
            'remind_hours' => 3,
        ]);
    }

    public function test_edit_event_clears_remind_hours()
    {
        $admin = $this->developer();
        $event = Event::factory()->create([
            'user_id' => $admin->id,
            'title' => 'Clear Reminder Probe',
            'private' => false,
            'remind_hours' => 6,
        ]);

        Livewire::actingAs($admin)->test(EditEvent::class, ['record' => $event->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['remind_hours' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($event->fresh()->remind_hours);
    }

    public function test_create_event_requires_title()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateEvent::class)
            ->fillForm([
                'title' => '',
                'date_jalali' => '2026-08-15',
                'date_time_part' => '10:30',
            ])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required']);
    }

    public function test_edit_event_updates_title_and_preserves_date()
    {
        $admin = $this->developer();
        $event = Event::factory()->create([
            'user_id' => $admin->id,
            'title' => 'Original Title',
            'date' => '2026-09-01 14:00:00',
            'private' => false,
        ]);

        Livewire::actingAs($admin)->test(EditEvent::class, ['record' => $event->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['title' => 'Renamed Event'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Renamed Event', $event->fresh()->title);
        $this->assertSame('2026-09-01', $event->fresh()->date->format('Y-m-d'));
    }

    public function test_delete_event_action_removes_row()
    {
        $admin = $this->developer();
        $event = Event::factory()->create(['user_id' => $admin->id, 'title' => 'Delete Probe']);

        Livewire::actingAs($admin)->test(ListEvents::class)
            ->callTableAction(\Filament\Actions\DeleteAction::class, $event);

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_upcoming_filter_scopes_list_to_future_events_only()
    {
        $admin = $this->developer();
        $future = Event::factory()->create(['user_id' => $admin->id, 'title' => 'Future Probe', 'date' => now()->addDays(3)]);
        $past = Event::factory()->create(['user_id' => $admin->id, 'title' => 'Past Probe', 'date' => now()->subDays(3)]);

        Livewire::actingAs($admin)->test(ListEvents::class)
            ->assertSuccessful()
            ->filterTable('upcoming')
            ->assertCanSeeTableRecords([$future])
            ->assertCanNotSeeTableRecords([$past]);
    }

    public function test_user_filter_scopes_list_to_matching_user()
    {
        $admin = $this->developer();
        $otherUser = User::factory()->create();

        $adminEvent = Event::factory()->create(['user_id' => $admin->id, 'title' => 'Admin Event']);
        $otherEvent = Event::factory()->create(['user_id' => $otherUser->id, 'title' => 'Other Event']);

        Livewire::actingAs($admin)->test(ListEvents::class)
            ->assertSuccessful()
            ->filterTable('user_id', $admin->id)
            ->assertCanSeeTableRecords([$adminEvent])
            ->assertCanNotSeeTableRecords([$otherEvent]);
    }

    public function test_visibility_filter_scopes_list_to_private_events()
    {
        $admin = $this->developer();
        $private = Event::factory()->create(['user_id' => $admin->id, 'title' => 'Private Probe', 'private' => true]);
        $public = Event::factory()->create(['user_id' => $admin->id, 'title' => 'Public Probe', 'private' => false]);

        Livewire::actingAs($admin)->test(ListEvents::class)
            ->assertSuccessful()
            ->filterTable('private', true)
            ->assertCanSeeTableRecords([$private])
            ->assertCanNotSeeTableRecords([$public]);
    }

    public function test_date_range_filter_scopes_to_the_given_date_range()
    {
        $admin = $this->developer();

        $inRange = Event::factory()->create(['user_id' => $admin->id, 'title' => 'In Range', 'date' => now()->subDays(5)]);
        $outOfRange = Event::factory()->create(['user_id' => $admin->id, 'title' => 'Out Of Range', 'date' => now()->subDays(60)]);

        Livewire::actingAs($admin)->test(ListEvents::class)
            ->assertSuccessful()
            ->filterTable('date_range', ['from' => now()->subDays(10)->toDateString(), 'until' => now()->toDateString()])
            ->assertCanSeeTableRecords([$inRange])
            ->assertCanNotSeeTableRecords([$outOfRange]);
    }

    public function test_sorting_by_title_orders_records_correctly()
    {
        $admin = $this->developer();
        $first = Event::factory()->create(['user_id' => $admin->id, 'title' => '0-AAA-sort-event']);
        $second = Event::factory()->create(['user_id' => $admin->id, 'title' => '0-ZZZ-sort-event']);

        $component = Livewire::actingAs($admin)->test(ListEvents::class)
            ->set('tableRecordsPerPage', 1000)
            ->sortTable('title')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('title', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sorting_by_id_created_at_and_date_orders_records_correctly()
    {
        $admin = $this->developer();
        $older = Event::factory()->create(['user_id' => $admin->id, 'title' => '0-sort-a', 'date' => now()->subDays(60)]);
        $older->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();
        $newer = Event::factory()->create(['user_id' => $admin->id, 'title' => '0-sort-b', 'date' => now()->addDays(60)]);
        $newer->forceFill(['created_at' => now()])->saveQuietly();

        $component = Livewire::actingAs($admin)->test(ListEvents::class)
            ->set('tableRecordsPerPage', 1000)
            ->sortTable('id')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('id', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('created_at')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('date')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('date', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();
        Event::factory()->create(['user_id' => $admin->id, 'title' => 'Group Render Probe']);

        Livewire::actingAs($admin)->test(ListEvents::class)
            ->assertSuccessful()
            ->set('tableGrouping', 'private')
            ->assertSuccessful();
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\EventResource\Pages\CreateEvent::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\EventResource\Pages\EditEvent::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\EventResource\Pages\ListEvents::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\EventResource\Pages\CreateEvent::class, \App\Filament\Resources\EventResource\Pages\EditEvent::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }

        $managers = [\App\Filament\Resources\EventResource\RelationManagers\EventSharesRelationManager::class];

        foreach ($managers as $manager) {
            $this->assertSame(\App\Filament\RelationManagers\RelationManager::class, get_parent_class($manager));
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, class_uses_recursive($manager));
        }
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/EventResource/Schemas/EventTablePresenter.php'),
        ];

        $recordMethods = ['getTitleFromRecordUsing', 'getKeyFromRecordUsing', 'getDescriptionFromRecordUsing'];
        $queryMethods = ['modifyQueryUsing', 'groupQueryUsing', 'orderQueryUsing', 'scopeQueryByKeyUsing', 'scopeQueryUsing', 'query'];

        $offenders = [];

        foreach ([[$recordMethods, 'record'], [$queryMethods, 'query']] as [$methods, $expectedName]) {
            $pattern = '/\\b(' . implode('|', $methods) . ')\\s*\\(\\s*(?:static\\s+)?(?:function|fn)\\s*\\(([^)]*)\\)/s';

            foreach ($files as $path) {
                $contents = file_get_contents($path);

                if (!preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                    continue;
                }

                foreach ($matches as $match) {
                    $method = $match[1][0];
                    $paramList = $match[2][0];
                    $offset = $match[0][1];

                    $firstParam = trim(explode(',', $paramList)[0] ?? '');

                    if ($firstParam === '') {
                        continue;
                    }

                    if (!preg_match('/^(?<type>[^$]*)\\$(?<name>[A-Za-z_][A-Za-z0-9_]*)/', $firstParam, $m)) {
                        continue;
                    }

                    $hasType = trim($m['type']) !== '';
                    $name = $m['name'];

                    if ($name === $expectedName || $hasType) {
                        continue;
                    }

                    $line = substr_count(substr($contents, 0, $offset), "\n") + 1;
                    $offenders[] = basename($path) . ":{$line} {$method}(fn(\${$name}) => ...)";
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Closure param must be named \$record/\$query (or carry an explicit type hint) so Filament's by-name evaluate() injection binds correctly:\n" . implode("\n", $offenders),
        );
    }
}
