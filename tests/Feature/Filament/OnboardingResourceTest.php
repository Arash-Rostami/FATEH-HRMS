<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\OnboardingResource\Pages\CreateOnboarding;
use App\Filament\Resources\OnboardingResource\Pages\EditOnboarding;
use App\Filament\Resources\OnboardingResource\Pages\ListOnboardings;
use App\Filament\Resources\OnboardingResource\Schemas\Helper\ExtrasTemplate;
use App\Filament\Resources\OnboardingResource\Schemas\OnboardingFormPresenter;
use App\Filament\Resources\OnboardingResource\Schemas\OnboardingInfolistPresenter;
use App\Filament\Resources\OnboardingResource\Exports\OnboardingExporter;
use App\Models\Onboarding;
use App\Models\Permission;
use App\Models\User;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class OnboardingResourceTest extends TestCase
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

    public function test_list_renders_and_shows_factory_row()
    {
        $admin = $this->developer();
        $row = Onboarding::factory()->create(['welcome' => 'سلام خوش آمدید']);

        Livewire::actingAs($admin)->test(ListOnboardings::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$row]);
    }

    public function test_create_onboarding_persists_welcome_and_active_flag()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateOnboarding::class)
            ->fillForm([
                'welcome' => 'به سازمان ما خوش آمدید',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('onboardings', [
            'welcome' => '<p>به سازمان ما خوش آمدید</p>',
            'is_active' => 1,
        ]);
    }

    public function test_create_onboarding_requires_welcome_field()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateOnboarding::class)
            ->fillForm(['welcome' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => []]]]])
            ->call('create')
            ->assertHasFormErrors(['welcome' => 'The resources/onboarding/strings.fields.welcome field is required.']);
    }

    public function test_edit_onboarding_updates_welcome_content()
    {
        $admin = $this->developer();
        $row = Onboarding::factory()->create(['welcome' => 'متن قدیمی']);

        Livewire::actingAs($admin)->test(EditOnboarding::class, ['record' => $row->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['welcome' => 'متن جدید ویرایش شده'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertStringContainsString('متن جدید ویرایش شده', (string) $row->fresh()->welcome);
    }

    public function test_delete_action_removes_onboarding_record()
    {
        $admin = $this->developer();
        $row = Onboarding::factory()->create();

        Livewire::actingAs($admin)->test(ListOnboardings::class)
            ->callTableAction(\Filament\Actions\DeleteAction::class, $row);

        $this->assertDatabaseMissing('onboardings', ['id' => $row->id]);
    }

    public function test_is_active_filter_scopes_list_to_active_records_only()
    {
        $admin = $this->developer();
        $active = Onboarding::factory()->create(['is_active' => true]);
        $inactive = Onboarding::factory()->create(['is_active' => false]);

        Livewire::actingAs($admin)->test(ListOnboardings::class)
            ->assertSuccessful()
            ->filterTable('is_active', '1')
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$inactive]);
    }

    public function test_audience_filter_scopes_list_to_global_records_only()
    {
        $admin = $this->developer();
        $target = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'staff']);
        $personal = Onboarding::factory()->create(['user_id' => $target->id]);
        $global = Onboarding::factory()->create(['user_id' => null]);

        Livewire::actingAs($admin)->test(ListOnboardings::class)
            ->assertSuccessful()
            ->filterTable('audience', '0')
            ->assertCanSeeTableRecords([$global])
            ->assertCanNotSeeTableRecords([$personal]);
    }

    public function test_toggle_column_activation_deactivates_other_active_record_for_same_audience()
    {
        $admin = $this->developer();
        $first = Onboarding::factory()->create(['user_id' => null, 'is_active' => true]);
        $second = Onboarding::factory()->create(['user_id' => null, 'is_active' => false]);

        Livewire::actingAs($admin)->test(ListOnboardings::class)
            ->call('updateTableColumnState', 'is_active', (string) $second->getKey(), true);

        $this->assertTrue($second->fresh()->is_active);
        $this->assertFalse($first->fresh()->is_active);
    }

    public function test_hydrate_guides_with_file_meta_recomputes_stale_size_from_disk()
    {
        Storage::fake('public');
        Storage::disk('public')->put('onboarding/guides/g.pdf', str_repeat('x', 2048));

        $data = OnboardingFormPresenter::hydrateGuidesWithFileMeta([
            'guides' => [
                ['title' => 'G', 'url' => 'onboarding/guides/g.pdf', 'ext' => 'pdf', 'size' => '0 KB'],
            ],
        ]);

        $this->assertSame('pdf', $data['guides'][0]['ext']);
        $this->assertSame('2 KB', $data['guides'][0]['size']);
    }

    public function test_view_action_infolist_lists_semantic_extra_keys_not_repeater_uuids()
    {
        $row = Onboarding::factory()->create([
            'extras' => ['uuid-abc123' => ['key' => 'zz_parking', 'display_title' => null, 'value' => '<p>متن</p>']],
        ]);

        $state = OnboardingInfolistPresenter::extras()->model($row)->getState();

        $this->assertSame([['key' => 'zz_parking', 'value' => '<p>متن</p>']], $state);
    }

    public function test_exporter_extras_keys_column_lists_semantic_keys_not_repeater_uuids()
    {
        $row = Onboarding::factory()->create([
            'extras' => [
                'uuid-abc123' => ['key' => 'zz_parking', 'display_title' => null, 'value' => 'x'],
                'uuid-def456' => ['key' => 'zz_canteen', 'display_title' => null, 'value' => 'y'],
            ],
        ]);

        $data = (new OnboardingExporter(new Export(), ['extras_keys' => 'Extras'], []))($row);

        $this->assertSame(['zz_parking, zz_canteen'], $data);
    }

    public function test_extras_template_options_include_current_custom_keys_with_labels()
    {
        $options = ExtrasTemplate::options([
            ['key' => 'zz_custom', 'display_title' => 'عنوان سفارشی', 'value' => 'x'],
        ]);

        $this->assertSame('عنوان سفارشی', $options['سفارشی']['zz_custom']);
        $this->assertArrayHasKey('محیط کار', $options);

        $plain = ExtrasTemplate::options([]);
        $this->assertArrayNotHasKey('سفارشی', $plain);
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();
        Onboarding::factory()->create(['welcome' => 'گروه‌بندی تست']);

        Livewire::actingAs($admin)->test(ListOnboardings::class)
            ->assertSuccessful()
            ->set('tableGrouping', 'user_id')
            ->assertSuccessful();
    }

    public function test_sorting_by_id_and_created_at_produces_correct_order()
    {
        $admin = $this->developer();
        $first = Onboarding::factory()->create();
        $second = Onboarding::factory()->create();

        $component = Livewire::actingAs($admin)->test(ListOnboardings::class)
            ->set('tableRecordsPerPage', 'all')
            ->sortTable('id')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('id', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $old = Onboarding::factory()->create();
        $old->forceFill(['created_at' => now()->subYears(5)])->saveQuietly();
        $new = Onboarding::factory()->create();
        $new->forceFill(['created_at' => now()])->saveQuietly();

        $component->sortTable('created_at')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$old->id, $new->id], array_values(array_intersect($ids, [$old->id, $new->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$new->id, $old->id], array_values(array_intersect($ids, [$old->id, $new->id])));
    }

    public function test_sorting_by_user_name_produces_correct_order()
    {
        $admin = $this->developer();
        $userA = User::factory()->create(['name' => 'AAA-sort-user']);
        $userZ = User::factory()->create(['name' => 'ZZZ-sort-user']);
        $first = Onboarding::factory()->create(['user_id' => $userA->id]);
        $second = Onboarding::factory()->create(['user_id' => $userZ->id]);

        $component = Livewire::actingAs($admin)->test(ListOnboardings::class)
            ->set('tableRecordsPerPage', 'all')
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
            \App\Filament\Resources\OnboardingResource\Pages\CreateOnboarding::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\OnboardingResource\Pages\EditOnboarding::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\OnboardingResource\Pages\ListOnboardings::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\OnboardingResource\Pages\CreateOnboarding::class, \App\Filament\Resources\OnboardingResource\Pages\EditOnboarding::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }
    }

    public function test_edit_tracked_file_columns_cover_guides_and_video_thumbnails(): void
    {
        $probe = new class { use \App\Filament\Traits\HandlesDroppedFiles { trackedFilePaths as public; deleteDroppedFiles as public; } };
        $tracked = (fn () => $this->trackedFileColumns())->call(new \App\Filament\Resources\OnboardingResource\Pages\EditOnboarding());
        Storage::fake('public');
        foreach (['onboarding/guides/old.pdf', 'onboarding/guides/kept.pdf', 'onboarding/video/v.mp4', 'onboarding/thumbnail/old.jpg'] as $path) {
            Storage::disk('public')->put($path, 'x');
        }
        $record = \App\Models\Onboarding::factory()->create([
            'guides' => [['url' => 'onboarding/guides/old.pdf'], ['url' => 'onboarding/guides/kept.pdf']],
            'videos' => [['url' => 'onboarding/video/v.mp4', 'thumbnail' => 'onboarding/thumbnail/old.jpg']],
        ]);
        $before = $probe->trackedFilePaths($record, $tracked);
        $this->assertEqualsCanonicalizing(['onboarding/guides/old.pdf', 'onboarding/guides/kept.pdf', 'onboarding/video/v.mp4', 'onboarding/thumbnail/old.jpg'], $before);

        $record->update([
            'guides' => [['url' => 'onboarding/guides/kept.pdf']],
            'videos' => [['url' => 'onboarding/video/v.mp4']],
        ]);
        $probe->deleteDroppedFiles($record, $before, $tracked);

        Storage::disk('public')->assertMissing('onboarding/guides/old.pdf');
        Storage::disk('public')->assertMissing('onboarding/thumbnail/old.jpg');
        Storage::disk('public')->assertExists('onboarding/guides/kept.pdf');
        Storage::disk('public')->assertExists('onboarding/video/v.mp4');
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/OnboardingResource/Schemas/OnboardingTablePresenter.php'),
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
