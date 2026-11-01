<?php

namespace Tests\Feature\Traits;

use App\Filament\Resources\PostResource;
use Filament\Actions\BulkAction;
use Filament\Actions\Exports\ExportColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Tests\TestCase;

class FilamentVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->setLocale('fa');
    }

    public function test_active_column_entry_and_export_column_target_the_active_attribute(): void
    {
        $this->assertInstanceOf(ToggleColumn::class, PostResource::activeColumn());
        $this->assertSame('active', PostResource::activeColumn()->getName());
        $this->assertInstanceOf(IconEntry::class, PostResource::activeEntry());
        $this->assertSame('active', PostResource::activeEntry()->getName());
        $this->assertInstanceOf(ExportColumn::class, PostResource::activeExportColumn());
        $this->assertSame('active', PostResource::activeExportColumn()->getName());
    }

    public function test_audience_column_entry_and_export_column_use_the_audience_state(): void
    {
        $this->assertInstanceOf(TextColumn::class, PostResource::audienceColumn());
        $this->assertSame('audience', PostResource::audienceColumn()->getName());
        $this->assertInstanceOf(TextEntry::class, PostResource::audienceEntry());
        $this->assertSame('audience', PostResource::audienceEntry()->getName());
        $this->assertInstanceOf(ExportColumn::class, PostResource::audienceExportColumn());
        $this->assertSame('audience', PostResource::audienceExportColumn()->getName());
    }

    public function test_filters_and_group_are_named_for_the_resource_tests_that_drive_them(): void
    {
        $this->assertInstanceOf(TernaryFilter::class, PostResource::activeFilter());
        $this->assertSame('active', PostResource::activeFilter()->getName());
        $this->assertInstanceOf(SelectFilter::class, PostResource::audienceFilter());
        $this->assertSame('audience', PostResource::audienceFilter()->getName());
        $this->assertInstanceOf(TernaryFilter::class, PostResource::visibilityFilter());
        $this->assertSame('visibility', PostResource::visibilityFilter()->getName());
        $this->assertInstanceOf(Group::class, PostResource::activeGroup());
        $this->assertSame('active', PostResource::activeGroup()->getId());
    }

    public function test_form_fields_active_toggle_is_on_by_default_and_departments_select_is_multiple(): void
    {
        $toggle = PostResource::activeToggle();
        $select = PostResource::departmentsSelect();

        $this->assertInstanceOf(Toggle::class, $toggle);
        $this->assertSame('active', $toggle->getName());
        $this->assertTrue($toggle->getDefaultState());
        $this->assertInstanceOf(Select::class, $select);
        $this->assertSame('departments', $select->getName());
        $this->assertTrue($select->isMultiple());
    }

    public function test_bulk_visibility_actions_are_named_activate_then_deactivate(): void
    {
        $actions = PostResource::bulkVisibilityActions();

        $this->assertCount(2, $actions);
        $this->assertContainsOnlyInstancesOf(BulkAction::class, $actions);
        $this->assertSame(['bulk_activate', 'bulk_deactivate'], array_map(fn(BulkAction $action) => $action->getName(), $actions));
        $this->assertTrue($actions[0]->isConfirmationRequired());
    }

    public function test_active_tabs_return_active_and_inactive_tabs_keyed_for_list_pages(): void
    {
        $tabs = PostResource::activeTabs(fn() => (object) ['active_count' => 0, 'inactive_count' => 0]);

        $this->assertSame(['active', 'inactive'], array_keys($tabs));
        $this->assertContainsOnlyInstancesOf(Tab::class, $tabs);
    }
}
