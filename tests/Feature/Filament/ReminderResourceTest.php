<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ReminderResource\Pages\CreateReminder;
use App\Filament\Resources\ReminderResource\Pages\EditReminder;
use App\Filament\Resources\ReminderResource\Pages\ListReminders;
use App\Models\Permission;
use App\Models\Reminder;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Morilog\Jalali\Jalalian;
use Tests\TestCase;

class ReminderResourceTest extends TestCase
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

    private function activeUser(): User
    {
        return User::factory()->create(['status' => 'active']);
    }

    private function makeReminder(User $user, array $overrides = []): Reminder
    {
        return Reminder::create(array_merge([
            'user_id' => $user->id,
            'title' => 'Test reminder',
            'due_at' => now(),
            'recurs' => 'none',
            'channels' => ['inapp:edge,badge,nudge'],
        ], $overrides));
    }

    public function test_list_renders_and_shows_factory_record(): void
    {
        $admin = $this->developer();
        $owner = $this->activeUser();
        $record = $this->makeReminder($owner, ['title' => '0 test list renders']);

        Livewire::actingAs($admin)->test(ListReminders::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$record]);
    }

    public function test_create_reminder_persists_user_title_due_at_and_recurs(): void
    {
        $admin = $this->developer();
        $owner = $this->activeUser();
        $due = now()->addDays(3)->setTime(14, 30);
        $jDue = Jalalian::fromCarbon($due);

        Livewire::actingAs($admin)->test(CreateReminder::class)
            ->fillForm([
                'user_id' => $owner->id,
                'title' => 'New admin-created reminder',
                'recurs' => 'weekly',
                'due_at_date' => $due->format('Y-m-d'),
                'due_at_date_year' => $jDue->getYear(),
                'due_at_date_month' => $jDue->getMonth(),
                'due_at_date_day' => $jDue->getDay(),
                'due_at_time' => $due->format('H:i'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $reminder = Reminder::where('title', 'New admin-created reminder')->firstOrFail();
        $this->assertSame($owner->id, $reminder->user_id);
        $this->assertSame('weekly', $reminder->recurs->value);
        $this->assertSame($due->format('Y-m-d H:i'), $reminder->due_at->format('Y-m-d H:i'));
    }

    public function test_create_validation_requires_title(): void
    {
        $admin = $this->developer();
        $owner = $this->activeUser();

        Livewire::actingAs($admin)->test(CreateReminder::class)
            ->fillForm([
                'user_id' => $owner->id,
                'title' => '',
                'due_at_time' => '09:00',
            ])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required']);
    }

    public function test_edit_reminder_updates_title(): void
    {
        $admin = $this->developer();
        $owner = $this->activeUser();
        $record = $this->makeReminder($owner, ['title' => 'original title here']);

        Livewire::actingAs($admin)->test(EditReminder::class, ['record' => $record->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['title' => 'updated title text'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('updated title text', $record->fresh()->title);
    }

    public function test_delete_table_action_removes_record(): void
    {
        $admin = $this->developer();
        $owner = $this->activeUser();
        $record = $this->makeReminder($owner);

        Livewire::actingAs($admin)->test(ListReminders::class)
            ->callTableAction(DeleteAction::class, $record);

        $this->assertDatabaseMissing('reminders', ['id' => $record->id]);
    }

    public function test_completed_filter_scopes_list_to_matching_records(): void
    {
        $admin = $this->developer();
        $owner = $this->activeUser();
        $completed = $this->makeReminder($owner, ['title' => 'completed one', 'completed_at' => now()]);
        $active = $this->makeReminder($owner, ['title' => 'active one']);

        Livewire::actingAs($admin)->test(ListReminders::class)
            ->filterTable('completed', true)
            ->assertCanSeeTableRecords([$completed])
            ->assertCanNotSeeTableRecords([$active]);
    }

    public function test_user_filter_scopes_list_to_matching_owner(): void
    {
        $admin = $this->developer();
        $ownerA = $this->activeUser();
        $ownerB = $this->activeUser();
        $recordA = $this->makeReminder($ownerA, ['title' => 'owner a reminder']);
        $recordB = $this->makeReminder($ownerB, ['title' => 'owner b reminder']);

        Livewire::actingAs($admin)->test(ListReminders::class)
            ->filterTable('user_id', $ownerA->id)
            ->assertCanSeeTableRecords([$recordA])
            ->assertCanNotSeeTableRecords([$recordB]);
    }

    public function test_recurs_filter_scopes_list_to_matching_recurrence(): void
    {
        $admin = $this->developer();
        $owner = $this->activeUser();
        $weekly = $this->makeReminder($owner, ['title' => 'weekly reminder', 'recurs' => 'weekly']);
        $daily = $this->makeReminder($owner, ['title' => 'daily reminder', 'recurs' => 'daily']);

        Livewire::actingAs($admin)->test(ListReminders::class)
            ->filterTable('recurs', 'weekly')
            ->assertCanSeeTableRecords([$weekly])
            ->assertCanNotSeeTableRecords([$daily]);
    }

    public function test_created_at_filter_scopes_list_to_reminders_in_date_range(): void
    {
        $admin = $this->developer();
        $owner = $this->activeUser();

        $inRange = $this->makeReminder($owner, ['title' => '0-created-in-range']);
        $inRange->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();

        $outOfRange = $this->makeReminder($owner, ['title' => '0-created-out-of-range']);
        $outOfRange->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();

        Livewire::actingAs($admin)->test(ListReminders::class)
            ->filterTable('created_at', ['from' => now()->subDays(10)->toDateString(), 'until' => now()->toDateString()])
            ->assertCanSeeTableRecords([$inRange])
            ->assertCanNotSeeTableRecords([$outOfRange]);
    }

    public function test_sorting_by_completed_orders_records_correctly(): void
    {
        $admin = $this->developer();
        $owner = $this->activeUser();
        $notCompleted = $this->makeReminder($owner, ['title' => '0-sort-completed-a']);
        $completed = $this->makeReminder($owner, ['title' => '0-sort-completed-b', 'completed_at' => now()]);

        $component = Livewire::actingAs($admin)->test(ListReminders::class)
            ->sortTable('completed_at')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$notCompleted->id, $completed->id], array_values(array_intersect($ids, [$notCompleted->id, $completed->id])));

        $component->sortTable('completed_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$completed->id, $notCompleted->id], array_values(array_intersect($ids, [$notCompleted->id, $completed->id])));
    }

    public function test_sorting_by_created_at_due_at_and_id_orders_records_correctly(): void
    {
        $admin = $this->developer();
        $owner = $this->activeUser();
        $older = $this->makeReminder($owner, ['title' => '0-sort-created-a', 'due_at' => now()->subDays(30)]);
        $older->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();
        $newer = $this->makeReminder($owner, ['title' => '0-sort-created-b', 'due_at' => now()->addDays(30)]);
        $newer->forceFill(['created_at' => now()])->saveQuietly();

        $component = Livewire::actingAs($admin)->test(ListReminders::class)
            ->sortTable('created_at')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('due_at')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('due_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('id')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('id', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));
    }

    public function test_sorting_by_recurs_orders_records_correctly(): void
    {
        $admin = $this->developer();
        $owner = $this->activeUser();
        $daily = $this->makeReminder($owner, ['title' => '0-sort-recurs-a', 'recurs' => 'daily']);
        $weekly = $this->makeReminder($owner, ['title' => '0-sort-recurs-b', 'recurs' => 'weekly']);

        $component = Livewire::actingAs($admin)->test(ListReminders::class)
            ->sortTable('recurs')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$daily->id, $weekly->id], array_values(array_intersect($ids, [$daily->id, $weekly->id])));

        $component->sortTable('recurs', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$weekly->id, $daily->id], array_values(array_intersect($ids, [$daily->id, $weekly->id])));
    }

    public function test_sorting_by_user_orders_records_correctly(): void
    {
        $admin = $this->developer();
        $ownerA = User::factory()->create(['status' => 'active', 'name' => 'AAA-sort-owner']);
        $ownerB = User::factory()->create(['status' => 'active', 'name' => 'ZZZ-sort-owner']);
        $first = $this->makeReminder($ownerA, ['title' => '0-sort-user-a']);
        $second = $this->makeReminder($ownerB, ['title' => '0-sort-user-b']);

        $component = Livewire::actingAs($admin)->test(ListReminders::class)
            ->sortTable('user.name')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('user.name', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\ReminderResource\Pages\CreateReminder::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\ReminderResource\Pages\EditReminder::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\ReminderResource\Pages\ListReminders::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\ReminderResource\Pages\CreateReminder::class, \App\Filament\Resources\ReminderResource\Pages\EditReminder::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }
    }
}
