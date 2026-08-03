<?php

namespace App\Filament\Resources\SkillRequestResource\Schemas\Helper;

use App\Enums\SkillRequestStatus;
use App\Models\SkillUser;
use App\Services\SkillService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

trait RequestActions
{

    public static function approveAction(): Action
    {
        return Action::make('approve')
            ->label(__('resources/skill_request/strings.actions.approve'))
            ->icon('heroicon-m-check')
            ->color('success')
            ->button()
            ->iconButton()
            ->visible(fn($record) => $record->status === SkillRequestStatus::Pending && self::canEdit($record))
            ->action(function (SkillUser $record): void {
                abort_unless(self::canEdit($record), 403);
                if ($record->status !== SkillRequestStatus::Pending) {
                    return;
                }
                app(SkillService::class)->approve($record);
                Notification::make()->title(__('resources/skill_request/strings.notifications.approved', ['skill' => $record->skill->name]))->success()->send();
            });
    }

    public static function bulkApproveAction(): BulkAction
    {
        return BulkAction::make('bulk_approve')
            ->label(__('resources/skill_request/strings.actions.approve_selected'))
            ->icon('heroicon-m-check')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn() => self::canEdit(new SkillUser()))
            ->action(function (Collection $records): void {
                abort_unless(self::canEdit(new SkillUser()), 403);
                $service = app(SkillService::class);
                $pending = $records->filter(fn($record) => $record->status === SkillRequestStatus::Pending);
                foreach ($pending as $record) {
                    $service->approve($record);
                }
                self::notifyCount($pending->count(), 'approve_done');
            });
    }

    public static function bulkRejectAction(): BulkAction
    {
        return BulkAction::make('bulk_reject')
            ->label(__('resources/skill_request/strings.actions.reject_selected'))
            ->icon('heroicon-m-x-mark')
            ->color('danger')
            ->visible(fn() => self::canEdit(new SkillUser()))
            ->schema([
                Textarea::make('rejected_reason')
                    ->label(__('resources/skill_request/strings.actions.reason'))
                    ->maxLength(500),
            ])
            ->action(function (Collection $records, array $data): void {
                abort_unless(self::canEdit(new SkillUser()), 403);
                $service = app(SkillService::class);
                $reason = $data['rejected_reason'] ?? null;
                $pending = $records->filter(fn($record) => $record->status === SkillRequestStatus::Pending);
                foreach ($pending as $record) {
                    $service->reject($record, $reason);
                }
                self::notifyCount($pending->count(), 'reject_done');
            });
    }

    public static function rejectAction(): Action
    {
        return Action::make('reject')
            ->label(__('resources/skill_request/strings.actions.reject'))
            ->icon('heroicon-m-x-mark')
            ->color('danger')
            ->button()
            ->iconButton()
            ->visible(fn($record) => $record->status === SkillRequestStatus::Pending && self::canEdit($record))
            ->modalHeading(__('resources/skill_request/strings.actions.reject_heading'))
            ->modalDescription(__('resources/skill_request/strings.actions.reject_description'))
            ->schema([
                Textarea::make('rejected_reason')
                    ->label(__('resources/skill_request/strings.actions.reason'))
                    ->maxLength(500),
            ])
            ->action(function (SkillUser $record, array $data): void {
                abort_unless(self::canEdit($record), 403);
                if ($record->status !== SkillRequestStatus::Pending) {
                    return;
                }
                app(SkillService::class)->reject($record, $data['rejected_reason'] ?? null);
                Notification::make()->title(__('resources/skill_request/strings.notifications.rejected', ['skill' => $record->skill->name]))->success()->send();
            });
    }
}
