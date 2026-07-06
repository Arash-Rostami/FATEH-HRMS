<?php

namespace App\Filament\Resources\ChannelResource\Exports;

use App\Enums\ChannelType;
use App\Models\Channel;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class ChannelExporter extends Exporter
{
    protected static ?string $model = Channel::class;

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['owner'])->withCount(['members', 'messages']);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('name')->label(__('resources/channel/strings.fields.name')),
            ExportColumn::make('slug')->label(__('resources/channel/strings.fields.slug')),
            ExportColumn::make('description')->label(__('resources/channel/strings.fields.description')),
            ExportColumn::make('type')
                ->label(__('resources/channel/strings.fields.type'))
                ->formatStateUsing(fn($state) => $state instanceof ChannelType ? $state->getLabel() : (ChannelType::tryFrom((string) $state)?->getLabel() ?? '—')),
            ExportColumn::make('owner.name')->label(__('resources/channel/strings.fields.owner')),
            ExportColumn::make('members_count')->label(__('resources/channel/strings.fields.members_count')),
            ExportColumn::make('messages_count')->label(__('resources/channel/strings.fields.messages_count')),
            ExportColumn::make('created_at')->label(__('resources/channel/strings.fields.created_at')),
            ExportColumn::make('updated_at')->label(__('resources/channel/strings.fields.updated_at')),
            ExportColumn::make('deleted_at')->label(__('resources/channel/strings.fields.deleted_at')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);

        return __('resources/channel/strings.export.completed', ['count' => $count]);
    }
}