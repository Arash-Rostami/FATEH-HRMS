<?php

namespace Tests\Feature\Traits\Filament;

use App\Models\User;
use App\Traits\Filament\ExplainsHiddenRelationTabs;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use RuntimeException;
use Tests\TestCase;

class HiddenTabsParentStub
{
    public array $managers = [];

    public function content(Schema $schema): Schema
    {
        return $schema;
    }

    public function hasRecord(): bool
    {
        return true;
    }

    public function getRecord(): Model
    {
        return new User;
    }

    public function getAllRelationManagers(): array
    {
        return $this->managers;
    }
}

class DeniedTasksTabStub
{
    public static function deniedByPermission(Model $record): ?string
    {
        return 'task';
    }

    public static function getTitle(Model $record, string $pageClass): string
    {
        return 'Tasks';
    }
}

class DeniedSkillsTabStub
{
    public static function deniedByPermission(Model $record): ?string
    {
        return 'skill';
    }

    public static function getTitle(Model $record, string $pageClass): string
    {
        return 'Skills';
    }
}

class OpenTabStub
{
    public static function deniedByPermission(Model $record): ?string
    {
        return null;
    }

    public static function getTitle(Model $record, string $pageClass): string
    {
        return 'Open';
    }
}

class ThrowingTabStub
{
    public static function deniedByPermission(Model $record): ?string
    {
        throw new RuntimeException('probe failed');
    }

    public static function getTitle(Model $record, string $pageClass): string
    {
        return 'Broken';
    }
}

class ExplainsHiddenRelationTabsTest extends TestCase
{
    private function host(array $managers): object
    {
        $host = new class extends HiddenTabsParentStub {
            use ExplainsHiddenRelationTabs {
                hiddenRelationTabsNotice as public;
            }
        };
        $host->managers = $managers;

        return $host;
    }

    private function tab(string $title, string $module): string
    {
        return __('resources/general/strings.hidden_tabs.tab', ['title' => $title, 'module' => $module]);
    }

    public function test_callout_names_a_single_hidden_tab_with_its_module(): void
    {
        $this->actingAs(new User);

        $callout = $this->host([DeniedTasksTabStub::class, OpenTabStub::class])->hiddenRelationTabsNotice();

        $this->assertInstanceOf(Callout::class, $callout);
        $this->assertSame(__('resources/general/strings.hidden_tabs.one', ['tabs' => $this->tab('Tasks', 'task')]), $callout->getHeading());
        $this->assertSame(__('resources/general/strings.hidden_tabs.pointer'), $callout->getDescription());
    }

    public function test_callout_lists_every_hidden_tab_including_those_inside_relation_groups(): void
    {
        $this->actingAs(new User);

        $callout = $this->host([DeniedTasksTabStub::class, RelationGroup::make('group', [DeniedSkillsTabStub::class, OpenTabStub::class])])->hiddenRelationTabsNotice();

        $tabs = Arr::join([$this->tab('Tasks', 'task'), $this->tab('Skills', 'skill')], '، ', ' و ');
        $this->assertSame(__('resources/general/strings.hidden_tabs.many', ['tabs' => $tabs]), $callout->getHeading());
    }

    public function test_nothing_renders_when_no_tab_is_hidden(): void
    {
        $this->actingAs(new User);

        $this->assertNull($this->host([OpenTabStub::class])->hiddenRelationTabsNotice());
        $this->assertNull($this->host([])->hiddenRelationTabsNotice());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->setLocale('fa');
    }

    public function test_nothing_renders_for_guests(): void
    {
        $this->assertNull($this->host([DeniedTasksTabStub::class])->hiddenRelationTabsNotice());
    }

    public function test_a_failing_tab_probe_is_skipped_and_does_not_break_the_page(): void
    {
        $this->actingAs(new User);

        $callout = $this->host([ThrowingTabStub::class, DeniedTasksTabStub::class])->hiddenRelationTabsNotice();

        $this->assertStringContainsString($this->tab('Tasks', 'task'), $callout->getHeading());
        $this->assertStringNotContainsString('Broken', $callout->getHeading());
    }

    public function test_content_prepends_the_callout_to_the_page_schema(): void
    {
        $this->actingAs(new User);

        $schema = $this->host([DeniedTasksTabStub::class])->content(Schema::make());

        $this->assertInstanceOf(Callout::class, $schema->getComponents(withHidden: true)[0]);
    }

    public function test_content_is_returned_unchanged_when_no_tab_is_hidden(): void
    {
        $this->actingAs(new User);
        $schema = Schema::make();

        $this->assertSame($schema, $this->host([OpenTabStub::class])->content($schema));
    }
}
