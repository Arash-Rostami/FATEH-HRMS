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
            ->rules([
                fn (\Filament\Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                    $version = $get('version');
                    $query = \App\Models\DMS::where('code', $value)
                        ->where('version', $version)
                        ->where('status', 'live');

                    if ($record) {
                        $query->where('id', '!=', $record->id);
                    }

                    if ($query->exists()) {
                        $fail('یک سند فعال با همین کد و نسخه از قبل در سیستم وجود دارد.');
                    }
                },
            ]);
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
            ->helperText(__('resources/dms/strings.hints.owners'));
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
            ->helperText(__('resources/dms/strings.hints.status'));
    }

    public static function tags(): KeyValue
    {
        return KeyValue::make('tags')
            ->label(__('resources/dms/strings.fields.tags'))
            ->columnSpanFull()
            ->helperText(__('resources/dms/strings.fields.tags_hint'));
    }

    public static function title(): TextInput
    {
        return TextInput::make('title')
            ->label(__('resources/dms/strings.fields.title'))
            ->required()
            ->maxLength(255)
            ->helperText(__('resources/dms/strings.hints.title'));
    }

    public static function type(): Toggle
    {
        return Toggle::make('type')
            ->label(__('resources/dms/strings.fields.type_label'))
            ->default(true)
            ->columnSpanFull()
            ->live()
            ->helperText(fn($state) => $state
                ? __('resources/dms/strings.hints.type.systematic')
                : __('resources/dms/strings.hints.type.non_systematic')
            );
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
            ->nullable();
    }

    public static function version(): TextInput
    {
        return TextInput::make('version')
            ->label(__('resources/dms/strings.fields.version'))
            ->required()
            ->maxLength(50)
            ->helperText(__('resources/dms/strings.hints.version'))
            ->rules([
                fn (\Filament\Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                    $code = $get('code');
                    $query = \App\Models\DMS::where('code', $code)
                        ->where('version', $value)
                        ->where('status', 'live');

                    if ($record) {
                        $query->where('id', '!=', $record->id);
                    }

                    if ($query->exists()) {
                        $fail('یک سند فعال با همین کد و نسخه از قبل در سیستم وجود دارد.');
                    }
                },
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
