<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\AdResource\Pages\CreateAd;
use App\Filament\Resources\AdResource\Pages\EditAd;
use App\Filament\Resources\AdResource\Pages\ListAds;
use App\Models\Ad;
use App\Models\Permission;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AdResourceTest extends TestCase
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

    public function test_list_renders_and_shows_factory_ad_row()
    {
        $admin = $this->developer();
        $ad = Ad::factory()->create(['position' => 'Senior Laravel Developer']);

        Livewire::actingAs($admin)->test(ListAds::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$ad]);
    }

    public function test_create_ad_persists_position_gender_link_and_active()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateAd::class)
            ->fillForm([
                'position' => 'Backend Engineer',
                'gender' => 'Male',
                'link' => 'https://example.com/jobs/backend',
                'active' => true,
                'certificate' => 'Laravel Certification',
                'skill' => 'PHP, MySQL, Redis',
                'experience' => '3 years',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('ads', [
            'position' => 'Backend Engineer',
            'gender' => 'Male',
            'link' => 'https://example.com/jobs/backend',
            'active' => 1,
        ]);
    }

    public function test_create_ad_requires_a_valid_link()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateAd::class)
            ->fillForm([
                'position' => 'Frontend Engineer',
                'gender' => 'Female',
                'link' => '',
            ])
            ->call('create')
            ->assertHasFormErrors(['link' => 'required']);
    }

    public function test_create_ad_requires_gender()
    {
        $admin = $this->developer();

        Livewire::actingAs($admin)->test(CreateAd::class)
            ->fillForm([
                'link' => 'https://example.com/jobs/dev',
                'gender' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['gender' => 'required']);
    }

    public function test_edit_ad_updates_link_and_active()
    {
        $admin = $this->developer();
        $ad = Ad::factory()->create([
            'link' => 'https://example.com/old',
            'active' => true,
        ]);

        Livewire::actingAs($admin)->test(EditAd::class, ['record' => $ad->getRouteKey()])
            ->assertSuccessful()
            ->fillForm([
                'link' => 'https://example.com/new',
                'active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('https://example.com/new', $ad->fresh()->link);
        $this->assertFalse((bool) $ad->fresh()->active);
    }

    public function test_delete_ad_removes_row_from_database()
    {
        $admin = $this->developer();
        $ad = Ad::factory()->create();

        Livewire::actingAs($admin)->test(ListAds::class)
            ->callTableAction(DeleteAction::class, $ad);

        $this->assertDatabaseMissing('ads', ['id' => $ad->id]);
    }

    public function test_gender_filter_scopes_list_to_matching_ads()
    {
        $admin = $this->developer();
        $male = Ad::factory()->create(['gender' => 'Male', 'position' => 'Male Dev']);
        $female = Ad::factory()->create(['gender' => 'Female', 'position' => 'Female Dev']);

        Livewire::actingAs($admin)->test(ListAds::class)
            ->assertSuccessful()
            ->filterTable('gender', 'Male')
            ->assertCanSeeTableRecords([$male])
            ->assertCanNotSeeTableRecords([$female]);
    }

    public function test_active_filter_scopes_list_to_inactive_ads_only()
    {
        $admin = $this->developer();
        $active = Ad::factory()->create(['active' => true, 'position' => 'Active Ad']);
        $inactive = Ad::factory()->create(['active' => false, 'position' => 'Inactive Ad']);

        Livewire::actingAs($admin)->test(ListAds::class)
            ->assertSuccessful()
            ->filterTable('active', '0')
            ->assertCanSeeTableRecords([$inactive])
            ->assertCanNotSeeTableRecords([$active]);
    }

    public function test_has_experience_filter_scopes_list_to_ads_with_experience()
    {
        $admin = $this->developer();
        $withExperience = Ad::factory()->create(['experience' => '3 years', 'position' => 'With Experience']);
        $withoutExperience = Ad::factory()->create(['experience' => null, 'position' => 'Without Experience']);

        Livewire::actingAs($admin)->test(ListAds::class)
            ->assertSuccessful()
            ->filterTable('has_experience', true)
            ->assertCanSeeTableRecords([$withExperience])
            ->assertCanNotSeeTableRecords([$withoutExperience]);
    }

    public function test_has_certificate_filter_scopes_list_to_ads_with_certificate()
    {
        $admin = $this->developer();
        $withCertificate = Ad::factory()->create(['certificate' => 'Laravel Cert', 'position' => 'With Certificate']);
        $withoutCertificate = Ad::factory()->create(['certificate' => null, 'position' => 'Without Certificate']);

        Livewire::actingAs($admin)->test(ListAds::class)
            ->assertSuccessful()
            ->filterTable('has_certificate', true)
            ->assertCanSeeTableRecords([$withCertificate])
            ->assertCanNotSeeTableRecords([$withoutCertificate]);
    }

    public function test_has_skill_filter_scopes_list_to_ads_with_skill()
    {
        $admin = $this->developer();
        $withSkill = Ad::factory()->create(['skill' => 'PHP, MySQL', 'position' => 'With Skill']);
        $withoutSkill = Ad::factory()->create(['skill' => null, 'position' => 'Without Skill']);

        Livewire::actingAs($admin)->test(ListAds::class)
            ->assertSuccessful()
            ->filterTable('has_skill', true)
            ->assertCanSeeTableRecords([$withSkill])
            ->assertCanNotSeeTableRecords([$withoutSkill]);
    }

    public function test_created_at_filter_scopes_to_the_given_date_range()
    {
        $admin = $this->developer();

        $inRange = Ad::factory()->create(['position' => '0-in-range']);
        $inRange->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();

        $outOfRange = Ad::factory()->create(['position' => '0-out-of-range']);
        $outOfRange->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();

        Livewire::actingAs($admin)->test(ListAds::class)
            ->assertSuccessful()
            ->filterTable('created_at', ['from' => now()->subDays(10)->toDateString(), 'until' => now()->toDateString()])
            ->assertCanSeeTableRecords([$inRange])
            ->assertCanNotSeeTableRecords([$outOfRange]);
    }

    public function test_sorting_by_position_orders_records_correctly()
    {
        $admin = $this->developer();
        $first = Ad::factory()->create(['position' => 'AAA-test-position']);
        $second = Ad::factory()->create(['position' => 'ZZZ-test-position']);

        $component = Livewire::actingAs($admin)->test(ListAds::class)
            ->set('tableRecordsPerPage', 1000)
            ->sortTable('position')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('position', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_sorting_by_gender_orders_records_correctly()
    {
        $admin = $this->developer();
        $male = Ad::factory()->create(['gender' => 'Male', 'position' => '0-gender-sort-a']);
        $female = Ad::factory()->create(['gender' => 'Female', 'position' => '0-gender-sort-b']);

        $component = Livewire::actingAs($admin)->test(ListAds::class)
            ->set('tableRecordsPerPage', 1000)
            ->sortTable('gender')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$male->id, $female->id], array_values(array_intersect($ids, [$male->id, $female->id])));

        $component->sortTable('gender', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$female->id, $male->id], array_values(array_intersect($ids, [$male->id, $female->id])));
    }

    public function test_sorting_by_created_at_orders_records_correctly()
    {
        $admin = $this->developer();
        $older = Ad::factory()->create(['position' => '0-created-sort-a']);
        $older->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();
        $newer = Ad::factory()->create(['position' => '0-created-sort-b']);
        $newer->forceFill(['created_at' => now()])->saveQuietly();

        $component = Livewire::actingAs($admin)->test(ListAds::class)
            ->set('tableRecordsPerPage', 1000)
            ->sortTable('created_at')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], array_values(array_intersect($ids, [$older->id, $newer->id])));

        $component->sortTable('created_at', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$newer->id, $older->id], array_values(array_intersect($ids, [$older->id, $newer->id])));
    }

    public function test_sorting_by_id_orders_records_correctly()
    {
        $admin = $this->developer();
        $first = Ad::factory()->create(['position' => '0-id-sort-a']);
        $second = Ad::factory()->create(['position' => '0-id-sort-b']);

        $component = Livewire::actingAs($admin)->test(ListAds::class)
            ->set('tableRecordsPerPage', 1000)
            ->sortTable('id')
            ->assertSuccessful();

        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$first->id, $second->id], array_values(array_intersect($ids, [$first->id, $second->id])));

        $component->sortTable('id', 'desc')->assertSuccessful();
        $ids = $component->instance()->getTableRecords()->pluck('id')->values()->all();
        $this->assertSame([$second->id, $first->id], array_values(array_intersect($ids, [$first->id, $second->id])));
    }

    public function test_all_table_groups_render_without_error()
    {
        $admin = $this->developer();
        Ad::factory()->create();

        $component = Livewire::actingAs($admin)->test(ListAds::class)->assertSuccessful();

        foreach (['active', 'gender'] as $group) {
            $component->set('tableGrouping', $group)->assertSuccessful();
        }
    }

    public function test_pages_are_reparented_and_wired_for_exception_presentation(): void
    {
        $bases = [
            \App\Filament\Resources\AdResource\Pages\CreateAd::class => \App\Filament\Pages\CreateRecord::class,
            \App\Filament\Resources\AdResource\Pages\EditAd::class => \App\Filament\Pages\EditRecord::class,
            \App\Filament\Resources\AdResource\Pages\ListAds::class => \App\Filament\Pages\ListRecords::class,
        ];

        foreach ($bases as $page => $base) {
            $this->assertSame($base, get_parent_class($page));
        }

        $writers = [\App\Filament\Resources\AdResource\Pages\CreateAd::class, \App\Filament\Resources\AdResource\Pages\EditAd::class];

        foreach ($writers as $page) {
            $uses = class_uses_recursive($page);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesActionExceptions::class, $uses);
            $this->assertArrayHasKey(\App\Filament\Traits\HandlesSaveExceptions::class, $uses);
        }
    }

    public function test_group_and_query_closures_use_correct_param_names(): void
    {
        $files = [
            base_path('app/Filament/Resources/AdResource/Schemas/AdTablePresenter.php'),
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
