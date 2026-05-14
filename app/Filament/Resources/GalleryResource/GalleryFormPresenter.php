<?php

namespace App\Filament\Resources\GalleryResource\Schemas;

use App\Models\Department;
use App\Services\PersianDateFieldService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\FusedGroup;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class GalleryFormPresenter
{
    public static function departmentId(): Select
    {
        return Select::make('department_id')
            ->label(__('resources/gallery/strings.fields.department'))
            ->helperText(__('resources/gallery/strings.fields.department_hint'))
            ->options(fn() => Department::getCachedOptions()->toArray())
            ->searchable()
            ->preload()
            ->nullable();
    }

    public static function description(): Textarea
    {
        return Textarea::make('description')
            ->label(__('resources/gallery/strings.fields.description'))
            ->rows(2)
            ->maxLength(2000)
            ->columnSpanFull();
    }

    public static function eventDate(): FusedGroup
    {
        return PersianDateFieldService::make(
            prefix: 'event_date',
            label: __('resources/gallery/strings.fields.event_date'),
            required: false,
            yearFrom: 1380,
            fullWidth: false,
        );
    }

    public static function path(): FileUpload
    {
        return FileUpload::make('path')
            ->label(__('resources/gallery/strings.fields.photos'))
            ->disk('public')
            ->directory('gallery')
            ->multiple()
            ->maxFiles(50)
            ->maxSize(5120)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
            ->getUploadedFileNameForStorageUsing(
                fn(TemporaryUploadedFile $file): string => Str::random(12) . '-' . time() . '.' . $file->getClientOriginalExtension()
            )
            ->image()
            ->imagePreviewHeight('100')
            ->panelLayout('grid')
            ->reorderable()
            ->required()
            ->columnSpanFull();
    }

    public static function title(): TextInput
    {
        return TextInput::make('title')
            ->label(__('resources/gallery/strings.fields.title'))
            ->required()
            ->maxLength(255)
            ->validationMessages(['required' => __('resources/gallery/strings.validation.title_required')]);
    }
}
