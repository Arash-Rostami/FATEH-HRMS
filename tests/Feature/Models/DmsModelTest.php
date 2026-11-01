<?php

namespace Tests\Feature\Models;

use App\Livewire\Dashboard\Dms\Actions\ConfirmReadAction;
use App\Models\DMS;
use App\Models\Read;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DmsModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        ModelCacheVersion::bump(User::class);
        ModelCacheVersion::bump(DMS::class);
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        ModelCacheVersion::bump(User::class);
        ModelCacheVersion::bump(DMS::class);
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

    public function test_scope_systematic_filters_to_type_true(): void
    {
        $systematic = DMS::factory()->create(['type' => true, 'owners' => ['ALL']]);
        $nonSystematic = DMS::factory()->create(['type' => false, 'owners' => ['ALL']]);

        $this->assertContains($systematic->id, DMS::systematic()->pluck('id')->all());
        $this->assertNotContains($nonSystematic->id, DMS::systematic()->pluck('id')->all());
    }

    public function test_scope_non_systematic_filters_to_type_false(): void
    {
        $systematic = DMS::factory()->create(['type' => true, 'owners' => ['ALL']]);
        $nonSystematic = DMS::factory()->create(['type' => false, 'owners' => ['ALL']]);

        $this->assertContains($nonSystematic->id, DMS::nonSystematic()->pluck('id')->all());
        $this->assertNotContains($systematic->id, DMS::nonSystematic()->pluck('id')->all());
    }

    public function test_get_status_in_farsi_returns_mapped_label_for_known_status(): void
    {
        $dms = new DMS(['status' => 'live']);

        $this->assertSame('فعال', $dms->getStatusInFarsi());
    }

    public function test_get_status_in_farsi_returns_raw_status_for_unknown_status(): void
    {
        $dms = new DMS(['status' => 'unknown']);

        $this->assertSame('unknown', $dms->getStatusInFarsi());
    }

    public function test_get_status_icon_returns_html_for_known_status(): void
    {
        $dms = new DMS(['status' => 'live']);

        $this->assertStringContainsString('check_circle', $dms->getStatusIcon());
    }

    public function test_get_status_icon_returns_raw_status_for_unknown_status(): void
    {
        $dms = new DMS(['status' => 'unknown']);

        $this->assertSame('unknown', $dms->getStatusIcon());
    }

    public function test_get_status_icon_matches_document_status_material_icon_for_every_case(): void
    {
        foreach (\App\Filament\Resources\DmsResource\Enums\DocumentStatus::cases() as $status) {
            $dms = new DMS(['status' => $status->value]);

            $this->assertStringContainsString($status->getMaterialIcon(), $dms->getStatusIcon());
        }
    }

    public function test_renditions_returns_unique_file_paths_with_extensions(): void
    {
        $dms = new DMS();
        $dms->file = 'doc/main.pdf';
        $dms->extra_files = ['doc/extra.docx', 'doc/main.pdf'];

        $renditions = $dms->renditions();

        $this->assertCount(2, $renditions);
        $this->assertSame('pdf', $renditions[0]['ext']);
        $this->assertSame('docx', $renditions[1]['ext']);
    }

    public function test_renditions_filters_out_null_and_empty_paths(): void
    {
        $dms = new DMS();
        $dms->file = 'doc/main.pdf';
        $dms->extra_files = [null, '', 'doc/extra.docx'];

        $this->assertCount(2, $dms->renditions());
    }

    public function test_requires_sign_for_returns_true_when_user_has_no_read_record(): void
    {
        $doc = DMS::factory()->create(['status' => 'live', 'owners' => ['ALL']]);
        $user = User::factory()->create(['status' => 'active']);

        $this->assertTrue($doc->requiresSignFor($user->id));
    }

    public function test_requires_sign_for_returns_false_when_user_has_read_true(): void
    {
        $doc = DMS::factory()->create(['status' => 'live', 'owners' => ['ALL']]);
        $user = User::factory()->create(['status' => 'active']);
        Read::factory()->create([
            'document_id' => $doc->id,
            'user_id' => $user->id,
            'read' => true,
            'read_count' => 1,
        ]);

        $this->assertFalse($doc->requiresSignFor($user->id));
    }

    public function test_requires_read_for_returns_true_when_read_count_is_zero(): void
    {
        $doc = DMS::factory()->create(['status' => 'live', 'owners' => ['ALL']]);
        $user = User::factory()->create(['status' => 'active']);
        Read::factory()->create([
            'document_id' => $doc->id,
            'user_id' => $user->id,
            'read' => true,
            'read_count' => 0,
        ]);

        $this->assertTrue($doc->requiresReadFor($user->id));
    }

    public function test_requires_read_for_returns_false_when_read_count_is_nonzero(): void
    {
        $doc = DMS::factory()->create(['status' => 'live', 'owners' => ['ALL']]);
        $user = User::factory()->create(['status' => 'active']);
        Read::factory()->create([
            'document_id' => $doc->id,
            'user_id' => $user->id,
            'read' => true,
            'read_count' => 2,
        ]);

        $this->assertFalse($doc->requiresReadFor($user->id));
    }

    public function test_visible_to_returns_only_live_documents_matching_user_visibility(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $visible = DMS::factory()->create(['status' => 'live', 'owners' => ['ALL']]);
        $notLive = DMS::factory()->create(['status' => 'under_review', 'owners' => ['ALL']]);
        $notVisible = DMS::factory()->create(['status' => 'live', 'owners' => ['ZZ'], 'users' => []]);

        $query = DMS::visibleTo($user->id);
        $ids = $query->pluck('id')->all();

        $this->assertContains($visible->id, $ids);
        $this->assertNotContains($notLive->id, $ids);
        $this->assertNotContains($notVisible->id, $ids);
    }

    public function test_visible_to_matches_explicit_user_id_in_users_array(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $doc = DMS::factory()->create(['status' => 'live', 'owners' => ['ZZ'], 'users' => [(string) $user->id]]);

        $this->assertContains($doc->id, DMS::visibleTo($user->id)->pluck('id')->all());
    }

    public function test_needs_sign_count_increases_by_count_of_unread_visible_docs(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $baseline = DMS::needsSignCount($user->id);

        DMS::factory()->create(['status' => 'live', 'owners' => ['ZZT'], 'users' => [(string) $user->id]]);
        DMS::factory()->create(['status' => 'live', 'owners' => ['ZZT'], 'users' => [(string) $user->id]]);

        $this->assertSame($baseline + 2, DMS::needsSignCount($user->id));
    }

    public function test_updated_file_resets_reads_and_combined_read_count(): void
    {
        $doc = DMS::factory()->create([
            'status' => 'live',
            'owners' => ['ALL'],
            'combined_read_count' => 5,
        ]);
        $user = User::factory()->create(['status' => 'active']);
        Read::factory()->create([
            'document_id' => $doc->id,
            'user_id' => $user->id,
            'read' => true,
            'read_count' => 3,
        ]);

        $doc->file = 'dms/new.pdf';
        $doc->save();

        $this->assertSame(0, $doc->fresh()->combined_read_count);
        $this->assertSame(0, (int) Read::where('document_id', $doc->id)->value('read_count'));
        $this->assertFalse((bool) Read::where('document_id', $doc->id)->value('read'));
    }

    public function test_updated_revision_resets_reads_and_combined_read_count(): void
    {
        $doc = DMS::factory()->create([
            'status' => 'live',
            'owners' => ['ALL'],
            'combined_read_count' => 5,
        ]);

        $doc->revision = 'new revision text';
        $doc->save();

        $this->assertSame(0, $doc->fresh()->combined_read_count);
    }

    public function test_updated_title_does_not_reset_reads(): void
    {
        $doc = DMS::factory()->create([
            'status' => 'live',
            'owners' => ['ALL'],
            'combined_read_count' => 5,
        ]);

        $doc->title = 'New Title';
        $doc->save();

        $this->assertSame(5, $doc->fresh()->combined_read_count);
    }

    public function test_signed_user_ids_returns_user_ids_from_read_true_records(): void
    {
        $doc = DMS::factory()->create(['status' => 'live', 'owners' => ['ALL']]);
        $user1 = User::factory()->create(['status' => 'active']);
        $user2 = User::factory()->create(['status' => 'active']);
        Read::factory()->create(['document_id' => $doc->id, 'user_id' => $user1->id, 'read' => true, 'read_count' => 1]);
        Read::factory()->create(['document_id' => $doc->id, 'user_id' => $user2->id, 'read' => false, 'read_count' => 0]);

        $ids = $doc->signedUserIds();

        $this->assertContains($user1->id, $ids);
        $this->assertNotContains($user2->id, $ids);
    }

    public function test_pending_recipients_returns_empty_for_non_live_status(): void
    {
        $doc = DMS::factory()->create(['status' => 'under_review', 'owners' => ['ALL']]);

        $this->assertCount(0, $doc->pendingRecipients());
    }

    public function test_deleting_removes_file_and_extra_files_from_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('dms/main.pdf', 'content');
        Storage::disk('public')->put('dms/extra.docx', 'content');
        $doc = DMS::factory()->create([
            'owners' => ['ALL'],
            'file' => 'dms/main.pdf',
            'extra_files' => ['dms/extra.docx'],
        ]);

        $doc->delete();

        Storage::disk('public')->assertMissing('dms/main.pdf');
        Storage::disk('public')->assertMissing('dms/extra.docx');
    }

    public function test_deleting_with_no_files_does_not_error(): void
    {
        Storage::fake('public');
        $doc = DMS::factory()->create([
            'owners' => ['ALL'],
            'extra_files' => null,
        ]);

        $doc->delete();

        $this->assertDatabaseMissing('dms', ['id' => $doc->id]);
    }

    public function test_pending_counts_matches_direct_needs_sign_and_read_counts(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $sign = DMS::needsSignCount($user->id);
        $read = DMS::needsReadCount($user->id);

        DMS::factory()->count(2)->create(['status' => 'live', 'owners' => ['ZZP'], 'users' => [(string) $user->id]]);
        $confirmedUnread = DMS::factory()->create(['status' => 'live', 'owners' => ['ZZP'], 'users' => [(string) $user->id]]);
        Read::factory()->create(['document_id' => $confirmedUnread->id, 'user_id' => $user->id, 'read' => true, 'read_count' => 0]);

        $this->assertSame([$sign + 2, $read + 1], DMS::pendingCounts($user->id));
    }

    public function test_pending_counts_second_call_is_served_from_cache_without_querying_dms(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        DMS::factory()->create(['status' => 'live', 'owners' => ['ZZP'], 'users' => [(string) $user->id]]);

        DMS::pendingCounts($user->id);

        $this->assertTrue(Cache::has(ModelCacheVersion::key(DMS::class, 'pending_counts:' . $user->id)));

        DB::enableQueryLog();
        DB::flushQueryLog();
        DMS::pendingCounts($user->id);
        $dmsQueries = collect(DB::getQueryLog())->filter(fn($q) => str_contains($q['query'], '`dms`'));
        DB::disableQueryLog();

        $this->assertCount(0, $dmsQueries, 'a cached pendingCounts hit must not query the dms table again');
    }

    public function test_confirm_read_action_forgets_pending_counts_cache_and_recomputes_fresh_counts(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $doc = DMS::factory()->create(['status' => 'live', 'owners' => ['ALL'], 'users' => []]);

        $before = DMS::pendingCounts($user->id);

        $this->actingAs($user);
        $this->assertTrue(app(ConfirmReadAction::class)->execute($doc->id));

        $this->assertFalse(Cache::has(ModelCacheVersion::key(DMS::class, 'pending_counts:' . $user->id)));

        $after = DMS::pendingCounts($user->id);
        $this->assertSame($before[0] - 1, $after[0]);
        $this->assertSame($before[1] + 1, $after[1]);
    }

    public function test_same_format_attachments_are_enumerated_and_old_file_names_still_resolve(): void
    {
        config(['app.tenant' => 'persol']);

        $dms = new DMS([
            'code' => 'PRC-014',
            'version' => '2',
            'file' => 'dms/FATEH-DMS-20250714-abc-1-old-name.pdf',
            'extra_files' => ['dms/x1.xlsx', 'dms/x2.xlsx', 'dms/legacy'],
        ]);

        $this->assertSame(['excel', 1, 2], [$dms->renditions()[1]['kind'], $dms->renditions()[1]['n'], $dms->renditions()[1]['of']]);
        $this->assertSame('PERSOL-PRC-014-v2-pdf.pdf', $dms->downloadNameFor($dms->file));
        $this->assertSame('PERSOL-PRC-014-v2-excel-1.xlsx', $dms->downloadNameFor('dms/x1.xlsx'));
        $this->assertSame('PERSOL-PRC-014-v2-excel-2.xlsx', $dms->downloadNameFor('dms/x2.xlsx'));
        $this->assertSame('PERSOL-PRC-014-v2-file', $dms->downloadNameFor('dms/legacy'));
        $this->assertSame('unknown.bin', $dms->downloadNameFor('dms/unknown.bin'));
    }
}