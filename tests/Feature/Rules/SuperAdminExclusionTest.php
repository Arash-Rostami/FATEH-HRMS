<?php

namespace Tests\Feature\Rules;

use App\Rules\SuperAdminExclusion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Once;
use Tests\TestCase;

class SuperAdminExclusionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Once::flush();
        Cache::forget('permission_modules');
    }

    private function seedModules(int $count): void
    {
        $modules = [];
        for ($i = 1; $i <= $count; $i++) {
            $modules["m{$i}"] = "Module {$i}";
        }
        Cache::put('permission_modules', $modules, now()->addDay());
    }

    private function fails(SuperAdminExclusion $rule, mixed $value): bool
    {
        $failed = false;
        $rule->validate('excluded_modules', $value, function () use (&$failed): void {
            $failed = true;
        });

        return $failed;
    }

    public function test_empty_array_passes(): void
    {
        $this->seedModules(10);

        $this->assertFalse($this->fails(new SuperAdminExclusion(), []));
    }

    public function test_non_array_value_is_treated_as_zero_excluded_and_passes(): void
    {
        $this->seedModules(10);

        $this->assertFalse($this->fails(new SuperAdminExclusion(), null));
        $this->assertFalse($this->fails(new SuperAdminExclusion(), 'not-an-array'));
    }

    public function test_two_module_count_uses_max_one_guard_so_one_excluded_passes_and_two_fails(): void
    {
        $this->seedModules(2);

        $rule = new SuperAdminExclusion();
        $this->assertFalse($this->fails($rule, ['m1']));
        $this->assertTrue($this->fails($rule, ['m1', 'm2']));
    }

    public function test_five_modules_caps_excluded_at_one_with_strict_greater_than_boundary(): void
    {
        $this->seedModules(5);

        $rule = new SuperAdminExclusion();
        $this->assertFalse($this->fails($rule, ['m1']));
        $this->assertTrue($this->fails($rule, ['m1', 'm2']));
    }

    public function test_ten_modules_caps_excluded_at_two_with_strict_greater_than_boundary(): void
    {
        $this->seedModules(10);

        $rule = new SuperAdminExclusion();
        $this->assertFalse($this->fails($rule, ['m1', 'm2']));
        $this->assertTrue($this->fails($rule, ['m1', 'm2', 'm3']));
    }

    public function test_message_renders_excluded_total_and_max_placeholders(): void
    {
        $this->app->setLocale('fa');
        $this->seedModules(10);

        $message = null;
        (new SuperAdminExclusion())
            ->validate('excluded_modules', ['m1', 'm2', 'm3'], function (string $m) use (&$message): void {
                $message = $m;
            });

        $this->assertNotNull($message);
        $this->assertStringContainsString('3', $message);
        $this->assertStringContainsString('10', $message);
        $this->assertStringContainsString('2', $message);
        $this->assertStringNotContainsString(':excluded', $message);
        $this->assertStringNotContainsString(':total', $message);
        $this->assertStringNotContainsString(':max', $message);
    }
}