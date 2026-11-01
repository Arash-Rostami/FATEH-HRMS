<?php

namespace Tests\Feature\Services\ProjectTask;

use App\Livewire\Dashboard\Project\Main;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectTask\ProjectSettings;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Morilog\Jalali\CalendarUtils;
use Tests\TestCase;

class ProjectSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        Livewire::withoutLazyLoading();
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

    private function ownerWithSettings(?array $settings): array
    {
        $owner = User::factory()->create(['status' => 'active']);
        $project = Project::factory()->create([
            'owner_id' => $owner->id,
            'member_ids' => [],
            'departments' => [],
            'settings' => $settings,
        ]);

        return [$owner, $project];
    }

    public function test_setting_returns_each_settings_key_with_its_native_type(): void
    {
        [$owner, $project] = $this->ownerWithSettings([
            'requires_approval' => true,
            'sla' => 48,
            'deadline' => '2026-09-10',
            'custom_schema' => ['vendor' => ['label' => 'تأمین‌کننده']],
        ]);

        $this->assertTrue($project->setting('requires_approval'));
        $this->assertSame(48, $project->setting('sla'));
        $this->assertSame('2026-09-10', $project->setting('deadline'));
        $this->assertSame(['vendor' => ['label' => 'تأمین‌کننده']], $project->setting('custom_schema'));
    }

    public function test_setting_returns_null_by_default_and_honors_a_custom_default(): void
    {
        [$owner, $project] = $this->ownerWithSettings(null);

        $this->assertNull($project->setting('requires_approval'));
        $this->assertNull($project->setting('sla'));
        $this->assertNull($project->setting('deadline'));
        $this->assertSame(24, $project->setting('sla', 24));
    }

    public function test_bag_collects_all_four_settings_from_the_form(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $form = Livewire::actingAs($owner)->test(Main::class)->instance()->projectForm;
        $future = now()->addDays(40);
        [$year, $month, $day] = CalendarUtils::toJalali($future->year, $future->month, $future->day);
        $form->requiresApproval = true;
        $form->slaHours = 8;
        $form->deadlineYear = (string) $year;
        $form->deadlineMonth = (string) $month;
        $form->deadlineDay = (string) $day;
        $form->customSchema = [['key' => 'vendor', 'label' => 'تأمین‌کننده']];

        $this->assertSame([
            'requires_approval' => true,
            'sla' => 8,
            'deadline' => $future->toDateString(),
            'custom_schema' => ['vendor' => ['label' => 'تأمین‌کننده']],
        ], app(ProjectSettings::class)->bag($form));
    }

    public function test_bag_drops_unfilled_settings_to_an_empty_array(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $form = Livewire::actingAs($owner)->test(Main::class)->instance()->projectForm;

        $this->assertSame([], app(ProjectSettings::class)->bag($form));
    }

    public function test_fill_form_hydrates_the_settings_fields_from_a_saved_project(): void
    {
        [$owner, $project] = $this->ownerWithSettings([
            'requires_approval' => true,
            'sla' => 8,
            'deadline' => '2026-09-10',
            'custom_schema' => ['vendor' => ['label' => 'تأمین‌کننده']],
        ]);
        $form = Livewire::actingAs($owner)->test(Main::class)->instance()->projectForm;

        app(ProjectSettings::class)->fillForm($form, $project);

        $this->assertTrue($form->requiresApproval);
        $this->assertSame(8, $form->slaHours);
        $this->assertSame([['key' => 'vendor', 'label' => 'تأمین‌کننده']], $form->customSchema);
        [$year, $month, $day] = CalendarUtils::toJalali(2026, 9, 10);
        $this->assertSame((string) $year, $form->deadlineYear);
        $this->assertSame((string) $month, $form->deadlineMonth);
        $this->assertSame((string) $day, $form->deadlineDay);
    }

    public function test_updating_project_clears_requires_approval_and_sla_when_unset(): void
    {
        [$owner, $project] = $this->ownerWithSettings(['requires_approval' => true, 'sla' => 8]);
        $form = Livewire::actingAs($owner)->test(Main::class)->instance()->projectForm;
        $form->name = $project->name;
        $form->memberIds = $project->member_ids;
        $form->departments = $project->departments;
        $form->requiresApproval = false;
        $form->slaHours = null;

        app(\App\Livewire\Dashboard\Project\Actions\UpdateProjectAction::class)->execute($project, $form);

        $this->assertNull($project->fresh()->setting('requires_approval'));
        $this->assertNull($project->fresh()->setting('sla'));
    }

    public function test_settings_summary_lists_active_settings_and_dash_when_empty(): void
    {
        [, $project] = $this->ownerWithSettings([
            'requires_approval' => true,
            'sla' => 48,
            'deadline' => now()->toDateString(),
            'custom_schema' => ['vendor' => ['label' => 'تأمین‌کننده'], 'cost' => ['label' => 'هزینه']],
        ]);

        $summary = $project->settingsSummary();
        $this->assertStringContainsString('تأیید مدیر فعال', $summary);
        $this->assertStringContainsString('SLA: 48 ساعت', $summary);
        $this->assertStringContainsString('مهلت:', $summary);
        $this->assertStringContainsString('2 فیلد سفارشی', $summary);

        [, $empty] = $this->ownerWithSettings(null);
        $this->assertSame('—', $empty->settingsSummary());
    }

    public function test_other_settings_returns_only_unknown_keys(): void
    {
        [, $project] = $this->ownerWithSettings([
            'requires_approval' => true,
            'sla' => 8,
            'deadline' => '2026-09-10',
            'custom_schema' => ['vendor' => ['label' => 'تأمین‌کننده']],
            'webhook_url' => 'https://example.test/hook',
            'retry_count' => 3,
        ]);

        $this->assertSame([
            'webhook_url' => 'https://example.test/hook',
            'retry_count' => '3',
        ], $project->otherSettings());
    }

    public function test_other_settings_is_empty_when_only_known_keys_are_set(): void
    {
        [, $project] = $this->ownerWithSettings(['requires_approval' => true]);

        $this->assertSame([], $project->otherSettings());
    }

    public function test_bag_merges_extra_settings_rows(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $form = Livewire::actingAs($owner)->test(Main::class)->instance()->projectForm;
        $form->extraSettings = [['key' => 'webhook_url', 'value' => 'https://example.test/hook']];

        $this->assertSame(
            ['webhook_url' => 'https://example.test/hook'],
            app(ProjectSettings::class)->bag($form)
        );
    }

    public function test_bag_ignores_extra_settings_rows_with_a_reserved_key(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $form = Livewire::actingAs($owner)->test(Main::class)->instance()->projectForm;
        $form->extraSettings = [['key' => 'sla', 'value' => 'not-a-number']];

        $this->assertSame([], app(ProjectSettings::class)->bag($form));
    }

    public function test_fill_form_hydrates_extra_settings_from_other_settings(): void
    {
        [$owner, $project] = $this->ownerWithSettings([
            'requires_approval' => true,
            'webhook_url' => 'https://example.test/hook',
        ]);
        $form = Livewire::actingAs($owner)->test(Main::class)->instance()->projectForm;

        app(ProjectSettings::class)->fillForm($form, $project);

        $this->assertSame([['key' => 'webhook_url', 'value' => 'https://example.test/hook']], $form->extraSettings);
    }

    public function test_updating_project_persists_a_new_extra_setting(): void
    {
        [$owner, $project] = $this->ownerWithSettings(null);
        $form = Livewire::actingAs($owner)->test(Main::class)->instance()->projectForm;
        $form->name = $project->name;
        $form->memberIds = $project->member_ids;
        $form->departments = $project->departments;
        $form->extraSettings = [['key' => 'webhook_url', 'value' => 'https://example.test/hook']];

        app(\App\Livewire\Dashboard\Project\Actions\UpdateProjectAction::class)->execute($project, $form);

        $this->assertSame('https://example.test/hook', $project->fresh()->setting('webhook_url'));
    }

    public function test_extra_settings_key_must_not_reuse_a_reserved_settings_key(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        Livewire::actingAs($owner)->test(Main::class)
            ->call('selectProject', Project::factory()->create(['owner_id' => $owner->id, 'member_ids' => [], 'departments' => []])->id)
            ->call('openEdit')
            ->set('projectForm.extraSettings', [['key' => 'sla', 'value' => '10']])
            ->call('updateProject')
            ->assertHasErrors(['projectForm.extraSettings.0.key' => 'not_in']);
    }

    private function deadlineForm(?string $year, ?string $month, ?string $day): \App\Livewire\Dashboard\Project\Forms\ProjectForm
    {
        $owner = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $form = Livewire::actingAs($owner)->test(Main::class)->instance()->projectForm;
        $form->deadlineYear = (string) $year;
        $form->deadlineMonth = (string) $month;
        $form->deadlineDay = (string) $day;

        return $form;
    }

    private function assertDeadlineRejected(\App\Livewire\Dashboard\Project\Forms\ProjectForm $form, ?string $current = null): void
    {
        try {
            app(ProjectSettings::class)->bag($form, $current);
            $this->fail('deadline must be rejected');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('projectForm.deadlineDay', $e->errors());
        }
    }

    public function test_deadline_rejects_partial_impossible_and_past_dates(): void
    {
        $future = now()->addYears(2);
        [$year] = CalendarUtils::toJalali($future->year, $future->month, $future->day);

        $this->assertDeadlineRejected($this->deadlineForm((string) $year, '5', ''));
        $this->assertDeadlineRejected($this->deadlineForm((string) $year, '7', '31'));

        $past = now()->subDays(3);
        [$py, $pm, $pd] = CalendarUtils::toJalali($past->year, $past->month, $past->day);
        $this->assertDeadlineRejected($this->deadlineForm((string) $py, (string) $pm, (string) $pd));
    }

    public function test_an_unchanged_past_deadline_is_tolerated_on_edit_but_a_changed_one_is_not(): void
    {
        $past = now()->subDays(3);
        [$py, $pm, $pd] = CalendarUtils::toJalali($past->year, $past->month, $past->day);
        $form = $this->deadlineForm((string) $py, (string) $pm, (string) $pd);

        $bag = app(ProjectSettings::class)->bag($form, $past->toDateString());

        $this->assertSame($past->toDateString(), $bag['deadline']);
        $this->assertDeadlineRejected($form, now()->addDays(10)->toDateString());
    }
}
