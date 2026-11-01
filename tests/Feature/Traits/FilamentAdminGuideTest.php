<?php

namespace Tests\Feature\Traits;

use App\Filament\Resources\PostResource;
use App\Traits\FilamentAdminGuide;
use Filament\Actions\Action;
use ReflectionObject;
use Tests\TestCase;

class FilamentAdminGuideTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->setLocale('fa');
    }

    public function test_guide_tabs_returns_empty_array_when_host_declares_no_guide_property(): void
    {
        $host = new class { use FilamentAdminGuide; };

        $this->assertSame([], $host::guideTabs());
    }

    public function test_guide_tabs_returns_non_empty_array_with_label_icon_view_keys(): void
    {
        $tabs = PostResource::guideTabs();

        $this->assertNotEmpty($tabs);

        foreach ($tabs as $tab) {
            $this->assertIsArray($tab);
            $this->assertArrayHasKey('label', $tab);
            $this->assertArrayHasKey('icon', $tab);
            $this->assertArrayHasKey('view', $tab);
            $this->assertIsString($tab['label']);
            $this->assertNotSame('', $tab['label']);
            $this->assertTrue(view()->exists($tab['view']), "Guide tab view '{$tab['view']}' does not resolve to a registered view.");
        }
    }

    public function test_setup_guide_action_builds_module_guide_action_with_no_submit(): void
    {
        $action = PostResource::setupGuideAction();

        $this->assertInstanceOf(Action::class, $action);
        $this->assertSame('moduleGuide', $action->getName());
        $this->assertNull($action->getModalSubmitAction());
    }

    public function test_setup_guide_action_modal_content_renders_admin_guide_view_with_tabs(): void
    {
        $action = PostResource::setupGuideAction();
        $closure = $this->modalContentClosure($action);

        $this->assertInstanceOf(\Closure::class, $closure);

        $html = (string) $closure();

        foreach (PostResource::guideTabs() as $tab) {
            $this->assertStringContainsString($tab['label'], $html, "Rendered guide modal missing tab label '{$tab['label']}'.");
        }
        $this->assertStringContainsString('material-symbols-rounded', $html);
        $this->assertStringContainsString('dir="rtl"', $html);
    }

    public function test_guide_empty_state_actions_returns_only_the_module_guide_action(): void
    {
        $actions = PostResource::guideEmptyStateActions();

        $this->assertCount(1, $actions);
        $this->assertInstanceOf(Action::class, $actions[0]);
        $this->assertSame('moduleGuide', $actions[0]->getName());
    }

    private function modalContentClosure(Action $action): \Closure
    {
        $r = new ReflectionObject($action);
        while ($r && !$r->hasProperty('modalContent')) {
            $r = $r->getParentClass();
        }
        $this->assertNotNull($r, 'Filament Action does not expose a modalContent property.');

        $prop = $r->getProperty('modalContent');
        $prop->setAccessible(true);

        return $prop->getValue($action);
    }
}