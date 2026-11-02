<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\LinkResource\Pages\CreateLink;
use App\Filament\Resources\LinkResource\Pages\EditLink;
use App\Filament\Resources\LinkResource\Pages\ListLinks;
use App\Models\Link;
use App\Models\Permission;
use App\Models\User;
use Filament\Tables\Table;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LinkResourceTest extends TestCase
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

    public function test_list_renders_and_shows_factory_link_row()
    {
        $admin = $this->developer();
        $link = Link::factory()->create(['url_title' => 'Fateh Portal', 'sequence' => 0]);

        Livewire::actingAs($admin)
            ->test(ListLinks::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$link]);
    }

    public function test_deferred_table_stays_empty_until_loaded_as_the_panel_default_ships()
    {
        Table::configureUsing(fn (Table $table) => $table->deferLoading());
        $admin = $this->developer();
        $link = Link::factory()->create(['url_title' => 'Fateh Portal', 'sequence' => 0]);

        Livewire::actingAs($admin)
            ->test(ListLinks::class)
            ->assertSuccessful()
            ->assertCanNotSeeTableRecords([$link])
            ->loadTable()
            ->assertCanSeeTableRecords([$link]);
    }

    public function test_top_bar_quick_create_menu_lists_create_pages_the_user_may_use_and_exposes_the_recents_config()
    {
        $admin = $this->developer();

        $html = $this->actingAs($admin)->get('/admin/links')->getContent();

        $this->assertStringContainsString('data-popover="quick-create"', $html);
        $this->assertStringContainsString(\App\Filament\Resources\LinkResource::getUrl('create'), $html);
        $this->assertStringContainsString('window.__adminRecents', $html);
        $this->assertStringContainsString('"links"', $html);
        $this->assertStringContainsString('data-popover="recents"', $html);
    }

    public function test_top_bar_settings_buttons_live_together_in_the_utilities_row()
    {
        $admin = $this->developer();

        $html = $this->actingAs($admin)->get('/admin/links')->getContent();

        $this->assertStringContainsString('data-popover="palette"', $html);
        $this->assertSame(1, substr_count($html, 'data-icon="palette"'));
        $this->assertStringContainsString("id: 'database-notifications'", $html);
        $this->assertStringContainsString('md:w-max md:max-w-none', $html);
    }

    public function test_row_click_goes_to_the_view_modal_by_default_and_to_the_edit_page_when_the_preference_is_on()
    {
        $admin = $this->developer();
        $link = Link::factory()->create(['url_title' => 'Fateh Portal', 'sequence' => 0]);
        $table = fn () => Livewire::actingAs($admin)->test(ListLinks::class)->instance()->getTable();

        $this->assertNull($table()->getRecordUrl($link));
        $this->assertNotNull($table()->getRecordAction($link));

        $admin->setExtraValue('preferences.row_click_edit', true);
        $admin->save();

        $this->assertSame(\App\Filament\Resources\LinkResource::getUrl('edit', ['record' => $link]), $table()->getRecordUrl($link));
    }

    public function test_persist_table_state_preference_turns_on_filter_search_and_sort_memory()
    {
        $admin = $this->developer();

        $table = fn () => Livewire::actingAs($admin)->test(ListLinks::class)->instance()->getTable();

        $this->assertFalse($table()->persistsFiltersInSession());

        $admin->setExtraValue('preferences.persist_table_state', true);
        $admin->save();

        $this->assertTrue($table()->persistsFiltersInSession());
        $this->assertTrue($table()->persistsSearchInSession());
        $this->assertTrue($table()->persistsSortInSession());
    }

    public function test_create_link_persists_url_title_and_link_type()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)
            ->test(CreateLink::class)
            ->fillForm([
                'link' => 'external',
                'url' => 'https://example.com/fateh',
                'url_title' => 'Fateh Portal',
                'icon' => UploadedFile::fake()->image('icon.jpg'),
                'image' => UploadedFile::fake()->image('image.jpg'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('links', [
            'url' => 'https://example.com/fateh',
            'url_title' => 'Fateh Portal',
            'link' => 'external',
        ]);
    }

    public function test_create_link_requires_url_field()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)
            ->test(CreateLink::class)
            ->fillForm([
                'link' => 'external',
                'url' => '',
                'url_title' => 'No URL',
                'icon' => UploadedFile::fake()->image('icon.jpg'),
                'image' => UploadedFile::fake()->image('image.jpg'),
            ])
            ->call('create')
            ->assertHasFormErrors(['url' => 'required']);
    }

    public function test_edit_link_updates_url_title()
    {
        $admin = $this->developer();
        $link = Link::factory()->create([
            'url_title' => 'Old Title',
            'internal_url' => null,
            'extra' => [],
        ]);

        Livewire::actingAs($admin)
            ->test(EditLink::class, ['record' => $link->getRouteKey()])
            ->assertSuccessful()
            ->fillForm([
                'link' => $link->link,
                'url' => $link->url,
                'url_title' => 'New Title',
                'icon' => UploadedFile::fake()->image('icon.jpg'),
                'image' => UploadedFile::fake()->image('image.jpg'),
                'internal_url' => null,
                'extra' => [],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('New Title', $link->fresh()->url_title);
    }

    public function test_delete_link_action_removes_record()
    {
        $admin = $this->developer();
        $link = Link::factory()->create();

        Livewire::actingAs($admin)
            ->test(ListLinks::class)
            ->callTableAction('delete', $link);

        $this->assertDatabaseMissing('links', ['id' => $link->id]);
    }

    public function test_internal_tab_scopes_list_to_internal_links()
    {
        $admin = $this->developer();
        $internal = Link::factory()->create(['link' => 'internal', 'url_title' => 'Intranet', 'sequence' => 0]);
        $external = Link::factory()->create(['link' => 'external', 'url_title' => 'Internet', 'sequence' => 1]);

        Livewire::actingAs($admin)
            ->test(ListLinks::class)
            ->set('activeTab', 'internal')
            ->assertCanSeeTableRecords([$internal])
            ->assertCanNotSeeTableRecords([$external]);
    }

    public function test_create_link_with_extra_but_blank_internal_url_is_unenforced_because_extra_is_hidden()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)
            ->test(CreateLink::class)
            ->fillForm([
                'link' => 'external',
                'url' => 'https://example.com/fateh',
                'url_title' => 'Fateh Portal',
                'icon' => UploadedFile::fake()->image('icon.jpg'),
                'image' => UploadedFile::fake()->image('image.jpg'),
                'internal_url' => null,
                'extra' => ['10.0.0.1'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_create_link_with_internal_url_but_no_extra_fails_validation()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)
            ->test(CreateLink::class)
            ->fillForm([
                'link' => 'external',
                'url' => 'https://example.com/fateh',
                'url_title' => 'Fateh Portal',
                'icon' => UploadedFile::fake()->image('icon.jpg'),
                'image' => UploadedFile::fake()->image('image.jpg'),
                'internal_url' => 'https://intranet.fateh.local',
                'extra' => [],
            ])
            ->call('create')
            ->assertHasFormErrors(['internal_url']);
    }

    public function test_create_link_with_both_internal_url_and_extra_saves_without_error()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)
            ->test(CreateLink::class)
            ->fillForm([
                'link' => 'external',
                'url' => 'https://example.com/fateh',
                'url_title' => 'Fateh Portal',
                'icon' => UploadedFile::fake()->image('icon.jpg'),
                'image' => UploadedFile::fake()->image('image.jpg'),
                'internal_url' => 'https://intranet.fateh.local',
                'extra' => ['10.0.0.1'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('links', ['internal_url' => 'https://intranet.fateh.local']);
    }

    public function test_smart_routing_filter_scopes_to_links_with_internal_url()
    {
        $admin = $this->developer();
        $routed = Link::factory()->create([
            'internal_url' => 'https://intranet.fateh.local',
            'extra' => ['10.0.0.1'],
        ]);
        $plain = Link::factory()->create(['internal_url' => null, 'extra' => []]);

        Livewire::actingAs($admin)
            ->test(ListLinks::class)
            ->filterTable('smart_routing', ['value' => '1'])
            ->assertCanSeeTableRecords([$routed])
            ->assertCanNotSeeTableRecords([$plain]);
    }

    public function test_create_link_persists_picked_icon_from_curated_select()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)
            ->test(CreateLink::class)
            ->fillForm([
                'link' => 'external',
                'url' => 'https://example.com/fateh',
                'url_title' => 'Fateh Portal',
                'icon_description' => \App\Enums\LinkIcon::Hub->value,
                'icon' => UploadedFile::fake()->image('icon.jpg'),
                'image' => UploadedFile::fake()->image('image.jpg'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('links', [
            'url_title' => 'Fateh Portal',
            'icon_description' => \App\Enums\LinkIcon::Hub->value,
        ]);
    }

    public function test_edit_link_keeps_legacy_icon_description_value_editable_when_not_in_curated_list()
    {
        $admin = $this->developer();
        $link = Link::factory()->create(['icon_description' => 'legacy free-typed sentence']);

        Livewire::actingAs($admin)
            ->test(EditLink::class, ['record' => $link->getRouteKey()])
            ->assertFormSet(['icon_description' => 'legacy free-typed sentence']);
    }

    public function test_link_type_filter_scopes_list_to_matching_type()
    {
        $admin = $this->developer();
        $internal = Link::factory()->create(['link' => 'internal', 'url_title' => 'Internal Link', 'sequence' => 0]);
        $external = Link::factory()->create(['link' => 'external', 'url_title' => 'External Link', 'sequence' => 1]);

        Livewire::actingAs($admin)
            ->test(ListLinks::class)
            ->filterTable('link', 'internal')
            ->assertCanSeeTableRecords([$internal])
            ->assertCanNotSeeTableRecords([$external]);
    }

    public function test_sorting_by_link_type_sequence_and_url_title_orders_records_correctly()
    {
        $admin = $this->developer();
        $first = Link::factory()->create(['link' => 'external', 'url_title' => '0-AAA-sort-link', 'sequence' => 1]);
        $second = Link::factory()->create(['link' => 'internal', 'url_title' => '0-ZZZ-sort-link', 'sequence' => 9]);

        $component = Livewire::actingAs($admin)->test(ListLinks::class)
            ->set('tableRecordsPerPage', 1000)
            ->sortTable('link')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('link', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('sequence')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('sequence', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('url_title')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('url_title', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();
        Link::factory()->create(['url_title' => 'Group Render Probe', 'sequence' => 0]);

        Livewire::actingAs($admin)
            ->test(ListLinks::class)
            ->assertSuccessful()
            ->set('tableGrouping', 'link')
            ->assertSuccessful();
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\LinkResource\Pages\CreateLink::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\LinkResource\Pages\EditLink::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\LinkResource\Pages\ListLinks::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\LinkResource\Pages\CreateLink::class, \App\Filament\Resources\LinkResource\Pages\EditLink::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }
    }

    public function test_edit_tracked_file_columns_delete_only_the_replaced_files(): void
    {
        $probe = new class { use \App\Filament\Traits\HandlesDroppedFiles { trackedFilePaths as public; deleteDroppedFiles as public; } };
        $tracked = (fn () => $this->trackedFileColumns())->call(new \App\Filament\Resources\LinkResource\Pages\EditLink());
        Storage::fake('public');
        foreach (['link/icon/old.png', 'link/icon/new.png', 'link/image/kept.jpg'] as $path) {
            Storage::disk('public')->put($path, 'x');
        }
        $record = \App\Models\Link::factory()->create(['icon' => 'link/icon/old.png', 'image' => 'link/image/kept.jpg']);
        $before = $probe->trackedFilePaths($record, $tracked);
        $this->assertEqualsCanonicalizing(['link/icon/old.png', 'link/image/kept.jpg'], $before);

        $record->update(['icon' => 'link/icon/new.png']);
        $probe->deleteDroppedFiles($record, $before, $tracked);

        Storage::disk('public')->assertMissing('link/icon/old.png');
        Storage::disk('public')->assertExists('link/image/kept.jpg');
        Storage::disk('public')->assertExists('link/icon/new.png');
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/LinkResource/Schemas/LinkTablePresenter.php'),
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
