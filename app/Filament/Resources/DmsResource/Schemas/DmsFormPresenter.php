<?php

namespace App\Filament\Resources\DmsResource\Schemas;

use App\Filament\Resources\DmsResource\Enums\DocumentStatus;
use App\Filament\Resources\DmsResource\Schemas\Action\GenerateOwnerPreview;
use App\Models\Department;
use App\Models\User;
use App\Traits\FilamentFormDivider;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class DmsFormPresenter
{
    use FilamentFormDivider;

    public static function code(): TextInput
    {
        return TextInput::make('code')
            ->label(__('resources/dms/strings.fields.code'))
            ->required()
            ->maxLength(100)
            ->helperText(__('resources/dms/strings.hints.code'))
            ->validationMessages([
                'required' => __('resources/dms/strings.validation.code.required'),
                'max' => __('resources/dms/strings.validation.code.max_length'),
            ]);
    }

    public static function type(): Toggle
    {
        return Toggle::make('type')
            ->label(__('resources/dms/strings.fields.type_label'))
            ->default(true)
            ->columnSpanFull()
            ->live()
            ->helperText(fn ($state) => $state
                ? __('resources/dms/strings.hints.type.systematic')
                : __('resources/dms/strings.hints.type.non_systematic')
            );
    }

    public static function tags(): KeyValue
    {
        return KeyValue::make('tags')
            ->label(__('resources/dms/strings.fields.tags'))
            ->columnSpanFull()
            ->helperText(__('resources/dms/strings.fields.tags_hint'));
    }

    public static function extra(): KeyValue
    {
        return KeyValue::make('extra')
            ->label(__('resources/dms/strings.fields.extra'))
            ->keyLabel(__('resources/dms/strings.fields.extra_key'))
            ->valueLabel(__('resources/dms/strings.fields.extra_value'))
            ->helperText(__('resources/dms/strings.fields.extra_hint'))
            ->columnSpanFull();
    }

    public static function file(): FileUpload
    {
        return FileUpload::make('file')
            ->label(__('resources/dms/strings.fields.file'))
            ->disk('public')
            ->directory('dms')
            ->openable()
            ->downloadable()
            ->maxSize(4096)
            ->getUploadedFileNameForStorageUsing(
                fn(TemporaryUploadedFile $file): string => self::forgeFileName($file)
            )
            ->required()
            ->validationMessages([
                'required' => __('resources/dms/strings.validation.file.required'),
                'max' => __('resources/dms/strings.validation.file.max_size'),
            ])
            ->columnSpanFull()
            ->helperText(__('resources/dms/strings.hints.file'));
    }

    public static function owners(): Select
    {
        return Select::make('owners')
            ->label(__('resources/dms/strings.fields.owners'))
            ->options(fn() => array_merge(
                ['ALL' => __('resources/dms/strings.fields.all_departments')],
                Department::getCachedOptions()->toArray()
            ))
            ->multiple()
            ->searchable()
            ->live()
            ->afterStateUpdated(function (Set $set, ?array $state) {
                $preview = app(GenerateOwnerPreview::class)->handle($state ?? []);
                $set('owners_preview_display', $preview);
            })
            ->required()
            ->helperText(__('resources/dms/strings.hints.owners'))
            ->validationMessages(['required' => __('resources/dms/strings.validation.owners.required'),
                'in' => __('resources/dms/strings.validation.owners.in')
            ]);
    }

    public static function ownersPreview(): Textarea
    {
        return Textarea::make('owners_preview_display')
            ->label(__('resources/dms/strings.fields.owners_preview'))
            ->disabled()
            ->dehydrated(false)
            ->afterStateHydrated(fn($component, $record) => $component->state($record
                ? app(GenerateOwnerPreview::class)->handle($record->owners ?? [])
                : null)
            )
            ->rows(5)
            ->placeholder(__('resources/dms/strings.fields.owners_preview_placeholder'))
            ->columnSpanFull()
            ->helperText(__('resources/dms/strings.hints.owners_preview'));
    }

    public static function revision(): Textarea
    {
        return Textarea::make('revision')
            ->label(__('resources/dms/strings.fields.revision'))
            ->rows(6)
            ->maxLength(3000)
            ->nullable()
            ->placeholder(__('resources/dms/strings.fields.revision_placeholder'))
            ->validationMessages([
                'max' => __('resources/dms/strings.validation.revision.max_length'),
            ])
            ->columnSpanFull()
            ->helperText(__('resources/dms/strings.hints.revision'));
    }

    public static function status(): Select
    {
        return Select::make('status')
            ->label(__('resources/dms/strings.fields.status'))
            ->options(DocumentStatus::class)
            ->required()
            ->default(DocumentStatus::Live->value)
            ->helperText(__('resources/dms/strings.hints.status'))
            ->validationMessages([
                'required' => __('resources/dms/strings.validation.status.required'),
                'in' => __('resources/dms/strings.validation.status.in')
            ]);
    }

    public static function title(): TextInput
    {
        return TextInput::make('title')
            ->label(__('resources/dms/strings.fields.title'))
            ->required()
            ->maxLength(255)
            ->helperText(__('resources/dms/strings.hints.title'))
            ->validationMessages([
                'required' => __('resources/dms/strings.validation.title.required'),
                'max' => __('resources/dms/strings.validation.title.max_length'),
            ]);
    }

    public static function users(): Select
    {
        return Select::make('users')
            ->label(__('resources/dms/strings.fields.users'))
            ->helperText(__('resources/dms/strings.fields.users_hint'))
            ->options(fn() => User::getCachedActiveOptions()->toArray())
            ->multiple()
            ->searchable()
            ->preload()
            ->nullable()
            ->validationMessages([
                'in' => __('resources/dms/strings.validation.users.in')
            ]);
    }

    public static function version(): TextInput
    {
        return TextInput::make('version')
            ->label(__('resources/dms/strings.fields.version'))
            ->required()
            ->maxLength(50)
            ->helperText(__('resources/dms/strings.hints.version'))
            ->validationMessages([
                'required' => __('resources/dms/strings.validation.version.required'),
                'max' => __('resources/dms/strings.validation.version.max_length'),
            ]);
    }

    private static function forgeFileName(TemporaryUploadedFile $file): string
    {
        $prefix = 'FATEH-DMS';
        $date = now()->format('Ymd');
        $unique = Str::random(12) . '-' . time();
        $ext = $file->getClientOriginalExtension();

        $name = Str::of(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9]/', '')
            ->replace(' ', '')
            ->kebab()
            ->limit(30);

        return "{$prefix}-{$date}-{$unique}-{$name}.{$ext}";
    }
}
