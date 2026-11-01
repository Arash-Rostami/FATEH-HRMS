<?php

namespace Tests\Feature\Services\ProjectTask;

use App\Livewire\Dashboard\TaskBoard\Forms\TaskForm;
use App\Livewire\Dashboard\TaskBoard\Main;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectTask\CreateTaskAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Morilog\Jalali\Jalalian;
use Tests\TestCase;

class CreateTaskActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->setLocale('fa');
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

    private function form(User $owner): TaskForm
    {
        $this->actingAs($owner);

        return Livewire::test(Main::class)->instance()->form;
    }

    private function setDeadline(TaskForm $form, string $modifier): void
    {
        $j = Jalalian::fromCarbon(now()->modify($modifier));

        $form->deadlineYear = (string) $j->getYear();
        $form->deadlineMonth = (string) $j->getMonth();
        $form->deadlineDay = (string) $j->getDay();
    }

    public function test_create_rejects_deadline_beyond_the_project_deadline_setting(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['deadline' => now()->addDays(5)->toDateString()],
        ]);

        $form = $this->form($owner);
        $form->newTitle = 'Tinker project deadline cap probe';
        $form->projectId = $project->id;
        $this->setDeadline($form, '+10 days');

        try {
            app(CreateTaskAction::class)->execute($form);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('form.deadline', $e->errors());
            $this->assertSame('این تاریخ از مهلت پروژه فراتر است.', $e->errors()['form.deadline'][0]);
        }

        $this->assertTrue(Task::where('title', 'Tinker project deadline cap probe')->doesntExist());
    }

    public function test_create_accepts_deadline_within_the_project_deadline_setting(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['deadline' => now()->addDays(30)->toDateString()],
        ]);

        $form = $this->form($owner);
        $form->newTitle = 'Tinker within project deadline probe';
        $form->projectId = $project->id;
        $this->setDeadline($form, '+5 days');

        $task = app(CreateTaskAction::class)->execute($form);

        $this->assertTrue($task->exists);
        $this->assertSame('todo', $task->status);
        $this->assertSame($project->id, $task->project_id);
        $this->assertSame(now()->addDays(5)->toDateString(), $task->deadline->toDateString());
        $this->assertSame('12:00:00', $task->deadline->format('H:i:s'));
        $this->assertNotNull($task->detail);
    }

    public function test_sla_default_deadline_applies_on_create_and_project_change_but_not_on_unrelated_edits(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['sla' => 48],
        ]);
        $form = $this->form($owner);
        $resolver = new class {
            use \App\Traits\ResolvesTaskDeadline;

            public function call(...$args)
            {
                return $this->resolveTaskDeadline(...$args);
            }
        };

        $this->assertNotNull($resolver->call($form, $project, null, false, true));
        $this->assertNotNull($resolver->call($form, $project, null, true));
        $this->assertNull($resolver->call($form, $project, null, false));
    }

    public function test_create_normalizes_arabic_script_label_characters_to_persian(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []]);

        $form = $this->form($owner);
        $form->newTitle = 'Farsi label normalization probe';
        $form->projectId = $project->id;
        $this->setDeadline($form, '+5 days');
        $form->labels = [str_replace(['ی', 'ک'], ['ي', 'ك'], 'خودکارسازی')];

        $task = app(CreateTaskAction::class)->execute($form);

        $this->assertSame(['خودکارسازی'], $task->labels);
    }

    public function test_create_never_rejects_deadline_when_project_has_no_deadline_setting(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => ['sla' => 48],
        ]);

        $form = $this->form($owner);
        $form->newTitle = 'Tinker no project deadline probe';
        $form->projectId = $project->id;
        $this->setDeadline($form, '+60 days');

        $task = app(CreateTaskAction::class)->execute($form);

        $this->assertTrue($task->exists);
        $this->assertSame(now()->addDays(60)->toDateString(), $task->deadline->toDateString());
    }
}