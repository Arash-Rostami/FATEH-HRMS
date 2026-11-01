<?php

namespace Tests\Feature\Traits;

use App\Filament\Resources\PostResource\Pages\EditPost;
use App\Filament\Resources\ReservationPolicyResource\Pages\EditPolicy;
use App\Models\Permission;
use App\Models\Post;
use App\Models\ReservationPolicy;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentEditHeadingTest extends TestCase
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

    public function test_column_based_record_title_renders_in_edit_heading()
    {
        $admin = $this->developer();
        $post = Post::factory()->create(['user_id' => $admin->id, 'title' => 'تیتر نمونه']);

        $heading = Livewire::actingAs($admin)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->instance()->getHeading();

        $this->assertSame('ویرایش اعلانات: تیتر نمونه', (string) $heading);
    }

    public function test_composite_record_title_renders_in_edit_heading()
    {
        $admin = $this->developer();

        $type = 'zz-heading-' . uniqid();
        ReservationPolicy::factory()->create([
            'resource_type' => $type,
            'key' => 'window_days',
            'value' => [21],
        ]);

        $heading = Livewire::actingAs($admin)
            ->test(EditPolicy::class, ['record' => $type])
            ->instance()->getHeading();

        $this->assertSame('ویرایش قوانین رزرو: ' . $type, (string) $heading);
    }

    public function test_heading_falls_back_to_default_when_record_title_equals_label()
    {
        $admin = $this->developer();
        $post = Post::factory()->create(['user_id' => $admin->id, 'title' => 'اعلانات']);

        $heading = Livewire::actingAs($admin)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->instance()->getHeading();

        $this->assertSame('ویرایش اعلانات', (string) $heading);
        $this->assertStringNotContainsString(': اعلانات', (string) $heading);
    }
}