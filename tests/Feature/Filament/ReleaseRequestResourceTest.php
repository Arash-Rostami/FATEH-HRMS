<?php

namespace Tests\Feature\Filament;

use App\Enums\ReleaseRequestStatus;
use App\Enums\ReleaseRequestType;
use App\Filament\Resources\ReleaseRequestResource\Pages\CreateReleaseRequest;
use App\Filament\Resources\ReleaseRequestResource\Pages\EditReleaseRequest;
use App\Filament\Resources\ReleaseRequestResource\Pages\ListReleaseRequests;
use App\Filament\Resources\ReleaseRequestResource\Schemas\ReleaseRequestFormPresenter;
use App\Models\Permission;
use App\Models\ReleaseRequest;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReleaseRequestResourceTest extends TestCase
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

    public function test_edit_tracked_file_columns_delete_only_the_dropped_attachments(): void
    {
        $probe = new class { use \App\Filament\Traits\HandlesDroppedFiles { trackedFilePaths as public; deleteDroppedFiles as public; } };
        $tracked = (fn () => $this->trackedFileColumns())->call(new EditReleaseRequest());
        Storage::fake('public');
        foreach (['release_request/attachments/old.pdf', 'release_request/attachments/kept.pdf'] as $path) {
            Storage::disk('public')->put($path, 'x');
        }
        $record = \App\Models\ReleaseRequest::factory()->create(['attachments' => [
            ['path' => 'release_request/attachments/old.pdf', 'name' => 'old.pdf'],
            ['path' => 'release_request/attachments/kept.pdf', 'name' => 'kept.pdf'],
        ]]);
        $before = $probe->trackedFilePaths($record, $tracked);
        $this->assertEqualsCanonicalizing(['release_request/attachments/old.pdf', 'release_request/attachments/kept.pdf'], $before);

        $record->update(['attachments' => [['path' => 'release_request/attachments/kept.pdf', 'name' => 'kept.pdf']]]);
        $probe->deleteDroppedFiles($record, $before, $tracked);

        Storage::disk('public')->assertMissing('release_request/attachments/old.pdf');
        Storage::disk('public')->assertExists('release_request/attachments/kept.pdf');
    }

    public function test_list_renders_and_shows_factory_record()
    {
        $admin = $this->developer();
        $record = ReleaseRequest::factory()->create(['title' => '0 test list renders']);

        Livewire::actingAs($admin)->test(ListReleaseRequests::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$record]);
    }

    public function test_create_release_request_persists_title_type_and_status()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateReleaseRequest::class)
            ->fillForm([
                'type' => ReleaseRequestType::Bug->value,
                'status' => ReleaseRequestStatus::Open->value,
                'title' => 'New bug report title',
                'body' => 'Steps to reproduce the bug here',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('release_requests', [
            'user_id' => $admin->id,
            'type' => ReleaseRequestType::Bug->value,
            'status' => ReleaseRequestStatus::Open->value,
            'title' => 'New bug report title',
        ]);
    }

    public function test_create_validation_requires_title()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateReleaseRequest::class)
            ->fillForm([
                'type' => ReleaseRequestType::Bug->value,
                'status' => ReleaseRequestStatus::Open->value,
                'title' => '',
                'body' => 'valid body text here',
            ])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required']);
    }

    public function test_edit_release_request_updates_title()
    {
        $admin = $this->developer();
        $record = ReleaseRequest::factory()->create(['title' => 'original title here']);

        Livewire::actingAs($admin)->test(EditReleaseRequest::class, ['record' => $record->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['title' => 'updated title text'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('updated title text', $record->fresh()->title);
    }

    public function test_delete_table_action_removes_record()
    {
        $admin = $this->developer();
        $record = ReleaseRequest::factory()->create();

        Livewire::actingAs($admin)->test(ListReleaseRequests::class)
            ->callTableAction(DeleteAction::class, $record);

        $this->assertDatabaseMissing('release_requests', ['id' => $record->id]);
    }

    public function test_status_filter_scopes_list_to_matching_records()
    {
        $admin = $this->developer();
        $open = ReleaseRequest::factory()->create(['status' => ReleaseRequestStatus::Open->value]);
        $resolved = ReleaseRequest::factory()->create(['status' => ReleaseRequestStatus::Resolved->value]);

        Livewire::actingAs($admin)->test(ListReleaseRequests::class)
            ->filterTable('status', ReleaseRequestStatus::Open->value)
            ->assertCanSeeTableRecords([$open])
            ->assertCanNotSeeTableRecords([$resolved]);
    }

    public function test_type_filter_scopes_list_to_matching_records()
    {
        $admin = $this->developer();
        $bug = ReleaseRequest::factory()->create(['type' => ReleaseRequestType::Bug->value]);
        $support = ReleaseRequest::factory()->create(['type' => ReleaseRequestType::Support->value]);

        Livewire::actingAs($admin)->test(ListReleaseRequests::class)
            ->filterTable('type', ReleaseRequestType::Bug->value)
            ->assertCanSeeTableRecords([$bug])
            ->assertCanNotSeeTableRecords([$support]);
    }

    public function test_user_filter_scopes_list_to_records_created_by_selected_user()
    {
        $admin = $this->developer();
        $userA = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $userB = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $matching = ReleaseRequest::factory()->create(['user_id' => $userA->id]);
        $nonMatching = ReleaseRequest::factory()->create(['user_id' => $userB->id]);

        Livewire::actingAs($admin)->test(ListReleaseRequests::class)
            ->filterTable('user_id', $userA->id)
            ->assertCanSeeTableRecords([$matching])
            ->assertCanNotSeeTableRecords([$nonMatching]);
    }

    public function test_created_at_filter_scopes_list_to_records_in_date_range()
    {
        $admin = $this->developer();

        $inRange = ReleaseRequest::factory()->create(['title' => '0-created-in-range']);
        $inRange->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();

        $outOfRange = ReleaseRequest::factory()->create(['title' => '0-created-out-of-range']);
        $outOfRange->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();

        Livewire::actingAs($admin)->test(ListReleaseRequests::class)
            ->filterTable('created_at', ['from' => now()->subDays(10)->toDateString(), 'until' => now()->toDateString()])
            ->assertCanSeeTableRecords([$inRange])
            ->assertCanNotSeeTableRecords([$outOfRange]);
    }

    public function test_reject_action_sets_status_and_optional_response()
    {
        $admin = $this->developer();
        $record = ReleaseRequest::factory()->create(['status' => ReleaseRequestStatus::Open->value]);

        Livewire::actingAs($admin)->test(ListReleaseRequests::class)
            ->callTableAction('reject', $record, data: ['response' => 'خارج از ضوابط']);

        $fresh = $record->fresh();
        $this->assertSame(ReleaseRequestStatus::Rejected->value, $fresh->status);
        $this->assertSame('خارج از ضوابط', $fresh->response);
    }

    public function test_reject_action_does_not_require_a_response()
    {
        $admin = $this->developer();
        $record = ReleaseRequest::factory()->create(['status' => ReleaseRequestStatus::Open->value]);

        Livewire::actingAs($admin)->test(ListReleaseRequests::class)
            ->callTableAction('reject', $record, data: ['response' => ''])
            ->assertHasNoTableActionErrors();

        $fresh = $record->fresh();
        $this->assertSame(ReleaseRequestStatus::Rejected->value, $fresh->status);
        $this->assertNull($fresh->response);
    }

    public function test_reject_action_is_hidden_for_already_rejected_records()
    {
        $admin = $this->developer();
        $record = ReleaseRequest::factory()->create([
            'status' => ReleaseRequestStatus::Rejected->value,
            'response' => 'قبلاً رد شده',
        ]);

        Livewire::actingAs($admin)->test(ListReleaseRequests::class)
            ->assertTableActionHidden('reject', $record);
    }

    public function test_reject_action_is_hidden_for_view_only_admin()
    {
        $viewOnly = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'admin']);
        Permission::create([
            'user_id' => $viewOnly->id,
            'is_super_admin' => false,
            'abilities' => [['module' => 'release_request', 'actions' => ['view']]],
        ]);
        $record = ReleaseRequest::factory()->create(['status' => ReleaseRequestStatus::Open->value]);

        Livewire::actingAs($viewOnly)->test(ListReleaseRequests::class)
            ->assertTableActionHidden('reject', $record);

        $this->assertSame(ReleaseRequestStatus::Open->value, $record->fresh()->status);
    }

    public function test_status_select_excludes_rejected_option()
    {
        $options = ReleaseRequestFormPresenter::status()->getOptions();

        $this->assertArrayNotHasKey(ReleaseRequestStatus::Rejected->value, $options);
        $this->assertArrayHasKey(ReleaseRequestStatus::Open->value, $options);
    }

    public function test_edit_page_saves_without_error_when_record_already_rejected()
    {
        $admin = $this->developer();
        $record = ReleaseRequest::factory()->create([
            'status' => ReleaseRequestStatus::Rejected->value,
            'response' => 'خارج از ضوابط',
            'title' => 'original rejected title',
        ]);

        Livewire::actingAs($admin)->test(EditReleaseRequest::class, ['record' => $record->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['title' => 'updated rejected title'])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $record->fresh();
        $this->assertSame('updated rejected title', $fresh->title);
        $this->assertSame(ReleaseRequestStatus::Rejected->value, $fresh->status);
        $this->assertSame('خارج از ضوابط', $fresh->response);
    }

    public function test_response_field_is_optionally_editable_for_any_status()
    {
        $admin = $this->developer();
        $record = ReleaseRequest::factory()->create(['status' => ReleaseRequestStatus::Resolved->value]);

        Livewire::actingAs($admin)->test(EditReleaseRequest::class, ['record' => $record->getRouteKey()])
            ->assertSuccessful()
            ->fillForm(['response' => 'انجام شد، متشکریم از پیشنهاد شما'])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $record->fresh();
        $this->assertSame(ReleaseRequestStatus::Resolved->value, $fresh->status);
        $this->assertSame('انجام شد، متشکریم از پیشنهاد شما', $fresh->response);
    }


    public function test_sorting_by_created_at_and_id_orders_records_correctly(): void
    {
        $admin = $this->developer();
        $older = ReleaseRequest::factory()->create(['title' => '0-sort-created-a']);
        $older->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();
        $newer = ReleaseRequest::factory()->create(['title' => '0-sort-created-b']);
        $newer->forceFill(['created_at' => now()])->saveQuietly();

        $component = Livewire::actingAs($admin)->test(ListReleaseRequests::class)
            ->sortTable('created_at')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('id')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('id', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));
    }

    public function test_sorting_by_status_and_type_orders_records_correctly(): void
    {
        $admin = $this->developer();
        $first = ReleaseRequest::factory()->create([
            'status' => ReleaseRequestStatus::InReview->value,
            'type' => ReleaseRequestType::Bug->value,
        ]);
        $second = ReleaseRequest::factory()->create([
            'status' => ReleaseRequestStatus::Resolved->value,
            'type' => ReleaseRequestType::Support->value,
        ]);

        $component = Livewire::actingAs($admin)->test(ListReleaseRequests::class)
            ->sortTable('status')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('status', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('type')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('type', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sorting_by_user_orders_records_correctly(): void
    {
        $admin = $this->developer();
        $userA = User::factory()->create(['status' => 'active', 'role' => 'user', 'name' => 'AAA-sort-owner']);
        $userB = User::factory()->create(['status' => 'active', 'role' => 'user', 'name' => 'ZZZ-sort-owner']);
        $first = ReleaseRequest::factory()->create(['user_id' => $userA->id]);
        $second = ReleaseRequest::factory()->create(['user_id' => $userB->id]);

        $component = Livewire::actingAs($admin)->test(ListReleaseRequests::class)
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
            \App\Filament\Resources\ReleaseRequestResource\Pages\CreateReleaseRequest::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\ReleaseRequestResource\Pages\EditReleaseRequest::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\ReleaseRequestResource\Pages\ListReleaseRequests::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\ReleaseRequestResource\Pages\CreateReleaseRequest::class, \App\Filament\Resources\ReleaseRequestResource\Pages\EditReleaseRequest::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }
    }
}