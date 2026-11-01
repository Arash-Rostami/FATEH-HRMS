<?php

namespace Tests\Feature\Traits;

use App\Filament\Resources\PostResource;
use App\Filament\Resources\PostResource\Pages\CreatePost;
use App\Filament\Resources\PostResource\Pages\EditPost;
use App\Models\Post;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentPageBehaviorTest extends TestCase
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

    public function test_create_post_redirect_url_resolves_to_resource_index()
    {
        $admin = $this->developer();

        $instance = Livewire::actingAs($admin)->test(CreatePost::class)->instance();
        $method = new \ReflectionMethod($instance, 'getRedirectUrl');
        $method->setAccessible(true);

        $this->assertSame(PostResource::getUrl('index'), $method->invoke($instance));
    }

    public function test_edit_post_redirect_url_resolves_to_resource_index()
    {
        $admin = $this->developer();
        $post = Post::factory()->create(['user_id' => $admin->id]);

        $instance = Livewire::actingAs($admin)->test(EditPost::class, ['record' => $post->getRouteKey()])->instance();
        $method = new \ReflectionMethod($instance, 'getRedirectUrl');
        $method->setAccessible(true);

        $this->assertSame(PostResource::getUrl('index'), $method->invoke($instance));
    }

    public function test_create_post_created_notification_title_uses_fa_translation()
    {
        $admin = $this->developer();

        $instance = Livewire::actingAs($admin)->test(CreatePost::class)->instance();
        $method = new \ReflectionMethod($instance, 'getCreatedNotificationTitle');
        $method->setAccessible(true);

        $this->assertSame('رکورد با موفقیت ایجاد شد.', $method->invoke($instance));
    }

    public function test_edit_post_saved_notification_title_uses_fa_translation()
    {
        $admin = $this->developer();
        $post = Post::factory()->create(['user_id' => $admin->id]);

        $instance = Livewire::actingAs($admin)->test(EditPost::class, ['record' => $post->getRouteKey()])->instance();
        $method = new \ReflectionMethod($instance, 'getSavedNotificationTitle');
        $method->setAccessible(true);

        $this->assertSame('اطلاعات رکورد با موفقیت ذخیره شد.', $method->invoke($instance));
    }
}