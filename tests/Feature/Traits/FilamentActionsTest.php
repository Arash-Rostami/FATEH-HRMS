<?php

namespace Tests\Feature\Traits;

use App\Filament\Resources\PostResource;
use App\Filament\Resources\PostResource\Exports\PostExporter;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Tests\TestCase;

class FilamentActionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->setLocale('fa');
    }

    public function test_delete_action(): void
    {
        $action = PostResource::deleteAction();

        $this->assertInstanceOf(DeleteAction::class, $action);
        $this->assertSame('delete', $action->getName());
        $this->assertTrue($action->isConfirmationRequired());
    }

    public function test_edit_action(): void
    {
        $action = PostResource::editAction();

        $this->assertInstanceOf(EditAction::class, $action);
        $this->assertSame('edit', $action->getName());
    }

    public function test_view_action(): void
    {
        $action = PostResource::viewAction();

        $this->assertInstanceOf(ViewAction::class, $action);
        $this->assertSame('view', $action->getName());
    }

    public function test_restore_action(): void
    {
        $action = PostResource::restoreAction();

        $this->assertInstanceOf(RestoreAction::class, $action);
        $this->assertSame('restore', $action->getName());
    }

    public function test_bulk_delete_action(): void
    {
        $action = PostResource::bulkDeleteAction();

        $this->assertInstanceOf(DeleteBulkAction::class, $action);
        $this->assertSame('delete', $action->getName());
    }

    public function test_bulk_export_action(): void
    {
        $action = PostResource::bulkExportAction(PostExporter::class);

        $this->assertInstanceOf(ExportBulkAction::class, $action);
        $this->assertSame('export', $action->getName());
        $this->assertSame(PostExporter::class, $action->getExporter());
    }

    public function test_bulk_actions_returns_two_actions(): void
    {
        $actions = PostResource::bulkActions(PostExporter::class);

        $this->assertCount(2, $actions);
        $this->assertInstanceOf(DeleteBulkAction::class, $actions[0]);
        $this->assertInstanceOf(ExportBulkAction::class, $actions[1]);
    }

    public function test_assign_action(): void
    {
        $action = PostResource::assignAction(fn ($record) => null);

        $this->assertInstanceOf(Action::class, $action);
        $this->assertSame('assign', $action->getName());
        $this->assertSame('تخصیص', $action->getLabel());
        $this->assertTrue($action->isConfirmationRequired());
    }

    public function test_unassign_action(): void
    {
        $action = PostResource::unassignAction(fn ($record) => null);

        $this->assertInstanceOf(Action::class, $action);
        $this->assertSame('unassign', $action->getName());
        $this->assertSame('حذف تخصیص', $action->getLabel());
        $this->assertTrue($action->isConfirmationRequired());
    }

    public function test_bulk_unassign_action(): void
    {
        $action = PostResource::bulkUnassignAction(fn ($record) => null);

        $this->assertInstanceOf(BulkAction::class, $action);
        $this->assertSame('bulk_unassign', $action->getName());
    }
}