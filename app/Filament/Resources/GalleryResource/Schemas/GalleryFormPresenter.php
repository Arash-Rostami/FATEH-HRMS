<?php

namespace App\Filament\Resources\GalleryResource\Schemas;


use App\Models\Department;
use App\Services\PersianDateFieldService;
use App\Traits\FilamentFormDivider;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class GalleryFormPresenter
{
    use FilamentFormDivider;

    public static function departmentId(): Select
    {
        return Select::make('department_id')
            ->label(__('resources/gallery/strings.fields.department'))
            ->helperText(__('resources/gallery/strings.fields.department_hint'))
            ->options(fn() => Department::getCachedOptions()->toArray())
            ->searchable()
            ->preload()
            ->live()
            ->disabled(fn (Get $get) => filled($get('departments')))
            ->dehydrated(true)
            ->afterStateUpdated(fn (Set $set) => $set('departments', null))
            ->nullable();
    }


    public static function departments(): Select
    {
        return Select::make('departments')
            ->label(__('resources/gallery/strings.fields.departments_multi'))
            ->helperText(__('resources/gallery/strings.fields.departments_multi_hint'))
            ->options(fn() => Department::getCachedOptions()->toArray())
            ->multiple()
            ->searchable()
            ->preload()
            ->live()
            ->afterStateUpdated(fn (Set $set) => $set('department_id', null))
            ->nullable();
    }
    public static function description(): Textarea
    {
        return Textarea::make('description')
            ->label(__('resources/gallery/strings.fields.description'))
            ->rows(2)
            ->maxLength(2000)
            ->columnSpan(2)
            ->helperText(__('resources/gallery/strings.hints.description'));
    }

    public static function eventDate(): FusedGroup
    {
        return PersianDateFieldService::make(
            prefix: 'event_date',
            label: __('resources/gallery/strings.fields.event_date'),
            required: false,
            yearFrom: 1380,
            fullWidth: false,
        )->columnSpan(2);
    }

    public static function path(): FileUpload
    {
        return FileUpload::make('path')
            ->label(__('resources/gallery/strings.fields.photos'))
            ->disk('public')
            ->directory('gallery')
            ->multiple()
            ->maxFiles(50)
            ->maxSize(51200)
            ->acceptedFileTypes([
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'video/mp4',
                'video/webm',
                'video/quicktime',
            ])
            ->getUploadedFileNameForStorageUsing(
                fn(TemporaryUploadedFile $file): string => Str::random(12) . '-' . time() . '.' . $file->getClientOriginalExtension()
            )
            ->imagePreviewHeight('100')
            ->panelLayout('grid')
            ->reorderable()
            ->previewable()
            ->downloadable()
            ->openable()
            ->required()
            ->columnSpan(3)
            ->helperText(__('resources/gallery/strings.hints.path'));
    }

    public static function title(): TextInput
    {
        return TextInput::make('title')
            ->label(__('resources/gallery/strings.fields.title'))
            ->required()
            ->columnSpan(2)
            ->maxLength(255)
            ->helperText(__('resources/gallery/strings.hints.title'));
    }
}
