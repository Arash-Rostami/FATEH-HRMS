<?php

namespace Tests\Feature\Services;

use App\Livewire\Admin\ManagePreferences;
use App\Services\FilamentMenuService;
use Filament\Actions\Action;
use ReflectionMethod;
use Tests\TestCase;

class FilamentMenuServiceTest extends TestCase
{
    public function test_get_actions_returns_three_actions_starting_with_group_label(): void
    {
        $actions = FilamentMenuService::getActions();

        $this->assertCount(3, $actions);
        $this->assertInstanceOf(Action::class, $actions[0]);
        $this->assertInstanceOf(Action::class, $actions[1]);
        $this->assertInstanceOf(Action::class, $actions[2]);

        $this->assertSame('grp_' . md5('پیمایش'), $actions[0]->getName());
        $this->assertSame('پیمایش', $actions[0]->getLabel());
        $this->assertTrue($actions[0]->isDisabled());
    }

    public function test_dashboard_action_wires_name_label_icon_url_and_new_tab(): void
    {
        $action = FilamentMenuService::dashboard();

        $this->assertSame('dashboard', $action->getName());
        $this->assertSame('پنل کاربر', $action->getLabel());
        $this->assertSame('heroicon-o-squares-2x2', $action->getIcon());
        $this->assertTrue($action->shouldOpenUrlInNewTab());
        $this->assertSame(route('dashboard'), $action->getUrl());
    }

    public function test_preferences_action_wires_name_label_icon_and_url(): void
    {
        $action = FilamentMenuService::preferences();

        $this->assertSame('preferences', $action->getName());
        $this->assertSame('تنظیمات', $action->getLabel());
        $this->assertSame('heroicon-o-adjustments-horizontal', $action->getIcon());
        $this->assertFalse($action->shouldOpenUrlInNewTab());
        $this->assertSame(ManagePreferences::getUrl(), $action->getUrl());
    }

    public function test_group_action_uses_md5_hashed_name_and_is_disabled_with_pointer_events_off(): void
    {
        $label = 'بخش کاربردی';
        $action = $this->invokeGroup($label);

        $this->assertSame('grp_' . md5($label), $action->getName());
        $this->assertSame($label, $action->getLabel());
        $this->assertNull($action->getIcon());
        $this->assertTrue($action->isDisabled());
        $this->assertNull($action->getUrl());

        $extra = $action->getExtraAttributes();
        $this->assertStringContainsString('pointer-events-none', $extra['class'] ?? '');
        $this->assertStringContainsString('select-none', $extra['class'] ?? '');
        $this->assertStringContainsString('opacity-50', $extra['class'] ?? '');
        $this->assertStringContainsString('font-semibold', $extra['class'] ?? '');
    }

    public function test_group_action_with_empty_label_produces_consistent_md5_name(): void
    {
        $action = $this->invokeGroup('');

        $this->assertSame('grp_' . md5(''), $action->getName());
        $this->assertSame('', $action->getLabel());
        $this->assertTrue($action->isDisabled());
    }

    public function test_get_actions_dashboard_and_preferences_are_distinct_instances_per_call(): void
    {
        $first = FilamentMenuService::getActions();
        $second = FilamentMenuService::getActions();

        $this->assertCount(3, $first);
        $this->assertNotSame($first[0], $second[0]);
        $this->assertNotSame($first[1], $second[1]);
        $this->assertNotSame($first[2], $second[2]);

        $this->assertNotSame(FilamentMenuService::dashboard(), FilamentMenuService::dashboard());
        $this->assertNotSame(FilamentMenuService::preferences(), FilamentMenuService::preferences());
    }

    private function invokeGroup(string $label): Action
    {
        $method = new ReflectionMethod(FilamentMenuService::class, 'group');
        $method->setAccessible(true);

        return $method->invoke(null, $label);
    }
}