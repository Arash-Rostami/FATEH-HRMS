<?php

namespace App\Filament\Resources\ContactResource\Exports;

use App\Models\Message;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class ContactExporter extends Exporter
{
    protected static ?string $model = Message::class;

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['sender', 'recipient']);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('sender.name')->label(__('resources/contact/strings.fields.sender')),
            ExportColumn::make('recipient.name')->label(__('resources/contact/strings.fields.recipient')),
            ExportColumn::make('body')->label(__('resources/contact/strings.fields.body')),
            ExportColumn::make('attachments_count')
                ->label(__('resources/contact/strings.export.attachments_count'))
                ->state(fn(Message $record) => count($record->attachments ?? [])),
            ExportColumn::make('attachment_names')
                ->label(__('resources/contact/strings.export.attachment_names'))
                ->state(fn(Message $record) => collect($record->attachments ?? [])
                    ->pluck('name')
                    ->filter()
                    ->implode(' | ')
                ),
            ExportColumn::make('is_edited')
                ->label(__('resources/contact/strings.fields.is_edited'))
                ->formatStateUsing(fn($state) => $state ? 'بله' : 'خیر'),
            ExportColumn::make('read_at')->label(__('resources/contact/strings.fields.read_at'))
                ->formatStateUsing(fn($state) => $state ? toJalaliSmart($state) : '—'),
            ExportColumn::make('created_at')->label(__('resources/contact/strings.fields.created_at'))
                ->formatStateUsing(fn($state) => $state ? toJalaliSmart($state) : '—'),
            ExportColumn::make('deleted_at')->label(__('resources/contact/strings.fields.deleted_at'))
                ->formatStateUsing(fn($state) => $state ? toJalaliSmart($state) : '—'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);

        return __('resources/contact/strings.export.completed', ['count' => $count]);
    }
}
