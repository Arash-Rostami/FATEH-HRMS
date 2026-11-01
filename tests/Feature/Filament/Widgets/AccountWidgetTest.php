<?php

namespace Tests\Feature\Filament\Widgets;

use App\Filament\Resources\UserResource\Enums\UserRole;
use App\Filament\Widgets\AccountWidget;
use App\Models\Department;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class AccountWidgetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->useMysql();
        DB::beginTransaction();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Auth::logout();
        Cache::flush();
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

    private function getViewData(): array
    {
        $widget = (new ReflectionClass(AccountWidget::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod($widget, 'getViewData');
        $method->setAccessible(true);

        return $method->invoke($widget);
    }

    private function actorWithProfile(string $role): User
    {
        $department = Department::factory()->create([
            'code' => 'T' . substr(uniqid(), -8),
            'name' => 'Test Department',
            'description' => 'Test Unit',
        ]);
        $user = User::factory()->create(['status' => 'active', 'role' => $role]);
        Profile::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->code,
            'personnel_id' => 'PC-' . uniqid(),
        ]);

        return $user->refresh();
    }

    public function test_get_view_data_returns_expected_keys_and_values(): void
    {
        $user = $this->actorWithProfile('developer');
        $this->actingAs($user);

        $data = $this->getViewData();

        $this->assertSame(['user', 'greeting', 'roleLabel', 'department', 'jalaliDate'], array_keys($data));
        $this->assertTrue($data['user']->is($user));
        $this->assertSame(UserRole::Developer->getLabel(), $data['roleLabel']);
        $this->assertSame($user->profile->department->displayLabel(), $data['department']);
        $this->assertIsString($data['greeting']);
        $this->assertNotEmpty($data['greeting']);
        $this->assertIsString($data['jalaliDate']);
        $this->assertStringContainsString(' | ', $data['jalaliDate']);
    }

    public function test_role_label_branches_per_user_role(): void
    {
        foreach (UserRole::cases() as $role) {
            $user = $this->actorWithProfile($role->value);
            $this->actingAs($user);

            $data = $this->getViewData();

            $this->assertSame($role->getLabel(), $data['roleLabel'], "role {$role->value}");

            Auth::logout();
        }
    }

    public function test_role_label_is_null_when_role_is_not_a_valid_user_role(): void
    {
        $user = $this->actorWithProfile('unknown-role');
        $this->actingAs($user);

        $data = $this->getViewData();

        $this->assertNull($data['roleLabel']);
    }

    public function test_department_is_null_when_user_has_no_profile(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->actingAs($user);

        $data = $this->getViewData();

        $this->assertNull($data['department']);
        $this->assertSame(UserRole::User->getLabel(), $data['roleLabel']);
    }

    public function test_jalali_date_is_persian_formatted_current_date(): void
    {
        $user = $this->actorWithProfile('admin');
        $this->actingAs($user);

        $data = $this->getViewData();

        $expected = convertToPersian(Jalalian::now()->format('l | d F'));
        $this->assertSame($expected, $data['jalaliDate']);
        $this->assertStringContainsString(convertToPersian(Jalalian::now()->format('F')), $data['jalaliDate']);
    }
}