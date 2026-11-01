<?php

namespace Tests\Feature\Livewire\Dashboard;

use App\Livewire\Dashboard\Dms\Main;
use App\Livewire\Dashboard\Dms\Presentation\DmsPresenter;
use App\Models\DMS;
use App\Models\Department;
use App\Models\Profile;
use App\Models\Read;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class DmsTest extends TestCase
{
    private function cleanSlate(): void
    {
        DMS::query()->delete();
    }

    private function deptUser(string $dept, string $position): User
    {
        Department::firstOrCreate(['code' => $dept], ['name' => $dept]);
        $user = User::factory()->create(['status' => 'active', 'presence' => 'remote']);
        Profile::factory()->create([
            'user_id' => $user->id,
            'department_id' => $dept,
            'position' => $position,
        ]);

        return $user;
    }

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

    public function test_dms_module_crud_lifecycle()
    {
        $reader = $this->deptUser('PS', 'expert');

        $systematic = DMS::factory()->create([
            'status' => 'live',
            'type' => true,
            'owners' => ['ALL'],
            'users' => [],
            'extra' => [],
            'tags' => [],
        ]);
        DMS::factory()->create([
            'status' => 'live',
            'type' => false,
            'owners' => ['ALL'],
            'users' => [],
            'extra' => [],
            'tags' => [],
        ]);

        Livewire::actingAs($reader)
            ->test(Main::class)
            ->assertHasNoErrors();

        Livewire::actingAs($reader)
            ->test(Main::class)
            ->call('confirmRead', $systematic->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('reads', [
            'document_id' => $systematic->id,
            'user_id' => $reader->id,
            'read' => 1,
        ]);

        Livewire::actingAs($reader)
            ->test(Main::class)
            ->call('incrementRead', $systematic->id)
            ->assertHasNoErrors();

        $this->assertSame(1, (int) $systematic->fresh()->combined_read_count);
        $this->assertSame(1, (int) Read::where('document_id', $systematic->id)->where('user_id', $reader->id)->value('read_count'));

        Livewire::actingAs($reader)
            ->test(Main::class)
            ->call('switchTab', 'non-systematic')
            ->assertHasNoErrors()
            ->assertSet('activeTab', 'non-systematic');
    }

    public function test_read_count_caption_shows_after_reading_and_stays_hidden_before(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [], 'extra' => [], 'tags' => [],
            'title' => 'Read count probe ' . uniqid(),
        ]);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', $doc->title)
            ->assertDontSeeHtml('mt-1.5 text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-70">بازدید شما');

        Livewire::actingAs($reader)->test(Main::class)->call('confirmRead', $doc->id);
        Livewire::actingAs($reader)->test(Main::class)->call('incrementRead', $doc->id);
        Livewire::actingAs($reader)->test(Main::class)->call('incrementRead', $doc->id);

        Livewire::withoutLazyLoading();
        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', $doc->title)
            ->assertSeeHtml('بازدید شما: ۲ بار');
    }

    public function test_read_counts_resolve_from_a_single_query_not_one_per_computed(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [], 'extra' => [], 'tags' => [],
        ]);
        Livewire::actingAs($reader)->test(Main::class)->call('confirmRead', $doc->id);

        DB::enableQueryLog();
        $component = Livewire::actingAs($reader)->test(Main::class)->instance();
        $component->confirmedDocs;
        $component->readDocs;
        $component->readCounts;
        $readsQueries = collect(DB::getQueryLog())->filter(fn($q) => str_contains($q['query'], '`reads`'));
        DB::disableQueryLog();

        $this->assertCount(1, $readsQueries, 'confirmedDocs/readDocs/readCounts must share one query against `reads`, not one each');
    }

    public function test_type_filter_narrows_docs_to_matching_type()
    {
        $reader = $this->deptUser('PS', 'expert');
        $typeA = 'نوع-' . uniqid();
        $typeB = 'نوع-' . uniqid();

        $matching = DMS::factory()->create([
            'status' => 'live',
            'type' => true,
            'owners' => ['ALL'],
            'users' => [],
            'extra' => ['type' => $typeA],
            'tags' => [],
        ]);
        DMS::factory()->create([
            'status' => 'live',
            'type' => true,
            'owners' => ['ALL'],
            'users' => [],
            'extra' => ['type' => $typeB],
            'tags' => [],
        ]);

        Livewire::actingAs($reader)
            ->test(Main::class)
            ->set('activeFilter', "type|{$typeA}")
            ->assertHasNoErrors()
            ->assertSet('docIds', [$matching->id]);
    }

    public function test_tag_filter_narrows_docs_to_matching_tag()
    {
        $reader = $this->deptUser('PS', 'expert');
        $key = 'برچسب-' . uniqid();
        $valueA = 'مقدار-' . uniqid();
        $valueB = 'مقدار-' . uniqid();

        $matching = DMS::factory()->create([
            'status' => 'live',
            'type' => true,
            'owners' => ['ALL'],
            'users' => [],
            'extra' => [],
            'tags' => [$key => $valueA],
        ]);
        DMS::factory()->create([
            'status' => 'live',
            'type' => true,
            'owners' => ['ALL'],
            'users' => [],
            'extra' => [],
            'tags' => [$key => $valueB],
        ]);

        Livewire::actingAs($reader)
            ->test(Main::class)
            ->set('activeFilter', "{$key}|{$valueA}")
            ->assertHasNoErrors()
            ->assertSet('docIds', [$matching->id]);
    }

    public function test_clearing_filter_restores_all_docs()
    {
        $reader = $this->deptUser('PS', 'expert');
        $typeA = 'نوع-' . uniqid();
        $typeB = 'نوع-' . uniqid();

        DMS::factory()->create([
            'status' => 'live',
            'type' => true,
            'owners' => ['ALL'],
            'users' => [],
            'extra' => ['type' => $typeA],
            'tags' => [],
        ]);
        DMS::factory()->create([
            'status' => 'live',
            'type' => true,
            'owners' => ['ALL'],
            'users' => [],
            'extra' => ['type' => $typeB],
            'tags' => [],
        ]);

        $component = Livewire::actingAs($reader)
            ->test(Main::class)
            ->set('activeFilter', "type|{$typeA}")
            ->assertCount('docIds', 1);

        $component->set('activeFilter', 'all')
            ->assertHasNoErrors();

        $this->assertGreaterThan(1, count($component->get('docIds')));
    }

    public function test_filter_buttons_bind_to_livewire_scope_not_bare_alpine_variable()
    {
        $reader = $this->deptUser('PS', 'expert');

        DMS::factory()->create([
            'status' => 'live',
            'type' => true,
            'owners' => ['ALL'],
            'users' => [],
            'extra' => ['type' => 'دستورالعمل'],
            'tags' => [],
        ]);

        Livewire::withoutLazyLoading();

        $html = Livewire::actingAs($reader)
            ->test(Main::class)
            ->assertHasNoErrors()
            ->html();

        $this->assertStringContainsString(":class=\"\$wire.activeFilter === 'type|دستورالعمل'", $html);
        $this->assertStringNotContainsString(":class=\"activeFilter === 'type|دستورالعمل'", $html);
    }

    public function test_updating_extra_files_persists_and_preserves_read_spine()
    {
        $reader = $this->deptUser('PS', 'expert');

        $doc = DMS::factory()->create([
            'status' => 'live',
            'type' => true,
            'owners' => ['ALL'],
            'users' => [],
            'extra' => [],
            'tags' => [],
            'extra_files' => [],
        ]);

        Livewire::actingAs($reader)
            ->test(Main::class)
            ->call('confirmRead', $doc->id)
            ->assertHasNoErrors();

        Livewire::actingAs($reader)
            ->test(Main::class)
            ->call('incrementRead', $doc->id)
            ->assertHasNoErrors();

        $this->assertSame(1, (int) $doc->fresh()->combined_read_count);

        $this->assertSame([['path' => $doc->file, 'ext' => 'pdf', 'kind' => 'pdf', 'n' => 1, 'of' => 1]], $doc->fresh()->renditions());

        $doc->fresh()->update([
            'extra_files' => ['dms/word-copy.docx', 'dms/excel-copy.xlsx'],
        ]);

        $fresh = $doc->fresh();

        $this->assertSame(['dms/word-copy.docx', 'dms/excel-copy.xlsx'], $fresh->extra_files);

        $renditions = $fresh->renditions();
        $this->assertCount(3, $renditions);
        $this->assertSame('pdf', $renditions[0]['ext']);
        $this->assertSame('docx', $renditions[1]['ext']);
        $this->assertSame('xlsx', $renditions[2]['ext']);

        $this->assertSame(1, (int) $fresh->combined_read_count, 'extra_files update must not reset combined_read_count');
        $this->assertSame(1, (int) Read::where('document_id', $doc->id)->where('user_id', $reader->id)->value('read_count'), 'extra_files update must not reset read_count');
        $this->assertSame(1, (int) Read::where('document_id', $doc->id)->where('user_id', $reader->id)->value('read'), 'extra_files update must not mark unread');

        $fresh->update(['file' => 'dms/replaced.pdf']);

        $this->assertSame(0, (int) $fresh->fresh()->combined_read_count, 'primary file change must reset combined_read_count');
        $this->assertSame(0, (int) Read::where('document_id', $doc->id)->where('user_id', $reader->id)->value('read_count'), 'primary file change must reset read_count');
        $this->assertSame(0, (int) Read::where('document_id', $doc->id)->where('user_id', $reader->id)->value('read'), 'primary file change must mark unread');
    }

    public function test_visibility_scope_all_owners_shows_doc_to_every_user(): void
    {
        $t = 'T-' . uniqid();
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true,
            'owners' => ['ALL'], 'users' => [], 'extra' => ['type' => $t], 'tags' => [],
        ]);
        $psUser = $this->deptUser('PS', 'expert');
        $itUser = $this->deptUser('IT', 'expert');

        Livewire::actingAs($psUser)->test(Main::class)
            ->set('activeFilter', "type|{$t}")
            ->assertSet('docIds', [$doc->id]);

        Livewire::actingAs($itUser)->test(Main::class)
            ->set('activeFilter', "type|{$t}")
            ->assertSet('docIds', [$doc->id]);
    }

    public function test_visibility_scope_department_owners_only_visible_to_same_dept_user(): void
    {
        $t = 'T-' . uniqid();
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true,
            'owners' => ['PS'], 'users' => [], 'extra' => ['type' => $t], 'tags' => [],
        ]);
        $psUser = $this->deptUser('PS', 'expert');
        $itUser = $this->deptUser('IT', 'expert');

        Livewire::actingAs($psUser)->test(Main::class)
            ->set('activeFilter', "type|{$t}")
            ->assertSet('docIds', [$doc->id]);

        Livewire::actingAs($itUser)->test(Main::class)
            ->set('activeFilter', "type|{$t}")
            ->assertSet('docIds', []);
    }

    public function test_visibility_scope_explicit_users_list_grants_visibility_to_listed_user(): void
    {
        $t = 'T-' . uniqid();
        $owner = $this->deptUser('PS', 'expert');
        $other = $this->deptUser('IT', 'expert');
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true,
            'owners' => [], 'users' => [(string) $owner->id],
            'extra' => ['type' => $t], 'tags' => [],
        ]);

        Livewire::actingAs($owner)->test(Main::class)
            ->set('activeFilter', "type|{$t}")
            ->assertSet('docIds', [$doc->id]);

        Livewire::actingAs($other)->test(Main::class)
            ->set('activeFilter', "type|{$t}")
            ->assertSet('docIds', []);
    }

    public function test_visibility_scope_excludes_non_live_status_docs(): void
    {
        $t = 'T-' . uniqid();
        $live = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['type' => $t], 'tags' => [],
        ]);
        DMS::factory()->create([
            'status' => 'under_review', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['type' => $t], 'tags' => [],
        ]);
        DMS::factory()->create([
            'status' => 'obsolete', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['type' => $t], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::actingAs($reader)->test(Main::class)
            ->set('activeFilter', "type|{$t}")
            ->assertSet('docIds', [$live->id]);
    }

    public function test_confirm_read_on_invisible_doc_is_silently_refused(): void
    {
        $t = 'T-' . uniqid();
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true,
            'owners' => ['ZZ'], 'users' => [], 'extra' => ['type' => $t], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::actingAs($reader)->test(Main::class)
            ->call('confirmRead', $doc->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('reads', [
            'document_id' => $doc->id,
            'user_id' => $reader->id,
        ]);
    }

    public function test_systematic_tab_only_includes_type_true_docs(): void
    {
        $sysType = 'SY-' . uniqid();
        $nonSysType = 'NS-' . uniqid();
        $sys = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['type' => $sysType], 'tags' => [],
        ]);
        DMS::factory()->create([
            'status' => 'live', 'type' => false, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['type' => $nonSysType], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::actingAs($reader)->test(Main::class)
            ->set('activeFilter', "type|{$sysType}")
            ->assertSet('docIds', [$sys->id])
            ->set('activeFilter', "type|{$nonSysType}")
            ->assertSet('docIds', []);
    }

    public function test_non_systematic_tab_only_includes_type_false_docs(): void
    {
        $sysType = 'SY-' . uniqid();
        $nonSysType = 'NS-' . uniqid();
        $nonSys = DMS::factory()->create([
            'status' => 'live', 'type' => false, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['type' => $nonSysType], 'tags' => [],
        ]);
        DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['type' => $sysType], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::actingAs($reader)->test(Main::class)
            ->call('switchTab', 'non_systematic')
            ->set('activeFilter', "type|{$nonSysType}")
            ->assertSet('docIds', [$nonSys->id])
            ->set('activeFilter', "type|{$sysType}")
            ->assertSet('docIds', []);
    }

    public function test_switch_tab_resets_search_and_active_filter_and_reloads(): void
    {
        $this->cleanSlate();
        $sysType = 'SY-' . uniqid();
        $nonSysType = 'NS-' . uniqid();
        DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['type' => $sysType], 'tags' => [],
        ]);
        $nonSys = DMS::factory()->create([
            'status' => 'live', 'type' => false, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['type' => $nonSysType], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', 'zzz')
            ->set('activeFilter', "type|{$sysType}")
            ->call('switchTab', 'non_systematic')
            ->assertSet('activeTab', 'non_systematic')
            ->assertSet('search', '')
            ->assertSet('activeFilter', 'all')
            ->assertSet('docIds', [$nonSys->id]);
    }

    public function test_search_filters_by_title(): void
    {
        $title = 'UniqueTitle-' . uniqid();
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'title' => $title, 'extra' => [], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', $title)
            ->assertSet('docIds', [$doc->id]);
    }

    public function test_search_filters_by_code(): void
    {
        $code = 'UNIQCODE-' . uniqid();
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'code' => $code, 'extra' => [], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', $code)
            ->assertSet('docIds', [$doc->id]);
    }

    public function test_search_filters_by_revision(): void
    {
        $revision = 'UniqueRevision-' . uniqid();
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'revision' => $revision, 'extra' => [], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', $revision)
            ->assertSet('docIds', [$doc->id]);
    }

    public function test_search_with_literal_percent_and_underscore_does_not_wildcard_match(): void
    {
        $uid = uniqid();
        $exact = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'title' => "50%_{$uid}", 'extra' => [], 'tags' => [],
        ]);
        DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'title' => "50X{$uid}Y", 'extra' => [], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', "50%_{$uid}")
            ->assertSet('docIds', [$exact->id]);
    }

    public function test_search_filters_by_persian_extra_value(): void
    {
        $needle = 'رضایی' . uniqid();
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['notes' => "بازبینی توسط {$needle}"], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', $needle)
            ->assertSet('docIds', [$doc->id]);
    }

    public function test_search_filters_by_arbitrary_extra_value(): void
    {
        $needle = 'UniqueStakeholder-' . uniqid();
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['notes' => "Reviewed by {$needle}"], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', $needle)
            ->assertSet('docIds', [$doc->id]);
    }

    public function test_search_filters_by_arbitrary_tags_value(): void
    {
        $needle = 'UniqueTag-' . uniqid();
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => ['label' => $needle],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', $needle)
            ->assertSet('docIds', [$doc->id]);
    }

    public function test_sort_prioritizes_unreceived_then_received_unread_then_read(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        $tag = 'SortTest-' . uniqid();

        $unreceived = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'title' => 'Unreceived', 'extra' => ['category' => $tag], 'tags' => [],
            'updated_at' => now()->subMinutes(5),
        ]);
        $receivedNotRead = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'title' => 'ReceivedNotRead', 'extra' => ['category' => $tag], 'tags' => [],
            'updated_at' => now()->subMinutes(10),
        ]);
        $read = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'title' => 'FullyRead', 'extra' => ['category' => $tag], 'tags' => [],
            'updated_at' => now()->subMinute(),
        ]);

        Read::create(['document_id' => $receivedNotRead->id, 'user_id' => $reader->id, 'read' => true, 'read_count' => 0]);
        Read::create(['document_id' => $read->id, 'user_id' => $reader->id, 'read' => true, 'read_count' => 3]);

        $docIds = Livewire::actingAs($reader)->test(Main::class)
            ->set('search', $tag)
            ->get('docIds');

        $this->assertSame([$unreceived->id, $receivedNotRead->id, $read->id], $docIds);
    }

    public function test_sort_within_same_read_state_falls_back_to_most_recently_updated(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        $tag = 'SortTest-' . uniqid();

        $older = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'title' => 'Older', 'extra' => ['category' => $tag], 'tags' => [],
            'updated_at' => now()->subMinutes(10),
        ]);
        $newer = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'title' => 'Newer', 'extra' => ['category' => $tag], 'tags' => [],
            'updated_at' => now()->subMinute(),
        ]);

        $docIds = Livewire::actingAs($reader)->test(Main::class)
            ->set('search', $tag)
            ->get('docIds');

        $this->assertSame([$newer->id, $older->id], $docIds);
    }

    public function test_search_with_falsy_zero_string_filters_to_matching_doc_only(): void
    {
        $this->cleanSlate();
        DMS::factory()->create([
            'status' => 'live', 'type' => false, 'owners' => ['ALL'], 'users' => [],
            'title' => 'Has0Match', 'code' => 'CD0', 'version' => '1.0',
            'extra' => [], 'tags' => [],
        ]);
        DMS::factory()->create([
            'status' => 'live', 'type' => false, 'owners' => ['ALL'], 'users' => [],
            'title' => 'NoZeroHere', 'code' => 'CDX', 'version' => '1.1',
            'extra' => [], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::actingAs($reader)->test(Main::class)
            ->call('switchTab', 'non_systematic')
            ->assertCount('docIds', 2)
            ->set('search', '0')
            ->assertCount('docIds', 1);
    }

    public function test_load_more_appends_next_page_and_flips_has_more_pages_false_at_end(): void
    {
        $this->cleanSlate();
        DMS::factory()->count(12)->create([
            'status' => 'live', 'type' => false, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::actingAs($reader)->test(Main::class)
            ->call('switchTab', 'non_systematic')
            ->assertCount('docIds', 10)
            ->assertSet('hasMorePages', true)
            ->call('loadMore')
            ->assertCount('docIds', 12)
            ->call('loadMore')
            ->assertSet('hasMorePages', false);
    }

    public function test_load_more_is_noop_when_has_more_pages_false(): void
    {
        $this->cleanSlate();
        DMS::factory()->count(5)->create([
            'status' => 'live', 'type' => false, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::actingAs($reader)->test(Main::class)
            ->call('switchTab', 'non_systematic')
            ->assertSet('hasMorePages', false)
            ->assertCount('docIds', 5)
            ->call('loadMore')
            ->assertCount('docIds', 5)
            ->assertSet('hasMorePages', false);
    }

    public function test_load_more_flips_has_more_pages_false_on_an_exact_batch_boundary(): void
    {
        $this->cleanSlate();
        DMS::factory()->count(20)->create([
            'status' => 'live', 'type' => false, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::actingAs($reader)->test(Main::class)
            ->call('switchTab', 'non_systematic')
            ->assertCount('docIds', 10)
            ->assertSet('hasMorePages', true)
            ->call('loadMore')
            ->assertCount('docIds', 20)
            ->assertSet('hasMorePages', false);
    }

    public function test_mount_with_open_query_param_focuses_single_visible_doc(): void
    {
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::withoutLazyLoading();

        Livewire::actingAs($reader)->test(Main::class, ['open' => $doc->id])
            ->assertSet('docIds', [$doc->id])
            ->assertSet('hasMorePages', false);
    }

    public function test_mount_with_open_for_non_visible_doc_falls_back_to_initial_load(): void
    {
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ZZ'], 'users' => [],
            'extra' => [], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::withoutLazyLoading();

        $component = Livewire::actingAs($reader)->test(Main::class, ['open' => $doc->id]);

        $this->assertNull($component->get('open'));
        $this->assertNotContains($doc->id, $component->get('docIds'));
    }

    public function test_clear_focus_restores_initial_doc_load(): void
    {
        DMS::factory()->count(11)->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::actingAs($reader)->test(Main::class, ['open' => DMS::latest('id')->value('id')])
            ->call('clearFocus')
            ->assertSet('open', null)
            ->assertCount('docIds', 10)
            ->assertSet('hasMorePages', true);
    }

    public function test_confirm_read_is_idempotent_and_does_not_duplicate_read_row(): void
    {
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::actingAs($reader)->test(Main::class)
            ->call('confirmRead', $doc->id)
            ->assertHasNoErrors();

        Livewire::actingAs($reader)->test(Main::class)
            ->call('confirmRead', $doc->id)
            ->assertHasNoErrors();

        $this->assertSame(1, Read::where('document_id', $doc->id)->where('user_id', $reader->id)->count());
        $this->assertSame(1, (int) Read::where('document_id', $doc->id)->where('user_id', $reader->id)->value('read'));
        $this->assertSame(0, (int) Read::where('document_id', $doc->id)->where('user_id', $reader->id)->value('read_count'));
    }

    public function test_increment_read_advances_read_count_and_combined_read_count(): void
    {
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::actingAs($reader)->test(Main::class)
            ->call('incrementRead', $doc->id)
            ->call('incrementRead', $doc->id)
            ->call('incrementRead', $doc->id)
            ->assertHasNoErrors();

        $this->assertSame(3, (int) Read::where('document_id', $doc->id)->where('user_id', $reader->id)->value('read_count'));
        $this->assertSame(3, (int) $doc->fresh()->combined_read_count);
    }

    public function test_on_confirmation_confirmed_event_marks_doc_read(): void
    {
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::actingAs($reader)->test(Main::class)
            ->call('onConfirmationConfirmed', 'confirmRead', $doc->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('reads', [
            'document_id' => $doc->id,
            'user_id' => $reader->id,
            'read' => 1,
        ]);
    }

    public function test_revision_change_resets_reads_and_combined_read_count(): void
    {
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => [], 'revision' => 'original',
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::actingAs($reader)->test(Main::class)
            ->call('incrementRead', $doc->id)
            ->assertHasNoErrors();

        $this->assertSame(1, (int) $doc->fresh()->combined_read_count);
        $this->assertSame(1, (int) Read::where('document_id', $doc->id)->where('user_id', $reader->id)->value('read_count'));

        $fresh = $doc->fresh();
        $fresh->update(['revision' => 'revised text']);

        $this->assertSame(0, (int) $fresh->fresh()->combined_read_count, 'revision change must reset combined_read_count');
        $this->assertSame(0, (int) Read::where('document_id', $doc->id)->where('user_id', $reader->id)->value('read_count'), 'revision change must reset read_count');
        $this->assertSame(0, (int) Read::where('document_id', $doc->id)->where('user_id', $reader->id)->value('read'), 'revision change must mark unread');
    }

    public function test_receive_pending_count_counts_unconfirmed_docs_in_tab(): void
    {
        $this->cleanSlate();
        $docs = DMS::factory()->count(3)->create([
            'status' => 'live', 'type' => false, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::actingAs($reader)->test(Main::class)
            ->call('switchTab', 'non_systematic')
            ->assertSet('receivePendingCount', 3)
            ->call('confirmRead', $docs->first()->id)
            ->assertSet('receivePendingCount', 2);
    }

    public function test_read_pending_count_counts_confirmed_but_unread_docs_in_tab(): void
    {
        $docs = DMS::factory()->count(3)->create([
            'status' => 'live', 'type' => false, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::actingAs($reader)->test(Main::class)
            ->call('switchTab', 'non_systematic')
            ->call('confirmRead', $docs->first()->id)
            ->assertSet('readPendingCount', 1)
            ->call('incrementRead', $docs->first()->id)
            ->assertSet('readPendingCount', 0);
    }

    public function test_docs_computed_preserves_doc_ids_order(): void
    {
        DMS::factory()->count(3)->create([
            'status' => 'live', 'type' => false, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        $component = Livewire::actingAs($reader)->test(Main::class)
            ->call('switchTab', 'non_systematic');

        $ids = $component->get('docIds');
        $ordered = $component->get('docs')->pluck('id')->toArray();

        $this->assertSame($ids, $ordered);
    }

    public function test_filter_groups_aggregates_extra_type_and_tag_values(): void
    {
        DMS::factory()->create([
            'status' => 'live', 'type' => false, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['type' => 'A'], 'tags' => ['cat' => ['x', 'y']],
        ]);
        DMS::factory()->create([
            'status' => 'live', 'type' => false, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['type' => 'B'], 'tags' => ['cat' => ['z']],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        $groups = Livewire::actingAs($reader)->test(Main::class)
            ->call('switchTab', 'non_systematic')
            ->get('filterGroups');

        $this->assertEqualsCanonicalizing(['A', 'B'], $groups['type'] ?? []);
        $this->assertEqualsCanonicalizing(['x', 'y', 'z'], $groups['cat'] ?? []);
    }

    public function test_filter_group_label_returns_label_for_type_and_raw_key_otherwise(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        $component = Livewire::actingAs($reader)->test(Main::class);

        $this->assertSame('دسته بندی', $component->instance()->filterGroupLabel('type'));
        $this->assertSame('customtag', $component->instance()->filterGroupLabel('customtag'));
    }

    public function test_tag_filter_matches_array_valued_tag(): void
    {
        $key = 'برچسب-' . uniqid();
        $reader = $this->deptUser('PS', 'expert');

        $matching = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => [$key => ['x', 'y']],
        ]);
        DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => [$key => ['z']],
        ]);

        Livewire::actingAs($reader)->test(Main::class)
            ->set('activeFilter', "{$key}|y")
            ->assertSet('docIds', [$matching->id]);
    }

    public function test_empty_state_when_no_docs_match_filter_shows_zero_doc_ids(): void
    {
        $reader = $this->deptUser('PS', 'expert');

        Livewire::actingAs($reader)->test(Main::class)
            ->set('activeFilter', 'type|' . uniqid())
            ->assertSet('docIds', [])
            ->assertSet('hasMorePages', false)
            ->assertSeeHtml('wire:key="dms-empty-none"');
    }

    public function test_sort_by_title_orders_docs_alphabetically_within_same_read_state(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        $tag = 'TitleSort-' . uniqid();

        $beta = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'title' => "Beta-{$tag}", 'extra' => ['category' => $tag], 'tags' => [],
        ]);
        $alpha = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'title' => "Alpha-{$tag}", 'extra' => ['category' => $tag], 'tags' => [],
        ]);
        $gamma = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'title' => "Gamma-{$tag}", 'extra' => ['category' => $tag], 'tags' => [],
        ]);

        $docIds = Livewire::actingAs($reader)->test(Main::class)
            ->set('search', $tag)
            ->call('sortBy', 'title')
            ->get('docIds');

        $this->assertSame([$alpha->id, $beta->id, $gamma->id], $docIds);
    }

    public function test_sort_by_same_column_toggles_direction(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        $tag = 'DirToggle-' . uniqid();

        $beta = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'title' => "Beta-{$tag}", 'extra' => ['category' => $tag], 'tags' => [],
        ]);
        $alpha = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'title' => "Alpha-{$tag}", 'extra' => ['category' => $tag], 'tags' => [],
        ]);
        $gamma = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'title' => "Gamma-{$tag}", 'extra' => ['category' => $tag], 'tags' => [],
        ]);

        $asc = Livewire::actingAs($reader)->test(Main::class)
            ->set('search', $tag)
            ->call('sortBy', 'title')
            ->assertSet('sort', 'title')
            ->assertSet('sortDir', 'asc')
            ->get('docIds');

        $this->assertSame([$alpha->id, $beta->id, $gamma->id], $asc);

        $desc = Livewire::actingAs($reader)->test(Main::class)
            ->set('search', $tag)
            ->call('sortBy', 'title')
            ->call('sortBy', 'title')
            ->assertSet('sortDir', 'desc')
            ->get('docIds');

        $this->assertSame([$gamma->id, $beta->id, $alpha->id], $desc);
    }

    public function test_sort_by_new_column_resets_direction_to_asc(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        $tag = 'NewCol-' . uniqid();

        DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'code' => "ZZ-{$tag}", 'extra' => ['category' => $tag], 'tags' => [],
        ]);
        DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'code' => "AA-{$tag}", 'extra' => ['category' => $tag], 'tags' => [],
        ]);

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', $tag)
            ->call('sortBy', 'title')
            ->assertSet('sort', 'title')
            ->assertSet('sortDir', 'asc')
            ->call('sortBy', 'code')
            ->assertSet('sort', 'code')
            ->assertSet('sortDir', 'asc');
    }

    public function test_sort_by_unwhitelisted_column_is_silent_noop(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => [],
        ]);

        Livewire::actingAs($reader)->test(Main::class)
            ->call('sortBy', "title'; DROP TABLE dms; --")
            ->assertSet('sort', 'updated')
            ->assertSet('sortDir', 'desc')
            ->assertHasNoErrors();

        $this->assertGreaterThan(0, DMS::count());
    }

    public function test_reset_sort_restores_default_and_reloads(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        $tag = 'Reset-' . uniqid();

        $older = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'title' => "Z-{$tag}", 'extra' => ['category' => $tag], 'tags' => [],
            'updated_at' => now()->subMinutes(10),
        ]);
        $newer = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'title' => "A-{$tag}", 'extra' => ['category' => $tag], 'tags' => [],
            'updated_at' => now()->subMinute(),
        ]);

        $docIds = Livewire::actingAs($reader)->test(Main::class)
            ->set('search', $tag)
            ->call('sortBy', 'title')
            ->assertSet('sort', 'title')
            ->assertSet('sortDir', 'asc')
            ->call('resetSort')
            ->assertSet('sort', 'updated')
            ->assertSet('sortDir', 'desc')
            ->get('docIds');

        $this->assertSame([$newer->id, $older->id], $docIds);
    }

    public function test_reset_sort_is_noop_when_already_default(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => [],
        ]);

        Livewire::actingAs($reader)->test(Main::class)
            ->call('resetSort')
            ->assertSet('sort', 'updated')
            ->assertSet('sortDir', 'desc')
            ->assertHasNoErrors();
    }

    public function test_toggle_pending_filter_receive_isolates_unconfirmed_docs(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        $tag = 'PendRcv-' . uniqid();

        $unconfirmed = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['category' => $tag], 'tags' => [],
        ]);
        $confirmed = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['category' => $tag], 'tags' => [],
        ]);
        Read::create(['document_id' => $confirmed->id, 'user_id' => $reader->id, 'read' => true, 'read_count' => 0]);

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', $tag)
            ->assertCount('docIds', 2)
            ->call('togglePendingFilter', 'receive')
            ->assertSet('pendingFilter', 'receive')
            ->assertSet('docIds', [$unconfirmed->id]);
    }

    public function test_toggle_pending_filter_read_isolates_confirmed_but_unread_docs(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        $tag = 'PendRd-' . uniqid();

        DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['category' => $tag], 'tags' => [],
        ]);
        $confirmedUnread = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['category' => $tag], 'tags' => [],
        ]);
        $confirmedRead = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['category' => $tag], 'tags' => [],
        ]);
        Read::create(['document_id' => $confirmedUnread->id, 'user_id' => $reader->id, 'read' => true, 'read_count' => 0]);
        Read::create(['document_id' => $confirmedRead->id, 'user_id' => $reader->id, 'read' => true, 'read_count' => 3]);

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', $tag)
            ->assertCount('docIds', 3)
            ->call('togglePendingFilter', 'read')
            ->assertSet('pendingFilter', 'read')
            ->assertSet('docIds', [$confirmedUnread->id]);
    }

    public function test_toggle_pending_filter_same_value_clears_it(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        $tag = 'PendToggle-' . uniqid();

        DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['category' => $tag], 'tags' => [],
        ]);
        DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['category' => $tag], 'tags' => [],
        ]);

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', $tag)
            ->call('togglePendingFilter', 'receive')
            ->assertSet('pendingFilter', 'receive')
            ->call('togglePendingFilter', 'receive')
            ->assertSet('pendingFilter', null)
            ->assertCount('docIds', 2);
    }

    public function test_toggle_pending_filter_rejects_unknown_value(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => [],
        ]);

        Livewire::actingAs($reader)->test(Main::class)
            ->call('togglePendingFilter', 'evil')
            ->assertSet('pendingFilter', null)
            ->assertHasNoErrors();
    }

    public function test_clear_pending_filter_resets_and_reloads_all(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        $tag = 'PendClear-' . uniqid();

        DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['category' => $tag], 'tags' => [],
        ]);
        $confirmed = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['category' => $tag], 'tags' => [],
        ]);
        Read::create(['document_id' => $confirmed->id, 'user_id' => $reader->id, 'read' => true, 'read_count' => 0]);

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', $tag)
            ->call('togglePendingFilter', 'receive')
            ->assertSet('pendingFilter', 'receive')
            ->assertCount('docIds', 1)
            ->call('clearPendingFilter')
            ->assertSet('pendingFilter', null)
            ->assertCount('docIds', 2);
    }

    public function test_clear_pending_filter_is_noop_when_already_clear(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => [],
        ]);

        Livewire::actingAs($reader)->test(Main::class)
            ->call('clearPendingFilter')
            ->assertSet('pendingFilter', null)
            ->assertHasNoErrors();
    }

    public function test_switch_tab_resets_pending_filter(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        $tag = 'PendTab-' . uniqid();

        DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['category' => $tag], 'tags' => [],
        ]);
        DMS::factory()->create([
            'status' => 'live', 'type' => false, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['category' => $tag], 'tags' => [],
        ]);

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', $tag)
            ->call('togglePendingFilter', 'receive')
            ->assertSet('pendingFilter', 'receive')
            ->call('switchTab', 'non_systematic')
            ->assertSet('activeTab', 'non_systematic')
            ->assertSet('pendingFilter', null);
    }

    public function test_pending_filter_composes_with_search(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        $tag = 'PendSearch-' . uniqid();
        $needle = 'Needle-' . uniqid();

        $matchingUnconfirmed = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'title' => $needle, 'extra' => ['category' => $tag], 'tags' => [],
        ]);
        DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'title' => "Other-{$tag}", 'extra' => ['category' => $tag], 'tags' => [],
        ]);
        $matchingConfirmed = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'title' => $needle, 'extra' => ['category' => $tag], 'tags' => [],
        ]);
        Read::create(['document_id' => $matchingConfirmed->id, 'user_id' => $reader->id, 'read' => true, 'read_count' => 0]);

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', $needle)
            ->assertCount('docIds', 2)
            ->call('togglePendingFilter', 'receive')
            ->assertSet('docIds', [$matchingUnconfirmed->id]);
    }

    public function test_pending_filter_receive_empty_state_yields_zero_doc_ids(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        $tag = 'PendEmpty-' . uniqid();

        $confirmed = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => ['category' => $tag], 'tags' => [],
        ]);
        Read::create(['document_id' => $confirmed->id, 'user_id' => $reader->id, 'read' => true, 'read_count' => 0]);

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', $tag)
            ->assertCount('docIds', 1)
            ->call('togglePendingFilter', 'receive')
            ->assertSet('docIds', [])
            ->assertSet('hasMorePages', false);
    }

    public function test_confirmed_unread_document_view_link_wires_recent_doc_click(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'code' => 'RCT-001', 'version' => '1.0', 'title' => 'Recent doc title one',
            'file' => 'dms/recent-one.pdf',
        ]);
        Read::create(['document_id' => $doc->id, 'user_id' => $reader->id, 'read' => true, 'read_count' => 0]);

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', 'RCT-001')
            ->assertSee('data-doc-id="' . $doc->id . '"', false)
            ->assertSee('data-doc-title="Recent doc title one"', false)
            ->assertSee("recordClick({ id: \$el.closest('tr').dataset.docId, title: \$el.closest('tr').dataset.docTitle, url: \$el.href })", false)
            ->assertSee(route('dms.file',$doc->file), false);
    }

    public function test_confirmed_read_document_reopen_link_wires_recent_doc_click(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'code' => 'RCT-002', 'version' => '1.0', 'title' => 'Recent doc title two',
            'file' => 'dms/recent-two.pdf',
        ]);
        Read::create(['document_id' => $doc->id, 'user_id' => $reader->id, 'read' => true, 'read_count' => 1]);

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', 'RCT-002')
            ->assertSee('data-doc-id="' . $doc->id . '"', false)
            ->assertSee('مشاهده مجدد')
            ->assertSee("recordClick({ id: \$el.closest('tr').dataset.docId, title: \$el.closest('tr').dataset.docTitle, url: \$el.href })", false);
    }

    public function test_recent_docs_strip_markup_renders_with_recent_alpine_scope(): void
    {
        $reader = $this->deptUser('PS', 'expert');

        Livewire::withoutLazyLoading();

        Livewire::actingAs($reader)->test(Main::class)
            ->assertSee('x-show="recent.length"', false)
            ->assertSee('اخیراً مشاهده‌شده')
            ->assertSee('clearRecent()', false)
            ->assertSee('x-for="doc in recent"', false);
    }

    public function test_version_cell_popover_renders_created_and_updated_labels_when_dates_set(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'code' => 'VER-001', 'version' => '2.0',
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDay(),
        ]);

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', 'VER-001')
            ->assertSee('VER-001 - 2.0')
            ->assertSee('title="تاریخ ایجاد و بروزرسانی"', false)
            ->assertSee('expand_more')
            ->assertSee('ایجاد:')
            ->assertSee('بروزرسانی:');
    }

    public function test_version_cell_renders_plain_chip_without_popover_when_both_dates_null(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'code' => 'VER-002', 'version' => '3.0',
        ]);
        DB::table('dms')->where('id', $doc->id)->update(['created_at' => null, 'updated_at' => null]);

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', 'VER-002')
            ->assertSee('VER-002 - 3.0')
            ->assertDontSee('title="تاریخ ایجاد و بروزرسانی"', false)
            ->assertDontSee('ایجاد:')
            ->assertDontSee('بروزرسانی:');
    }

    public function test_details_cell_renders_popover_when_multiple_extra_details(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        $v1 = 'note-' . uniqid();
        $v2 = 'prio-' . uniqid();
        DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'code' => 'DET-001', 'version' => '1.0',
            'extra' => ['notes' => $v1, 'priority' => $v2],
            'revision' => null,
        ]);

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', 'DET-001')
            ->assertSee('title="نمایش جزییات"', false)
            ->assertSee($v1)
            ->assertSee($v2)
            ->assertDontSee('بدون جزییات');
    }

    public function test_details_cell_renders_popover_when_single_extra_detail(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        $v = 'solo-' . uniqid();
        DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'code' => 'DET-002', 'version' => '1.0',
            'extra' => ['notes' => $v],
            'revision' => null,
        ]);

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', 'DET-002')
            ->assertSee($v)
            ->assertSee('title="نمایش جزییات"', false)
            ->assertDontSee('بدون جزییات');
    }

    public function test_details_cell_renders_empty_state_when_no_extra_details(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'code' => 'DET-003', 'version' => '1.0',
            'extra' => ['type' => 'دستورالعمل'],
            'revision' => null,
        ]);

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', 'DET-003')
            ->assertSee('بدون جزییات')
            ->assertDontSee('title="نمایش جزییات"', false);
    }

    public function test_description_cell_renders_revision_popover_when_revision_set(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        $rev = 'توضیح-تست-' . uniqid();
        DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'code' => 'DET-004', 'version' => '1.0',
            'extra' => [],
            'revision' => $rev,
        ]);

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', 'DET-004')
            ->assertSee('title="نمایش توضیحات"', false)
            ->assertSee($rev)
            ->assertDontSee('بدون توضیح');
    }

    public function test_description_cell_renders_no_popover_when_revision_empty(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'code' => 'DET-005', 'version' => '1.0',
            'extra' => [],
            'revision' => null,
        ]);

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', 'DET-005')
            ->assertSee('بدون توضیح')
            ->assertDontSee('title="نمایش توضیحات"', false);
    }

    public function test_renditions_cell_renders_popover_chip_when_multiple_renditions(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'code' => 'REN-001', 'version' => '1.0',
            'file' => 'dms/primary.pdf',
            'extra_files' => ['dms/word-copy.docx', 'dms/excel-copy.xlsx'],
            'revision' => null,
            'extra' => [],
        ]);

        Livewire::actingAs($reader)->test(Main::class)
            ->call('confirmRead', $doc->id)
            ->assertHasNoErrors();

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', 'REN-001')
            ->assertSee('title="نمایش نسخه‌ها"', false)
            ->assertSee('نسخه‌های سند');
    }

    public function test_renditions_cell_renders_inline_when_single_rendition(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'code' => 'REN-002', 'version' => '1.0',
            'file' => 'dms/only.pdf',
            'extra_files' => [],
            'revision' => null,
            'extra' => [],
        ]);

        Livewire::actingAs($reader)->test(Main::class)
            ->call('confirmRead', $doc->id)
            ->assertHasNoErrors();

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', 'REN-002')
            ->assertDontSee('title="نمایش نسخه‌ها"', false)
            ->assertDontSee('نسخه‌های سند');
    }

    public function test_dms_table_renders_column_visibility_controls_and_data_keys(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'code' => 'COL-001', 'version' => '1.0',
            'revision' => null, 'extra' => [],
        ]);

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', 'COL-001')
            ->assertSee('title="نمایش و مخفی کردن ستون‌ها"', false)
            ->assertSee('data-col="title"', false)
            ->assertSee('data-col="code"', false)
            ->assertSee('data-col="dept"', false)
            ->assertSee('data-col="status"', false)
            ->assertSee('data-col="details"', false)
            ->assertSee('data-col="desc"', false)
            ->assertSee('data-col="action"', false)
            ->assertSee('نمایش همه');
    }

    public function test_dms_table_header_renders_unified_toolbar(): void
    {
        $reader = $this->deptUser('PS', 'expert');
        DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'code' => 'SET-001', 'version' => '1.0',
            'revision' => null, 'extra' => [],
        ]);

        Livewire::actingAs($reader)->test(Main::class)
            ->set('search', 'SET-001')
            ->assertSee('toggleHeaderDeclutter()', false)
            ->assertSee('toggleMaximize()', false)
            ->assertSee('group/max', false)
            ->assertSee("max ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]'", false)
            ->assertSee("'max-widget': max || maxLeaving", false)
            ->assertSee('open_in_full', false)
            ->assertSee('$store.density.toggle()', false)
            ->assertSee('title="نمایش و مخفی کردن ستون‌ها"', false)
            ->assertSee('title="فیلتر بازه تاریخ"', false)
            ->assertSee('title="مرتب‌سازی"', false)
            ->assertSee('wire:click="resetSort"', false)
            ->assertSee('بازنشانی ترتیب')
            ->assertSee('نمایش همه')
            ->assertSee('data-declutter-hide', false)
            ->assertDontSee('x-show="openSettings"', false)
            ->assertDontSee('toggleSettings()', false)
            ->assertDontSee('title="تنظیمات جدول"', false);
    }

    public function test_extension_icon_maps_known_extensions_and_defaults_unknown()
    {
        $p = new DmsPresenter();

        $this->assertSame('picture_as_pdf', $p->extensionIcon('pdf')['icon']);
        $this->assertSame('سند PDF', $p->extensionIcon('pdf')['label']);
        $this->assertSame('table_chart', $p->extensionIcon('xlsx')['icon']);
        $this->assertSame('table_chart', $p->extensionIcon('XLS')['icon']);
        $this->assertSame('table_chart', $p->extensionIcon('csv')['icon']);
        $this->assertSame('description', $p->extensionIcon('docx')['icon']);
        $this->assertSame('description', $p->extensionIcon('doc')['icon']);
        $this->assertSame('insert_drive_file', $p->extensionIcon('zip')['icon']);
        $this->assertSame('insert_drive_file', $p->extensionIcon(null)['icon']);
        $this->assertSame('insert_drive_file', $p->extensionIcon('')['icon']);
    }

    public function test_rendition_data_selects_route_by_path_match_and_maps_icon()
    {
        $p = new DmsPresenter();
        $docFile = 'dms/probe.pdf';

        $primary = $p->renditionData(['path' => $docFile, 'ext' => 'pdf'], $docFile);
        $this->assertSame(route('dms.file',$docFile), $primary['route']);
        $this->assertSame('picture_as_pdf', $primary['icon']);
        $this->assertSame('سند PDF', $primary['label']);

        $extra = $p->renditionData(['path' => 'dms/extra.xlsx', 'ext' => 'xlsx'], $docFile);
        $this->assertSame(route('dms.extra-file','dms/extra.xlsx'), $extra['route']);
        $this->assertSame('table_chart', $extra['icon']);
    }

    public function test_row_state_status_color_follows_confirmed_and_read_state()
    {
        $p = new DmsPresenter();
        $doc = DMS::factory()->create(['owners' => [], 'extra' => ['category' => 'qa'], 'title' => 'Probe Title']);

        $pending = $p->rowState($doc, [], []);
        $this->assertFalse($pending['isConfirmed']);
        $this->assertFalse($pending['isRead']);
        $this->assertSame('var(--md-sys-color-error)', $pending['statusColor']);
        $this->assertSame('qa', $pending['cat']);
        $this->assertSame(superClean('Probe Title'), $pending['cleanTitle']);
        $this->assertSame(0, $pending['extraDetails']->count());

        $confirmed = $p->rowState($doc, [$doc->id], []);
        $this->assertTrue($confirmed['isConfirmed']);
        $this->assertFalse($confirmed['isRead']);
        $this->assertSame('var(--md-sys-color-tertiary)', $confirmed['statusColor']);

        $read = $p->rowState($doc, [$doc->id], [$doc->id]);
        $this->assertTrue($read['isConfirmed']);
        $this->assertTrue($read['isRead']);
        $this->assertSame('var(--md-sys-color-primary)', $read['statusColor']);
    }

    private function jalaliParts(\Carbon\CarbonInterface $date): array
    {
        $j = \Morilog\Jalali\Jalalian::fromCarbon($date);

        return [$j->getYear(), $j->getMonth(), $j->getDay()];
    }

    private function stampDoc(DMS $doc, string $created, string $updated): DMS
    {
        $doc->forceFill(['created_at' => $created, 'updated_at' => $updated])->saveQuietly();

        return $doc;
    }

    public function test_date_span_filter_narrows_docs_by_updated_at()
    {
        $reader = $this->deptUser('PS', 'expert');

        $recent = $this->stampDoc(DMS::factory()->create(['status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [], 'extra' => [], 'tags' => []]), now()->subDays(2)->format('Y-m-d H:i:s'), now()->subDays(2)->format('Y-m-d H:i:s'));
        $old = $this->stampDoc(DMS::factory()->create(['status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [], 'extra' => [], 'tags' => []]), now()->subYears(2)->format('Y-m-d H:i:s'), now()->subYears(2)->format('Y-m-d H:i:s'));

        [$fy, $fm, $fd] = $this->jalaliParts(now()->subDays(3));
        [$ty, $tm, $td] = $this->jalaliParts(now());

        $ids = Livewire::actingAs($reader)
            ->test(Main::class)
            ->set('fromYear', $fy)->set('fromMonth', $fm)->set('fromDay', $fd)
            ->set('toYear', $ty)->set('toMonth', $tm)->set('toDay', $td)
            ->call('applyDateSpan')
            ->assertHasNoErrors()
            ->get('docIds');

        $this->assertContains($recent->id, $ids);
        $this->assertNotContains($old->id, $ids);
    }

    public function test_date_span_basis_created_filters_on_created_at_instead()
    {
        $reader = $this->deptUser('PS', 'expert');

        $createdRecently = $this->stampDoc(DMS::factory()->create(['status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [], 'extra' => [], 'tags' => []]), now()->subDays(2)->format('Y-m-d H:i:s'), now()->format('Y-m-d H:i:s'));
        $createdOld = $this->stampDoc(DMS::factory()->create(['status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [], 'extra' => [], 'tags' => []]), now()->subYears(2)->format('Y-m-d H:i:s'), now()->subDay()->format('Y-m-d H:i:s'));

        [$fy, $fm, $fd] = $this->jalaliParts(now()->subDays(3));
        [$ty, $tm, $td] = $this->jalaliParts(now());

        $ids = Livewire::actingAs($reader)
            ->test(Main::class)
            ->set('dateBasis', 'created')
            ->set('fromYear', $fy)->set('fromMonth', $fm)->set('fromDay', $fd)
            ->set('toYear', $ty)->set('toMonth', $tm)->set('toDay', $td)
            ->call('applyDateSpan')
            ->assertHasNoErrors()
            ->get('docIds');

        $this->assertContains($createdRecently->id, $ids);
        $this->assertNotContains($createdOld->id, $ids);
    }

    public function test_partial_and_invalid_date_spans_are_ignored()
    {
        $reader = $this->deptUser('PS', 'expert');

        $a = DMS::factory()->create(['status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [], 'extra' => [], 'tags' => []]);
        $b = DMS::factory()->create(['status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [], 'extra' => [], 'tags' => []]);

        $partial = Livewire::actingAs($reader)
            ->test(Main::class)
            ->set('perPage', 100)
            ->call('loadInitialDocs')
            ->set('fromYear', 1405)->set('fromMonth', 5)
            ->call('applyDateSpan')
            ->assertDispatched('toast')
            ->get('docIds');

        $this->assertContains($a->id, $partial);
        $this->assertContains($b->id, $partial);

        $invalid = Livewire::actingAs($reader)
            ->test(Main::class)
            ->set('perPage', 100)
            ->call('loadInitialDocs')
            ->set('fromYear', 1405)->set('fromMonth', 7)->set('fromDay', 31)
            ->set('toYear', 1405)->set('toMonth', 8)->set('toDay', 1)
            ->call('applyDateSpan')
            ->assertDispatched('toast')
            ->get('docIds');

        $this->assertContains($a->id, $invalid);
        $this->assertContains($b->id, $invalid);
    }

    public function test_clear_date_span_restores_all_docs()
    {
        $reader = $this->deptUser('PS', 'expert');

        $stale = $this->stampDoc(DMS::factory()->create(['status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [], 'extra' => [], 'tags' => []]), now()->subYears(2)->format('Y-m-d H:i:s'), now()->subYears(2)->format('Y-m-d H:i:s'));

        [$ty, $tm, $td] = $this->jalaliParts(now());

        $component = Livewire::actingAs($reader)
            ->test(Main::class)
            ->set('perPage', 1000)
            ->set('fromYear', $ty)->set('fromMonth', $tm)->set('fromDay', $td)
            ->set('toYear', $ty)->set('toMonth', $tm)->set('toDay', $td)
            ->call('applyDateSpan')
            ->assertHasNoErrors();

        $this->assertNotContains($stale->id, $component->get('docIds'));

        $ids = $component->call('clearDateSpan')
            ->assertHasNoErrors()
            ->assertSet('fromYear', null)
            ->assertSet('toDay', null)
            ->get('docIds');

        $this->assertContains($stale->id, $ids);
    }

    public function test_load_more_restores_applied_date_parts_instead_of_serving_unapplied_edits()
    {
        $reader = $this->deptUser('PS', 'expert');

        $older = $this->stampDoc(DMS::factory()->create(['status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [], 'extra' => [], 'tags' => []]), now()->subDays(5)->format('Y-m-d H:i:s'), now()->subDays(5)->format('Y-m-d H:i:s'));
        $newer = $this->stampDoc(DMS::factory()->create(['status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [], 'extra' => [], 'tags' => []]), now()->subDay()->format('Y-m-d H:i:s'), now()->subDay()->format('Y-m-d H:i:s'));

        [$fy, $fm, $fd] = $this->jalaliParts(now()->subDays(6));
        [$ty, $tm, $td] = $this->jalaliParts(now());

        $component = Livewire::actingAs($reader)
            ->test(Main::class)
            ->set('perPage', 1)
            ->set('fromYear', $fy)->set('fromMonth', $fm)->set('fromDay', $fd)
            ->set('toYear', $ty)->set('toMonth', $tm)->set('toDay', $td)
            ->call('applyDateSpan')
            ->assertHasNoErrors()
            ->assertSet('hasMorePages', true);

        $appliedIds = $component->get('docIds');
        $this->assertNotContains($older->id, $appliedIds);

        [$ty2, $tm2, $td2] = $this->jalaliParts(now()->subDays(4));

        $ids = $component->set('toYear', $ty2)->set('toMonth', $tm2)->set('toDay', $td2)
            ->call('loadMore')
            ->assertSet('toDay', $td)
            ->assertSet('hasMorePages', true)
            ->get('docIds');

        $this->assertSame($appliedIds, $ids);
    }

    public function test_date_span_popover_renders_settings_row_markers()
    {
        $reader = $this->deptUser('PS', 'expert');

        Livewire::withoutLazyLoading();

        Livewire::actingAs($reader)
            ->test(Main::class)
            ->assertSee('فیلتر بازه تاریخ')
            ->assertSee('حذف فیلتر')
            ->assertSee('setDateBasis');
    }

    public function test_confirmed_unread_pdf_doc_renders_inapp_viewer_button(): void
    {
        $this->cleanSlate();
        $reader = $this->deptUser('PS', 'expert');
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => [], 'file' => 'dms/report.pdf',
        ]);
        Read::create(['document_id' => $doc->id, 'user_id' => $reader->id, 'read' => true, 'read_count' => 0]);

        Livewire::withoutLazyLoading();

        $html = Livewire::actingAs($reader)->test(Main::class)->html();

        $this->assertStringContainsString("confirmAndSend({$doc->id}", $html);
        $this->assertStringNotContainsString('wire:key="dms-row-open" href=', $html);
    }

    public function test_confirmed_unread_non_pdf_doc_renders_original_download_anchor(): void
    {
        $this->cleanSlate();
        $reader = $this->deptUser('PS', 'expert');
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => [], 'file' => 'dms/report.docx',
        ]);
        Read::create(['document_id' => $doc->id, 'user_id' => $reader->id, 'read' => true, 'read_count' => 0]);

        Livewire::withoutLazyLoading();

        $html = Livewire::actingAs($reader)->test(Main::class)->html();

        $this->assertStringContainsString('wire:key="dms-row-open" href=', $html);
        $this->assertStringNotContainsString("confirmAndSend({$doc->id}", $html);
    }

    public function test_second_reads_row_for_same_document_and_user_is_rejected_by_the_unique_index(): void
    {
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Read::create(['document_id' => $doc->id, 'user_id' => $reader->id, 'read' => true, 'read_count' => 0]);

        $this->expectException(QueryException::class);
        Read::create(['document_id' => $doc->id, 'user_id' => $reader->id, 'read' => true, 'read_count' => 0]);
    }

    public function test_confirm_read_on_doc_revoked_mid_session_toasts_and_creates_no_reads_row(): void
    {
        $this->cleanSlate();
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::withoutLazyLoading();

        $component = Livewire::actingAs($reader)->test(Main::class);
        $this->assertSame([$doc->id], $component->get('docIds'));

        $doc->update(['status' => 'obsolete']);

        $component->call('confirmRead', $doc->id)
            ->assertDispatched('toast', message: 'سند دیگر در دسترس نیست', type: 'warning');

        $this->assertSame([], $component->get('docIds'));
        $this->assertDatabaseMissing('reads', [
            'document_id' => $doc->id,
            'user_id' => $reader->id,
        ]);
    }

    public function test_confirmation_confirmed_with_wrong_method_or_vanished_doc_toasts_and_creates_no_reads_row(): void
    {
        $this->cleanSlate();
        $doc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => [],
        ]);
        $liveDoc = DMS::factory()->create([
            'status' => 'live', 'type' => true, 'owners' => ['ALL'], 'users' => [],
            'extra' => [], 'tags' => [],
        ]);
        $reader = $this->deptUser('PS', 'expert');

        Livewire::withoutLazyLoading();

        $component = Livewire::actingAs($reader)->test(Main::class);

        $doc->update(['status' => 'obsolete']);

        $component->call('onConfirmationConfirmed', 'confirmRead', $doc->id)
            ->assertDispatched('toast', message: 'سند دیگر در دسترس نیست', type: 'warning');

        $this->assertDatabaseMissing('reads', [
            'document_id' => $doc->id,
            'user_id' => $reader->id,
        ]);

        Livewire::actingAs($reader)->test(Main::class)
            ->call('onConfirmationConfirmed', 'incrementRead', $liveDoc->id)
            ->assertDispatched('toast', message: 'سند دیگر در دسترس نیست', type: 'warning');

        $this->assertDatabaseMissing('reads', [
            'document_id' => $liveDoc->id,
            'user_id' => $reader->id,
        ]);
    }
}