<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\LinkResource\Pages\CreateLink;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class DebugLinkTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
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
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function test_debug_state()
    {
        $user = User::factory()->create(['status' => 'active', 'presence' => 'onsite', 'role' => 'developer']);
        Permission::create(['user_id' => $user->id, 'is_super_admin' => true]);

        $component = Livewire::actingAs($user)
            ->test(CreateLink::class)
            ->fillForm([
                'link' => 'external',
                'url' => 'https://example.com/fateh',
                'url_title' => 'Fateh Portal',
                'icon' => UploadedFile::fake()->image('icon.jpg'),
                'image' => UploadedFile::fake()->image('image.jpg'),
                'internal_url' => '',
                'extra' => ['10.0.0.1'],
            ]);

        dump($component->get('data.internal_url'));
        dump($component->get('data.extra'));

        $component->call('create');
        dump($component->errors()->all());
    }
}
