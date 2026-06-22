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
            ->nullable()
            ->validationMessages([
                'in' => __('resources/gallery/strings.validation.department_id.in')
            ]);
    }

    public static function description(): Textarea
    {
        return Textarea::make('description')
            ->label(__('resources/gallery/strings.fields.description'))
            ->rows(2)
            ->maxLength(2000)
            ->columnSpan(2)
            ->helperText(__('resources/gallery/strings.hints.description'))
            ->validationMessages([
                'max' => __('resources/gallery/strings.validation.description_max_length', ['length' => 2000]),
            ]);
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
            ->maxSize(5120)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
            ->getUploadedFileNameForStorageUsing(
                fn(TemporaryUploadedFile $file): string => Str::random(12) . '-' . time() . '.' . $file->getClientOriginalExtension()
            )
            ->image()
            ->imagePreviewHeight('100')
            ->panelLayout('grid')
            ->reorderable()
            ->previewable()
            ->downloadable()
            ->openable()
            ->required()
            ->columnSpan(3)
            ->helperText(__('resources/gallery/strings.hints.path'))
            ->validationMessages([
                'required' => __('resources/gallery/strings.validation.photos_required'),
                'max' => __('resources/gallery/strings.validation.photos_max_files', ['max' => 50,
                'mimes' => __('resources/gallery/strings.validation.path.mimes')
            ]),
                'max_size' => __('resources/gallery/strings.validation.photos_max_size'),
                'mimetypes' => __('resources/gallery/strings.validation.photos_mimetypes'),
            ]);
    }

    public static function title(): TextInput
    {
        return TextInput::make('title')
            ->label(__('resources/gallery/strings.fields.title'))
            ->required()
            ->columnSpan(2)
            ->maxLength(255)
            ->helperText(__('resources/gallery/strings.hints.title'))
            ->validationMessages([
                'required' => __('resources/gallery/strings.validation.title_required'),
                'max' => __('resources/gallery/strings.validation.title_max_length', ['length' => 255]),
            ]);
    }
}
