<?php

namespace Tests\Feature\Traits;

use App\Filament\Resources\TaskResource\Pages\CreateTask;
use App\Filament\Resources\TaskResource\Pages\EditTask;
use App\Filament\Resources\ThsResource\Pages\CreateTicket;
use App\Filament\Resources\ThsResource\Pages\EditTicket;
use App\Models\Permission;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentDateHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        $this->app->setLocale('fa');
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

    public function test_create_with_deadline_date_and_time_merges_into_datetime(): void
    {
        $admin = $this->developer();
        $requester = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateTicket::class)
            ->fillForm([
                'requester_id' => $requester->id,
                'request_type' => 'support',
                'request_area' => 'windows_office',
                'request_subject' => '0-test-create-deadline',
                'description' => '0-test-create-deadline-body',
                'priority' => 'low',
                'status' => 'open',
                'completion_deadline_date' => '2026-08-15',
                'completion_deadline_time' => '14:30',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tickets', [
            'request_subject' => '0-test-create-deadline',
            'requester_id' => $requester->id,
            'completion_deadline' => '2026-08-15 14:30:00',
        ]);
    }

    public function test_create_without_deadline_date_persists_null(): void
    {
        $admin = $this->developer();
        $requester = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateTicket::class)
            ->fillForm([
                'requester_id' => $requester->id,
                'request_type' => 'support',
                'request_area' => 'windows_office',
                'request_subject' => '0-test-create-no-deadline',
                'description' => '0-test-create-no-deadline-body',
                'priority' => 'low',
                'status' => 'open',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tickets', [
            'request_subject' => '0-test-create-no-deadline',
            'requester_id' => $requester->id,
            'completion_deadline' => null,
        ]);
    }

    public function test_edit_fill_splits_stored_deadline_into_date_and_time(): void
    {
        $admin = $this->developer();

        $ticket = Ticket::factory()->create([
            'request_subject' => '0-test-edit-deadline-split',
            'completion_deadline' => '2026-08-15 14:30:00',
            'requester_id' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(EditTicket::class, ['record' => $ticket->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet([
                'completion_deadline_date' => '2026-08-15',
            ]);

        $this->assertSame('2026-08-15 14:30:00', $ticket->fresh()->getRawOriginal('completion_deadline'));
    }

    public function test_task_create_and_edit_have_no_time_field_and_always_merge_at_the_default_time(): void
    {
        $admin = $this->developer();
        $creator = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateTask::class)
            ->assertFormFieldDoesNotExist('deadline_time')
            ->fillForm([
                'title' => '0-test-task-deadline-default',
                'user_id' => $creator->id,
                'status' => 'todo',
                'deadline_date' => '2026-08-15',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $task = Task::where('title', '0-test-task-deadline-default')->firstOrFail();
        $this->assertSame('2026-08-15 12:00:00', $task->getRawOriginal('deadline'));

        Livewire::actingAs($admin)
            ->test(EditTask::class, ['record' => $task->getRouteKey()])
            ->assertFormFieldDoesNotExist('deadline_time');
    }
}