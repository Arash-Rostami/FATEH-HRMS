<?php

namespace Tests\Feature\Filament;

use App\Enums\MediaIcon;
use App\Filament\Pages\CreateRecord;
use App\Filament\Pages\EditRecord;
use App\Filament\Pages\ListRecords;
use App\Filament\Resources\KnowledgeProgressResource;
use App\Filament\Resources\MediaResource;
use App\Filament\Resources\MediaResource\Exports\ProgressExporter;
use App\Filament\Resources\MediaResource\Pages\CreateMedia;
use App\Filament\Resources\MediaResource\Pages\EditMedia;
use App\Filament\Resources\KnowledgeProgressResource\Pages\ListKnowledgeProgress;
use App\Filament\Resources\MediaResource\Pages\ListMedia;
use App\Filament\Resources\MediaResource\Schemas\MediaFormPresenter;
use App\Filament\Resources\MediaResource\RelationManagers\EpisodesRelationManager;
use App\Filament\Resources\ReportResource;
use App\Models\Department;
use App\Models\KnowledgeProgress;
use App\Models\Media;
use App\Models\Permission;
use App\Models\Report;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use App\Traits\Filament\HandlesActionExceptions;
use App\Traits\Filament\HandlesDroppedFiles;
use App\Traits\Filament\HandlesSaveExceptions;
use Filament\Actions\DeleteAction;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Livewire;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class MediaResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        $this->app->setLocale('fa');
        Storage::fake('public');
        ModelCacheVersion::bump(Department::class);
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        ModelCacheVersion::bump(Department::class);
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

    public function test_list_renders_and_shows_factory_media_row()
    {
        $admin = $this->developer();

        $media = Media::factory()->create(['title' => '0-List Render Probe']);

        Livewire::actingAs($admin)->test(ListMedia::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$media]);
    }

    public function test_create_media_persists_series_fields()
    {
        $admin = $this->developer();
        $author = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($admin)->test(CreateMedia::class)
            ->fillForm([
                'kind' => 'series',
                'title' => '0-Created Series Title',
                'user_id' => $author->id,
                'icon' => 'heroicon-o-video-camera',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('media', [
            'title' => '0-Created Series Title',
            'kind' => 'series',
            'user_id' => $author->id,
        ]);
    }

    public function test_icon_select_offers_media_icon_options_and_labels_a_stored_value(): void
    {
        $options = MediaFormPresenter::icon()->getOptions();

        $this->assertCount(count(MediaIcon::cases()), $options);
        $this->assertStringContainsString('<svg', $options[MediaIcon::Idea->value]);
        $this->assertStringContainsString(MediaIcon::Idea->getLabel(), $options[MediaIcon::Idea->value]);

        $media = Media::factory()->create(['icon' => MediaIcon::Teach->value]);

        Livewire::actingAs($this->developer())->test(EditMedia::class, ['record' => $media->getRouteKey()])
            ->assertSuccessful()
            ->assertSee(MediaIcon::Teach->getLabel());
    }

    public function test_create_media_requires_a_title()
    {
        $admin = $this->developer();
        $author = User::factory()->create(['status' => 'active', 'presence' => 'onsite']);

        Livewire::actingAs($admin)->test(CreateMedia::class)
            ->fillForm([
                'title' => null,
                'kind' => 'audio',
                'user_id' => $author->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['title']);
    }

    public function test_edit_media_updates_title_and_persists()
    {
        $admin = $this->developer();

        $media = Media::factory()->create(['title' => '0-Original Media Title']);

        Livewire::actingAs($admin)->test(EditMedia::class, ['record' => $media->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['title' => '0-Updated Media Title'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('0-Updated Media Title', $media->fresh()->title);
    }

    public function test_create_episode_in_relation_manager_sets_kind_user_and_parent(): void
    {
        $admin = $this->developer();

        $series = Media::factory()->create(['title' => '0-Owner Series', 'kind' => 'series']);

        Livewire::actingAs($admin)
            ->test(EpisodesRelationManager::class, [
                'ownerRecord' => $series,
                'pageClass' => EditMedia::class,
            ])
            ->callAction(TestAction::make('create')->table(), [
                'kind' => 'audio',
                'title' => '0-Child Episode',
                'active' => true,
            ])
            ->assertHasNoFormErrors();

        $episode = Media::query()->where('title', '0-Child Episode')->first();
        $this->assertNotNull($episode);
        $this->assertSame('audio', $episode->kind);
        $this->assertSame($series->id, $episode->parent_id);
        $this->assertSame($admin->id, $episode->user_id);
    }

    public function test_episodes_relation_manager_hidden_for_non_series_owners(): void
    {
        $this->assertFalse(EpisodesRelationManager::canViewForRecord(
            Media::factory()->make(['kind' => 'audio']),
            EditMedia::class,
        ));
        $this->assertFalse(EpisodesRelationManager::canViewForRecord(
            Media::factory()->make(['kind' => 'audio', 'parent_id' => 1]),
            EditMedia::class,
        ));
    }

    public function test_list_media_header_actions_include_create(): void
    {
        $admin = $this->developer();

        $page = Livewire::actingAs($admin)->test(ListMedia::class);

        $names = array_map(fn($action) => $action->getName(), $page->instance()->getCachedHeaderActions());

        $this->assertContains('create', $names);
        $this->assertContains('switch_media_resource', $names);
        $this->assertContains('switch_knowledge_progress_resource', $names);
    }

    public function test_knowledge_progress_resource_is_read_only(): void
    {
        $this->assertFalse(KnowledgeProgressResource::canCreate());
        $this->assertSame(['index'], array_keys(KnowledgeProgressResource::getPages()));
        $this->assertSame('knowledge-progress', KnowledgeProgressResource::getSlug());
    }

    public function test_episode_reorder_bumps_the_media_cache_version_once(): void
    {
        $admin = $this->developer();

        $series = Media::factory()->create(['title' => '0-Reorder Series', 'kind' => 'series']);
        $episodes = [
            Media::factory()->create(['title' => '0-Ep A', 'kind' => 'audio', 'parent_id' => $series->id]),
            Media::factory()->create(['title' => '0-Ep B', 'kind' => 'audio', 'parent_id' => $series->id]),
        ];

        ModelCacheVersion::bump(Media::class);
        $versionBefore = ModelCacheVersion::version(Media::class);

        $component = Livewire::actingAs($admin)->test(EpisodesRelationManager::class, [
            'ownerRecord' => $series,
            'pageClass' => EditMedia::class,
        ]);

        $order = [$episodes[1]->getKey(), $episodes[0]->getKey()];

        $component->call('reorderTable', $order);

        $this->assertNotSame($versionBefore, ModelCacheVersion::version(Media::class), 'the reorder must bump the Media cache version');
        $this->assertSame(
            $order,
            Media::query()->where('parent_id', $series->id)->orderBy('position')->pluck('id')->values()->all(),
        );
    }

    public function test_delete_table_action_removes_media_row()
    {
        $admin = $this->developer();

        $media = Media::factory()->create(['title' => '0-Delete Probe']);

        Livewire::actingAs($admin)->test(ListMedia::class)
            ->callTableAction(DeleteAction::class, $media);

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    public function test_kind_filter_scopes_list_to_selected_kind()
    {
        $admin = $this->developer();

        $audio = Media::factory()->create(['title' => '0-Audio Probe', 'kind' => 'audio']);
        $video = Media::factory()->create(['title' => '0-Video Probe', 'kind' => 'video']);

        Livewire::actingAs($admin)->test(ListMedia::class)
            ->assertSuccessful()
            ->filterTable('kind', 'video')
            ->assertCanSeeTableRecords([$video])
            ->assertCanNotSeeTableRecords([$audio]);
    }

    public function test_root_filter_scopes_to_episodes_or_main_items()
    {
        $admin = $this->developer();

        $series = Media::factory()->create(['title' => '0-Series Probe', 'kind' => 'series']);
        $episode = Media::factory()->create(['title' => '0-Episode Probe', 'kind' => 'audio', 'parent_id' => $series->id]);

        Livewire::actingAs($admin)->test(ListMedia::class)
            ->filterTable('parent_id', true)
            ->assertCanSeeTableRecords([$episode])
            ->assertCanNotSeeTableRecords([$series]);
    }

    public function test_active_filter_scopes_list_to_active_media_only()
    {
        $admin = $this->developer();

        $active = Media::factory()->create(['title' => '0-Active Probe', 'active' => true]);
        $inactive = Media::factory()->create(['title' => '0-Inactive Probe', 'active' => false]);

        Livewire::actingAs($admin)->test(ListMedia::class)
            ->filterTable('active', ['value' => 1])
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$inactive]);
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();
        Media::factory()->create(['title' => '0-Group Render Probe']);

        $component = Livewire::actingAs($admin)->test(ListMedia::class)->assertSuccessful();

        foreach (['active', 'department.description', 'pinned', 'kind'] as $group) {
            $component->set('tableGrouping', $group)->assertSuccessful();
        }
    }

    public function test_sort_by_kind_orders_records_ascending_and_descending()
    {
        $admin = $this->developer();
        $first = Media::factory()->create(['title' => '0-sort-kind-a', 'kind' => 'audio']);
        $second = Media::factory()->create(['title' => '0-sort-kind-b', 'kind' => 'video']);

        $component = Livewire::actingAs($admin)->test(ListMedia::class)
            ->searchTable('0-sort-kind-')
            ->sortTable('kind')
            ->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('kind', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_switch_group_contains_media_and_progress(): void
    {
        $group = $this->switchGroupOf(ReportResource::class);

        $this->assertContains(MediaResource::class, $group);
        $this->assertContains(KnowledgeProgressResource::class, $group);
    }

    private function switchGroupOf(string $resource): array
    {
        $method = new ReflectionMethod($resource, 'switchGroup');
        $method->setAccessible(true);

        return $method->invoke(null);
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            CreateMedia::class => CreateRecord::class,
            EditMedia::class => EditRecord::class,
            ListMedia::class => ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        foreach ([CreateMedia::class, EditMedia::class] as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(HandlesSaveExceptions::class, $uses);
        }

        $this->assertSame(
            \App\Filament\RelationManagers\RelationManager::class,
            get_parent_class(EpisodesRelationManager::class)
        );
    }

    public function test_edit_tracked_file_columns_delete_only_the_replaced_files(): void
    {
        $probe = new class { use HandlesDroppedFiles { trackedFilePaths as public; deleteDroppedFiles as public; } };
        $tracked = (fn () => $this->trackedFileColumns())->call(new EditMedia());
        Storage::fake('public');
        foreach (['media/covers/old.jpg', 'media/covers/new.jpg', 'media/files/kept.mp3'] as $path) {
            Storage::disk('public')->put($path, 'x');
        }
        $record = Media::factory()->create(['cover_image' => 'media/covers/old.jpg', 'file_path' => 'media/files/kept.mp3']);
        $before = $probe->trackedFilePaths($record, $tracked);
        $this->assertEqualsCanonicalizing(['media/covers/old.jpg', 'media/files/kept.mp3'], $before);

        $record->update(['cover_image' => 'media/covers/new.jpg']);
        $probe->deleteDroppedFiles($record, $before, $tracked);

        Storage::disk('public')->assertMissing('media/covers/old.jpg');
        Storage::disk('public')->assertExists('media/files/kept.mp3');
    }

    public function test_export_completed_notification_body_resolves_with_the_row_count(): void
    {
        $export = new Export();
        $export->successful_rows = 1234;

        $body = ProgressExporter::getCompletedNotificationBody($export);

        $this->assertNotSame('resources/general/strings.notifications.export_completed', $body);
        $this->assertStringContainsString('1,234', $body);
        $this->assertStringNotContainsString(':count', $body);
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/MediaResource/Schemas/MediaTablePresenter.php'),
            base_path('app/Filament/Resources/KnowledgeProgressResource/Schemas/KnowledgeProgressTablePresenter.php'),
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

    public function test_vtt_upload_replaces_the_generated_text_extension_with_vtt(): void
    {
        $file = Mockery::mock(TemporaryUploadedFile::class);
        $file->shouldReceive('extension')->andReturn('txt');
        $file->shouldReceive('getClientOriginalExtension')->andReturn('txt');

        $name = MediaFormPresenter::transcriptPath()->getUploadedFileNameForStorage($file);

        $this->assertStringEndsWith('.vtt', $name);
        $this->assertStringNotContainsString('.txt', $name);
    }

    public function test_duration_probe_is_skipped_when_the_real_path_is_not_a_usable_string(): void
    {
        $file = Mockery::mock(TemporaryUploadedFile::class);
        $file->shouldReceive('getRealPath')->andReturn(false);

        $this->assertNull(MediaFormPresenter::probedDuration($file));
        $this->assertNull(MediaFormPresenter::probedDuration(null));
    }

    public function test_switching_an_episode_to_a_series_clears_its_parent_on_save(): void
    {
        $admin = $this->developer();
        $parent = Media::factory()->create(['kind' => 'series', 'title' => '0-Parent Probe']);
        $episode = Media::factory()->create(['kind' => 'audio', 'parent_id' => $parent->id, 'title' => '0-Episode Probe']);

        Livewire::actingAs($admin)->test(EditMedia::class, ['record' => $episode->getRouteKey()])
            ->fillForm(['kind' => 'series'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($episode->fresh()->parent_id);
    }

    public function test_zero_duration_is_refused_and_a_positive_one_accepted(): void
    {
        $admin = $this->developer();
        $media = Media::factory()->create(['kind' => 'audio', 'title' => '0-Duration Probe']);

        $page = Livewire::actingAs($admin)->test(EditMedia::class, ['record' => $media->getRouteKey()]);

        $page->fillForm(['duration_seconds' => 0])->call('save')->assertHasFormErrors(['duration_seconds']);
        $page->fillForm(['duration_seconds' => 90])->call('save')->assertHasNoFormErrors();

        $this->assertSame(90, $media->fresh()->duration_seconds);
    }

    public function test_kind_select_is_disabled_only_for_a_series_with_episodes(): void
    {
        $admin = $this->developer();
        $series = Media::factory()->create(['kind' => 'series', 'title' => '0-Locked Series']);
        $empty = Media::factory()->create(['kind' => 'series', 'title' => '0-Empty Series']);
        Media::factory()->create(['kind' => 'audio', 'parent_id' => $series->id, 'title' => '0-Locked Episode']);

        Livewire::actingAs($admin)->test(EditMedia::class, ['record' => $series->getRouteKey()])
            ->assertFormFieldIsDisabled('kind');

        Livewire::actingAs($admin)->test(EditMedia::class, ['record' => $empty->getRouteKey()])
            ->assertFormFieldIsEnabled('kind');
    }

    public function test_progress_table_renders_media_and_report_titles_and_filters_completion(): void
    {
        $admin = $this->developer();
        $media = Media::factory()->create(['title' => '0-Progress Media Title']);
        $report = Report::factory()->create(['title' => '0-Progress Report Title']);
        $done = KnowledgeProgress::create(['user_id' => $admin->id, 'media_id' => $media->id, 'position' => 10, 'last_position' => 10, 'completed_at' => now(), 'last_seen_at' => now()]);
        $open = KnowledgeProgress::create(['user_id' => $admin->id, 'report_id' => $report->id, 'position' => 5, 'last_position' => 5, 'last_seen_at' => now()]);

        $page = Livewire::actingAs($admin)->test(ListKnowledgeProgress::class)
            ->assertCanSeeTableRecords([$done, $open])
            ->assertSee('0-Progress Media Title')
            ->assertSee('0-Progress Report Title');

        $page->filterTable('completed_at', true)
            ->assertCanSeeTableRecords([$done])
            ->assertCanNotSeeTableRecords([$open]);
    }
}
