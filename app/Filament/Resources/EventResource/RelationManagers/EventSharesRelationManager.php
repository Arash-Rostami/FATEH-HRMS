<?php

namespace App\Filament\Resources\EventResource\RelationManagers;

use App\Filament\Resources\EventResource;
use Filament\Actions\Action;
use App\Filament\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EventSharesRelationManager extends RelationManager
{
    protected static string $relationship = 'shares';

    public static function getModelLabel(): string
    {
        return 'اشتراک';
    }

    public static function getPluralModelLabel(): string
    {
        return 'اشتراک‌گذاری‌های رویداد';
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'اشتراک‌گذاری‌های این رویداد';
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return parent::canViewForRecord($ownerRecord, $pageClass) && $ownerRecord->shares()->exists();
    }

    public static function deniedByPermission(Model $ownerRecord): ?string
    {
        return ($module = parent::deniedByPermission($ownerRecord)) && $ownerRecord->shares()->exists() ? $module : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['recipient', 'sharer']))
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('recipient.name')
                    ->label('گیرنده')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('sharer.name')
                    ->label('اشتراک‌گذار')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('تاریخ اشتراک‌گذاری')
                    ->formatStateUsing(fn($state) => $state ? toJalali($state, 'Y/m/d H:i') : '—')
                    ->extraAttributes(['dir' => 'ltr', 'style' => 'unicode-bidi: isolate;'])
                    ->alignCenter()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label('لغو اشتراک‌گذاری')
                    ->icon('heroicon-o-x-mark')
                    ->iconButton()
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('لغو اشتراک‌گذاری؟')
                    ->modalDescription('این رویداد دیگر برای کاربر گیرنده نمایش داده نمی‌شود.')
                    ->visible(fn() => EventResource::canDeleteAny())
                    ->action(fn(Model $record) => $record->delete()),
            ], RecordActionsPosition::AfterCells)
            ->emptyStateIcon('heroicon-o-share')
            ->defaultSort('created_at', 'desc');
    }
}