<?php

namespace Tests\Feature\Traits;

use App\Filament\Resources\PostResource\Pages\EditPost;
use App\Filament\Resources\PostResource\Pages\ListPosts;
use App\Filament\Resources\TaskResource\Pages\EditTask;
use App\Models\Permission;
use App\Models\Post;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentHeaderActionsTest extends TestCase
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

    public function test_list_page_header_actions_return_guide_and_create_actions(): void
    {
        $admin = $this->developer();

        $component = Livewire::actingAs($admin)->test(ListPosts::class);
        $instance = $component->instance();
        $method = new \ReflectionMethod($instance, 'getHeaderActions');
        $method->setAccessible(true);
        $actions = $method->invoke($instance);
        $names = array_map(fn($a) => $a->getName(), $actions);

        $this->assertSame(['moduleGuide', 'create'], $names);
    }

    public function test_edit_page_header_actions_return_create_view_and_delete_actions(): void
    {
        $admin = $this->developer();
        $post = Post::factory()->create(['user_id' => $admin->id]);

        $component = Livewire::actingAs($admin)->test(EditPost::class, ['record' => $post->getRouteKey()]);
        $instance = $component->instance();
        $method = new \ReflectionMethod($instance, 'getHeaderActions');
        $method->setAccessible(true);
        $actions = $method->invoke($instance);
        $names = array_map(fn($a) => $a->getName(), $actions);

        $this->assertSame(['create', 'view', 'delete'], $names);
    }

    public function test_edit_page_header_actions_add_restore_only_for_soft_deletable_models(): void
    {
        $admin = $this->developer();
        $task = Task::factory()->ownedBy($admin)->create();

        $component = Livewire::actingAs($admin)->test(EditTask::class, ['record' => $task->getRouteKey()]);
        $instance = $component->instance();
        $method = new \ReflectionMethod($instance, 'getHeaderActions');
        $method->setAccessible(true);
        $actions = $method->invoke($instance);
        $names = array_map(fn($a) => $a->getName(), $actions);

        $this->assertSame(['create', 'view', 'delete', 'restore'], $names);
    }
}