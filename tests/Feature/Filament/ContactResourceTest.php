<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ContactResource\Pages\EditContact;
use App\Filament\Resources\ContactResource\Pages\ListContacts;
use App\Filament\Resources\ContactResource\RelationManagers\RepliesRelationManager;
use App\Models\Message;
use App\Models\Permission;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ContactResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        Cache::flush();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Cache::flush();
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

    public function test_replies_relation_manager_is_read_only()
    {
        $this->assertTrue((new RepliesRelationManager())->isReadOnly());
    }

    private function stats(ListContacts $page): array
    {
        $method = new \ReflectionMethod($page, 'getStats');

        return $method->invoke($page);
    }

    public function test_tab_badge_stats_reuse_the_cache_when_no_message_changed()
    {
        $admin = $this->developer();
        Message::factory()->create(['read_at' => null]);

        // First mount computes the aggregate; the stats are cached for the 5-minute window.
        Livewire::actingAs($admin)->test(ListContacts::class)->assertSuccessful();

        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::actingAs($admin)->test(ListContacts::class)->assertSuccessful();
        DB::disableQueryLog();

        $scans = array_filter(DB::getQueryLog(), fn($q) => str_contains($q['query'], 'SUM(CASE WHEN read_at'));
        $this->assertCount(0, $scans, 'a re-mount inside the TTL window must not re-scan the messages table — the cached aggregate serves the badge counts');
    }

    public function test_tab_badge_stats_lag_by_design_until_the_window_expires()
    {
        $admin = $this->developer();

        // Dev DB carries real messages — assert the delta, never absolute counts.
        $before = (int)$this->stats(Livewire::actingAs($admin)->test(ListContacts::class)->instance())['unread_count'];

        Message::factory()->create(['read_at' => null]);

        $withinWindow = (int)$this->stats(Livewire::actingAs($admin)->test(ListContacts::class)->instance())['unread_count'];
        $this->assertSame($before, $withinWindow, 'a write inside the TTL window is served from cache — the badge lags by design, the accepted staleness trade');

        Cache::forget('contact_stats_badges');

        $afterExpiry = (int)$this->stats(Livewire::actingAs($admin)->test(ListContacts::class)->instance())['unread_count'];
        $this->assertSame($before + 1, $afterExpiry, 'once the window expires the aggregate recomputes and sees the new message');
    }

    public function test_replies_relation_manager_lists_replies()
    {
        $admin = $this->developer();
        $message = Message::factory()->create();
        $reply = Message::factory()->create([
            'reply_to_id' => $message->id,
            'sender_id' => $message->recipient_id,
            'recipient_id' => $message->sender_id,
        ]);

        Livewire::actingAs($admin)
            ->test(RepliesRelationManager::class, ['ownerRecord' => $message, 'pageClass' => EditContact::class])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$reply]);
    }

    public function test_list_renders_and_shows_a_factory_message_row()
    {
        $admin = $this->developer();

        $message = Message::factory()->create(['created_at' => now()]);

        Livewire::actingAs($admin)->test(ListContacts::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$message]);
    }

    public function test_edit_page_mounts_and_save_persists_new_body_and_marks_edited()
    {
        $admin = $this->developer();

        $message = Message::factory()->create(['is_edited' => false]);

        Livewire::actingAs($admin)
            ->test(EditContact::class, ['record' => $message->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['body' => '0 edited body content from test'])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $message->fresh();
        $this->assertStringContainsString('0 edited body content from test', $fresh->body);
        $this->assertTrue($fresh->is_edited);
    }

    public function test_edit_body_is_required()
    {
        $admin = $this->developer();

        $message = Message::factory()->create();

        Livewire::actingAs($admin)
            ->test(EditContact::class, ['record' => $message->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['body' => ''])
            ->call('save')
            ->assertHasFormErrors(['body' => 'required']);
    }

    public function test_unread_filter_scopes_list_to_unread_messages()
    {
        $admin = $this->developer();

        $unread = Message::factory()->create(['read_at' => null, 'created_at' => now()]);
        $read = Message::factory()->create(['read_at' => now(), 'created_at' => now()]);

        Livewire::actingAs($admin)->test(ListContacts::class)
            ->assertSuccessful()
            ->filterTable('read_at', '0')
            ->assertCanSeeTableRecords([$unread])
            ->assertCanNotSeeTableRecords([$read]);
    }

    public function test_has_reply_filter_true_scopes_list_to_reply_messages()
    {
        $admin = $this->developer();

        $parent = Message::factory()->create(['reply_to_id' => null, 'created_at' => now()]);
        $reply = Message::factory()->create(['reply_to_id' => $parent->id, 'created_at' => now()]);

        Livewire::actingAs($admin)->test(ListContacts::class)
            ->assertSuccessful()
            ->filterTable('has_reply', true)
            ->assertCanSeeTableRecords([$reply])
            ->assertCanNotSeeTableRecords([$parent]);
    }

    public function test_edited_filter_scopes_list_to_edited_messages()
    {
        $admin = $this->developer();

        $edited = Message::factory()->create(['is_edited' => true, 'created_at' => now()]);
        $notEdited = Message::factory()->create(['is_edited' => false, 'created_at' => now()]);

        Livewire::actingAs($admin)->test(ListContacts::class)
            ->assertSuccessful()
            ->filterTable('is_edited', true)
            ->assertCanSeeTableRecords([$edited])
            ->assertCanNotSeeTableRecords([$notEdited]);
    }

    public function test_has_attachments_filter_scopes_list_to_messages_with_attachments()
    {
        $admin = $this->developer();

        $withAttachments = Message::factory()->create(['attachments' => ['a.pdf'], 'created_at' => now()]);
        $withoutAttachments = Message::factory()->create(['attachments' => [], 'created_at' => now()]);

        Livewire::actingAs($admin)->test(ListContacts::class)
            ->assertSuccessful()
            ->filterTable('has_attachments', true)
            ->assertCanSeeTableRecords([$withAttachments])
            ->assertCanNotSeeTableRecords([$withoutAttachments]);
    }

    public function test_sender_filter_scopes_list_to_matching_sender()
    {
        $admin = $this->developer();

        $senderA = User::factory()->create();
        $senderB = User::factory()->create();
        $fromA = Message::factory()->create(['sender_id' => $senderA->id, 'created_at' => now()]);
        $fromB = Message::factory()->create(['sender_id' => $senderB->id, 'created_at' => now()]);

        Livewire::actingAs($admin)->test(ListContacts::class)
            ->assertSuccessful()
            ->filterTable('sender_id', $senderA->id)
            ->assertCanSeeTableRecords([$fromA])
            ->assertCanNotSeeTableRecords([$fromB]);
    }

    public function test_recipient_filter_scopes_list_to_matching_recipient()
    {
        $admin = $this->developer();

        $recipientA = User::factory()->create();
        $recipientB = User::factory()->create();
        $toA = Message::factory()->create(['recipient_id' => $recipientA->id, 'created_at' => now()]);
        $toB = Message::factory()->create(['recipient_id' => $recipientB->id, 'created_at' => now()]);

        Livewire::actingAs($admin)->test(ListContacts::class)
            ->assertSuccessful()
            ->filterTable('recipient_id', $recipientA->id)
            ->assertCanSeeTableRecords([$toA])
            ->assertCanNotSeeTableRecords([$toB]);
    }

    public function test_created_at_filter_scopes_to_the_given_date_range()
    {
        $admin = $this->developer();

        $inRange = Message::factory()->create(['created_at' => now()->subDays(5)]);
        $outOfRange = Message::factory()->create();
        $outOfRange->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();

        Livewire::actingAs($admin)->test(ListContacts::class)
            ->assertSuccessful()
            ->filterTable('created_at', ['from' => now()->subDays(10)->toDateString(), 'until' => now()->toDateString()])
            ->assertCanSeeTableRecords([$inRange])
            ->assertCanNotSeeTableRecords([$outOfRange]);
    }

    public function test_pruning_soon_filter_scopes_to_old_trashed_messages()
    {
        $admin = $this->developer();

        $old = Message::factory()->create(['created_at' => now()]);
        $old->forceFill(['deleted_at' => now()->subDays(40)])->saveQuietly();

        $recent = Message::factory()->create(['created_at' => now()]);
        $recent->forceFill(['deleted_at' => now()->subDays(5)])->saveQuietly();

        Livewire::actingAs($admin)->test(ListContacts::class)
            ->assertSuccessful()
            ->filterTable('pruning_soon')
            ->assertCanSeeTableRecords([$old])
            ->assertCanNotSeeTableRecords([$recent]);
    }

    public function test_delete_row_action_soft_deletes_message()
    {
        $admin = $this->developer();

        $message = Message::factory()->create();

        Livewire::actingAs($admin)->test(ListContacts::class)
            ->assertSuccessful()
            ->callTableAction(DeleteAction::class, $message);

        $this->assertSoftDeleted('messages', ['id' => $message->id]);
    }

    public function test_sorting_by_id_created_at_and_deleted_at_orders_records_correctly()
    {
        $admin = $this->developer();
        $older = Message::factory()->create();
        $older->forceFill(['created_at' => now()->subDays(60), 'deleted_at' => now()->subDays(60)])->saveQuietly();
        $newer = Message::factory()->create();
        $newer->forceFill(['created_at' => now(), 'deleted_at' => now()])->saveQuietly();

        $component = Livewire::actingAs($admin)->test(ListContacts::class)
            ->set('tableRecordsPerPage', 1000)
            ->sortTable('id')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('id', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('created_at')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('deleted_at')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('deleted_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));
    }

    public function test_sorting_by_read_at_orders_records_correctly()
    {
        $admin = $this->developer();
        $older = Message::factory()->create(['read_at' => now()->subDays(60)]);
        $newer = Message::factory()->create(['read_at' => now()]);

        $component = Livewire::actingAs($admin)->test(ListContacts::class)
            ->set('tableRecordsPerPage', 1000)
            ->sortTable('read_at')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('read_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));
    }

    public function test_sorting_by_sender_and_recipient_orders_records_correctly()
    {
        $admin = $this->developer();
        $senderA = User::factory()->create(['name' => '0-AAA-sender-sort']);
        $senderZ = User::factory()->create(['name' => '0-ZZZ-sender-sort']);
        $recipientA = User::factory()->create(['name' => '0-AAA-recipient-sort']);
        $recipientZ = User::factory()->create(['name' => '0-ZZZ-recipient-sort']);

        $fromA = Message::factory()->create(['sender_id' => $senderA->id, 'recipient_id' => $recipientA->id]);
        $fromZ = Message::factory()->create(['sender_id' => $senderZ->id, 'recipient_id' => $recipientZ->id]);

        $component = Livewire::actingAs($admin)->test(ListContacts::class)
            ->set('tableRecordsPerPage', 1000)
            ->sortTable('sender.name')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$fromA->id, $fromZ->id], array_values(array_intersect($ids, [$fromA->id, $fromZ->id])));

        $component->sortTable('sender.name', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$fromZ->id, $fromA->id], array_values(array_intersect($ids, [$fromA->id, $fromZ->id])));

        $component->sortTable('recipient.name')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$fromA->id, $fromZ->id], array_values(array_intersect($ids, [$fromA->id, $fromZ->id])));

        $component->sortTable('recipient.name', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$fromZ->id, $fromA->id], array_values(array_intersect($ids, [$fromA->id, $fromZ->id])));
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();
        Message::factory()->create(['created_at' => now()]);

        $component = Livewire::actingAs($admin)->test(ListContacts::class)->assertSuccessful();

        foreach (['sender.name', 'recipient.name'] as $group) {
            $component->set('tableGrouping', $group)->assertSuccessful();
        }
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\ContactResource\Pages\EditContact::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\ContactResource\Pages\ListContacts::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\ContactResource\Pages\EditContact::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }

        $managers = [\App\Filament\Resources\ContactResource\RelationManagers\RepliesRelationManager::class];

        foreach ($managers as $manager) {
            $this->assertSame(\App\Filament\RelationManagers\RelationManager::class, get_parent_class($manager));
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, class_uses_recursive($manager));
        }
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/ContactResource/Schemas/ContactTablePresenter.php'),
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
