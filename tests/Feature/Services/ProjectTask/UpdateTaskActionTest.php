<?php

namespace Tests\Feature\Services\ProjectTask;

use App\Livewire\Dashboard\TaskBoard\Forms\TaskForm;
use App\Livewire\Dashboard\TaskBoard\Main;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectTask\UpdateTaskAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class UpdateTaskActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->setLocale('fa');
        $this->useMysql();
        Storage::fake('public');
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

    private function taskWithAttachments(User $owner): Task
    {
        Storage::disk('public')->put('task/attachments/old.txt', 'x');
        Storage::disk('public')->put('task/attachments/kept.txt', 'x');

        $task = Task::factory()->create(['user_id' => $owner->id, 'assigned_to' => null, 'project_id' => null, 'ticket_id' => null, 'status' => 'todo']);
        $task->detail()->create(['attachments' => [
            ['path' => 'task/attachments/old.txt', 'name' => 'old.txt', 'mime' => 'text/plain', 'size' => 1],
            ['path' => 'task/attachments/kept.txt', 'name' => 'kept.txt', 'mime' => 'text/plain', 'size' => 1],
        ]]);

        return $task->fresh();
    }

    private function form(User $owner, Task $task, array $existing): TaskForm
    {
        $this->actingAs($owner);
        $form = Livewire::test(Main::class)->instance()->form;
        $form->newTitle = $task->title;
        $form->priority = $task->priority?->value ?? $form->priority;
        $form->existingAttachments = $existing;

        return $form;
    }

    public function test_dropping_an_existing_attachment_deletes_its_file_after_the_update_and_keeps_the_rest(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $task = $this->taskWithAttachments($owner);
        $form = $this->form($owner, $task, [['path' => 'task/attachments/kept.txt', 'name' => 'kept.txt']]);

        app(UpdateTaskAction::class)->execute($task, $form);

        Storage::disk('public')->assertMissing('task/attachments/old.txt');
        Storage::disk('public')->assertExists('task/attachments/kept.txt');
        $this->assertSame(['task/attachments/kept.txt'], array_column($task->detail->fresh()->attachments, 'path'));
    }

    public function test_dropping_a_non_leading_attachment_still_deletes_its_file(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $task = $this->taskWithAttachments($owner);
        $form = $this->form($owner, $task, [['path' => 'task/attachments/old.txt', 'name' => 'old.txt']]);

        app(UpdateTaskAction::class)->execute($task, $form);

        Storage::disk('public')->assertMissing('task/attachments/kept.txt');
        Storage::disk('public')->assertExists('task/attachments/old.txt');
    }

    public function test_forged_attachment_paths_are_ignored_and_stored_metadata_wins(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $task = $this->taskWithAttachments($owner);
        Storage::disk('public')->put('profiles/docs/someone-else.pdf', 'secret');
        $form = $this->form($owner, $task, [
            ['path' => 'task/attachments/kept.txt', 'name' => 'tampered.exe', 'mime' => 'application/x-msdownload', 'size' => 999],
            ['path' => 'profiles/docs/someone-else.pdf', 'name' => 'x.pdf'],
        ]);

        app(UpdateTaskAction::class)->execute($task, $form);

        $stored = $task->detail->fresh()->attachments;
        $this->assertSame(['task/attachments/kept.txt'], array_column($stored, 'path'));
        $this->assertSame('kept.txt', $stored[0]['name']);
        Storage::disk('public')->assertExists('profiles/docs/someone-else.pdf');
        Storage::disk('public')->assertMissing('task/attachments/old.txt');
    }
}
