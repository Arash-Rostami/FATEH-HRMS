<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\PostResource\Pages\CreatePost;
use App\Filament\Resources\PostResource\Pages\EditPost;
use App\Filament\Resources\PostResource\Pages\ListPosts;
use App\Models\Post;
use App\Models\Permission;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        $this->app->setLocale('fa');
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

    public function test_list_renders_and_shows_factory_post()
    {
        $admin = $this->developer();
        $post = Post::factory()->create(['user_id' => $admin->id]);

        Livewire::actingAs($admin)->test(ListPosts::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$post]);
    }

    public function test_active_toggle_and_bulk_visibility_actions_set_active_and_leave_pinned_untouched()
    {
        $admin = $this->developer();
        $pinned = Post::factory()->create(['user_id' => $admin->id, 'pinned' => 1, 'active' => true]);
        $plain = Post::factory()->create(['user_id' => $admin->id, 'pinned' => 0, 'active' => true]);

        $component = Livewire::actingAs($admin)->test(ListPosts::class)
            ->call('updateTableColumnState', 'active', (string) $plain->getKey(), false);

        $this->assertFalse((bool) $plain->fresh()->active);
        $component->assertCanSeeTableRecords([$plain->fresh(), $pinned]);

        $component->callTableBulkAction('bulk_deactivate', [$pinned, $plain]);
        $this->assertFalse((bool) $pinned->fresh()->active);
        $this->assertSame(1, (int) $pinned->fresh()->pinned);
        $this->assertSame(0, (int) $plain->fresh()->pinned);

        Livewire::actingAs($admin)->test(ListPosts::class)
            ->callTableBulkAction('bulk_activate', [$pinned, $plain]);
        $this->assertTrue((bool) $pinned->fresh()->active);
        $this->assertTrue((bool) $plain->fresh()->active);
        $this->assertSame(1, (int) $pinned->fresh()->pinned);
    }

    public function test_audience_filter_scopes_list_to_posts_shared_with_the_department()
    {
        $admin = $this->developer();
        $shared = Post::factory()->create(['user_id' => $admin->id, 'departments' => ['ZZ-PA']]);
        $public = Post::factory()->create(['user_id' => $admin->id, 'departments' => null]);

        Livewire::actingAs($admin)->test(ListPosts::class)
            ->filterTable('audience', 'ZZ-PA')
            ->assertCanSeeTableRecords([$shared])
            ->assertCanNotSeeTableRecords([$public]);
    }

    public function test_create_post_persists_title_body_and_pinned()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreatePost::class)
            ->fillForm([
                'title' => 'اعلانی جدید برای تست',
                'body' => 'محتوای اعلان جهت بررسی ذخیره‌سازی',
                'pinned' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::query()->where('user_id', $admin->id)->latest('id')->first();
        $this->assertNotNull($post);
        $this->assertStringContainsString('اعلانی جدید برای تست', $post->title);
        $this->assertStringContainsString('محتوای اعلان جهت بررسی ذخیره‌سازی', $post->body);
        $this->assertSame(1, (int) $post->pinned);
    }

    public function test_create_post_requires_title()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreatePost::class)
            ->fillForm([
                'title' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
                'body' => 'محتوای اعلان بدون عنوان',
            ])
            ->call('create')
            ->assertHasFormErrors(['title']);
    }

    public function test_create_post_requires_body()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreatePost::class)
            ->fillForm([
                'title' => 'اعلان بدون محتوا',
                'body' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
            ])
            ->call('create')
            ->assertHasFormErrors(['body']);
    }

    public function test_edit_post_updates_title_and_pinned()
    {
        $admin = $this->developer();
        $post = Post::factory()->create(['user_id' => $admin->id, 'pinned' => 0]);

        $component = Livewire::actingAs($admin)->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->assertSuccessful();

        $this->assertStringContainsString($post->title, (string) $component->instance()->getHeading());

        $component->fillForm([
                'title' => 'عنوان ویرایش‌شده اعلان',
                'pinned' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $refreshed = $post->fresh();
        $this->assertStringContainsString('عنوان ویرایش‌شده اعلان', $refreshed->title);
        $this->assertSame(1, (int) $refreshed->pinned);
    }

    public function test_edit_heading_strips_html_tags_from_rich_title()
    {
        $admin = $this->developer();
        $post = Post::factory()->create([
            'user_id' => $admin->id,
            'title' => '<p><strong>☕️ عنوان اعلان html</strong></p>',
        ]);

        $heading = (string) Livewire::actingAs($admin)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->assertSuccessful()
            ->instance()->getHeading();

        $this->assertStringContainsString('☕️ عنوان اعلان html', $heading);
        $this->assertStringNotContainsString('<p>', $heading);
        $this->assertStringNotContainsString('<strong>', $heading);
    }

    public function test_view_modal_heading_strips_html_tags_from_rich_title()
    {
        $this->app->setLocale('fa');
        $admin = $this->developer();
        $post = Post::factory()->create([
            'user_id' => $admin->id,
            'title' => '<p><strong>☕️ عنوان اعلان html</strong></p>',
        ]);

        $component = Livewire::actingAs($admin)
            ->test(ListPosts::class)
            ->assertSuccessful()
            ->mountTableAction('view', $post);

        $action = $component->instance()->getMountedAction();
        $this->assertNotNull($action, 'view table action should mount');

        $heading = (string) $action->getModalHeading();

        $this->assertStringContainsString('نمایش', $heading);
        $this->assertStringContainsString('☕️ عنوان اعلان html', $heading);
        $this->assertStringNotContainsString('<p>', $heading);
        $this->assertStringNotContainsString('<strong>', $heading);
    }

    public function test_delete_post_action_removes_record()
    {
        $admin = $this->developer();
        $post = Post::factory()->create(['user_id' => $admin->id]);

        Livewire::actingAs($admin)->test(ListPosts::class)
            ->callTableAction(DeleteAction::class, $post);

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_pinned_filter_scopes_list_to_pinned_posts_only()
    {
        $admin = $this->developer();
        $pinned = Post::factory()->pinned()->create(['user_id' => $admin->id]);
        $normal = Post::factory()->notPinned()->create(['user_id' => $admin->id]);

        Livewire::actingAs($admin)->test(ListPosts::class)
            ->assertSuccessful()
            ->filterTable('pinned')
            ->assertCanSeeTableRecords([$pinned])
            ->assertCanNotSeeTableRecords([$normal]);
    }

    public function test_has_image_filter_scopes_list_to_posts_with_image()
    {
        $admin = $this->developer();
        $withImage = Post::factory()->create(['user_id' => $admin->id, 'image' => 'post/image.jpg']);
        $withoutImage = Post::factory()->create(['user_id' => $admin->id, 'image' => null]);

        Livewire::actingAs($admin)->test(ListPosts::class)
            ->assertSuccessful()
            ->filterTable('has_image')
            ->assertCanSeeTableRecords([$withImage])
            ->assertCanNotSeeTableRecords([$withoutImage]);
    }

    public function test_created_at_filter_scopes_to_the_given_date_range()
    {
        $admin = $this->developer();

        $inRange = Post::factory()->create(['user_id' => $admin->id]);
        $inRange->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();

        $outOfRange = Post::factory()->create(['user_id' => $admin->id]);
        $outOfRange->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();

        Livewire::actingAs($admin)->test(ListPosts::class)
            ->assertSuccessful()
            ->filterTable('created_at', ['from' => now()->subDays(10)->toDateString(), 'until' => now()->toDateString()])
            ->assertCanSeeTableRecords([$inRange])
            ->assertCanNotSeeTableRecords([$outOfRange]);
    }

    public function test_sorting_by_title_id_created_at_and_user_orders_records_correctly()
    {
        $admin = $this->developer();
        $userA = User::factory()->create(['name' => '0-AAA-sort-user']);
        $userZ = User::factory()->create(['name' => '0-ZZZ-sort-user']);

        $first = Post::factory()->create(['user_id' => $userA->id, 'title' => '0-AAA-sort-post']);
        $first->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();
        $second = Post::factory()->create(['user_id' => $userZ->id, 'title' => '0-ZZZ-sort-post']);
        $second->forceFill(['created_at' => now()])->saveQuietly();

        $component = Livewire::actingAs($admin)->test(ListPosts::class)
            ->set('tableRecordsPerPage', 1000)
            ->sortTable('title')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('title', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('id')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('id', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('created_at')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('user.name')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('user.name', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\PostResource\Pages\CreatePost::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\PostResource\Pages\EditPost::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\PostResource\Pages\ListPosts::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\PostResource\Pages\CreatePost::class, \App\Filament\Resources\PostResource\Pages\EditPost::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }
    }

    public function test_edit_tracked_file_columns_delete_only_the_replaced_files(): void
    {
        $probe = new class { use \App\Filament\Traits\HandlesDroppedFiles { trackedFilePaths as public; deleteDroppedFiles as public; } };
        $tracked = (fn () => $this->trackedFileColumns())->call(new \App\Filament\Resources\PostResource\Pages\EditPost());
        Storage::fake('public');
        Storage::disk('public')->put('post/image/old.jpg', 'x');
        Storage::disk('public')->put('post/image/new.jpg', 'x');
        $record = \App\Models\Post::factory()->create(['image' => 'post/image/old.jpg']);
        $before = $probe->trackedFilePaths($record, $tracked);
        $this->assertSame(['post/image/old.jpg'], $before);

        $record->update(['image' => 'post/image/new.jpg']);
        $probe->deleteDroppedFiles($record, $before, $tracked);

        Storage::disk('public')->assertMissing('post/image/old.jpg');
        Storage::disk('public')->assertExists('post/image/new.jpg');
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/PostResource/Schemas/PostTablePresenter.php'),
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
