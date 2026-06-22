<?php

namespace App\Filament\Resources\OnboardingResource\Schemas;

use App\Filament\Resources\OnboardingResource\Schemas\Helper\ExtrasTemplate;
use App\Traits\FilamentFormDivider;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\TextColor;
use Filament\Forms\Components\RichEditor\ToolbarButtonGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class OnboardingFormPresenter
{
    use FilamentFormDivider;

    public static function extras(): array
    {
        return [
            Select::make('_extras_template')
                ->label('✨ افزودن سریع از الگوها')
                ->live()
                ->multiple()
                ->native(false)
                ->dehydrated(false)
                ->options(fn() => ExtrasTemplate::options())
                ->helperText(fn(Get $get): string => ExtrasTemplate::hint($get))
                ->afterStateUpdated(fn($state, Set $set, Get $get) => ExtrasTemplate::add($state, $set, $get))
                ->afterStateHydrated(fn($state, Get $get, Set $set) => ExtrasTemplate::sync($state, $get, $set))
                ->placeholder('مثلاً: پارکینگ، آموزش، بیمه...')
                ->prefixIcon('heroicon-m-sparkles')
                ->columnSpanFull(),

            Repeater::make('extras')
                ->label(__('resources/onboarding/strings.fields.extras'))
                ->schema([
                    TextInput::make('key')
                        ->label(__('resources/onboarding/strings.repeater.extra_key'))
                        ->maxLength(100)
                        ->helperText(__('resources/onboarding/strings.repeater.extra_key_hint'))
                        ->placeholder('e.g. parking, dress_code'),

                    TextInput::make('display_title')
                        ->label(__('resources/onboarding/strings.repeater.extra_display_title'))
                        ->maxLength(100)
                        ->nullable(),

                    self::makeExtraValueEditor(),
                ])
                ->columns(2)
                ->collapsed()
                ->addActionLabel(__('resources/onboarding/strings.actions.add_extra'))
                ->columnSpanFull()
                ->reorderable(),
        ]
            ->validationMessages([
                'max' => __('resources/onboarding/strings.validation._extras_template.max'),
                'in' => __('resources/onboarding/strings.validation._extras_template.in')
            ]);
    }

    public static function guides(): Repeater
    {
        return Repeater::make('guides')
            ->label(__('resources/onboarding/strings.fields.guides'))
            ->schema([
                TextInput::make('title')
                    ->label(__('resources/onboarding/strings.repeater.guide_title'))
                    ->maxLength(255)
                    ->columnSpanFull(),

                FileUpload::make('url')
                    ->label(__('resources/onboarding/strings.repeater.guide_file'))
                    ->disk('public')
                    ->directory('onboarding/guides')
                    ->downloadable()
                    ->openable()
                    ->live()
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->columnSpanFull()
                    ->helperText(__('resources/onboarding/strings.hints.guides_file'))
                    ->maxSize(50480)
                    ->getUploadedFileNameForStorageUsing(
                        fn(TemporaryUploadedFile $file): string => Str::random(12) . '-' . time() . '.' . $file->getClientOriginalExtension()
                    ),
                TextInput::make('ext')->hidden()->dehydrated(),
                TextInput::make('size')->hidden()->dehydrated(),
            ])
            ->columns(2)
            ->collapsed()
            ->addActionLabel(__('resources/onboarding/strings.actions.add_guide'))
            ->columnSpanFull()
            ->reorderable()
            ->validationMessages([
                'max' => __('resources/onboarding/strings.validation.guides.max'),
                'mimes' => __('resources/onboarding/strings.validation.guides.mimes')
            ]);
    }

    public static function hydrateGuidesWithFileMeta(array $data): array
    {
        $data['guides'] = collect($data['guides'] ?? [])
            ->map(function ($guide) {
                if (empty($guide['url']) || !empty($guide['ext']) || !Storage::disk('public')->exists($guide['url'])) return $guide;

                $guide['ext'] = pathinfo($guide['url'], PATHINFO_EXTENSION);
                $bytes = Storage::disk('public')->size($guide['url']);
                $guide['size'] = $bytes >= 1_048_576
                    ? round($bytes / 1_048_576, 2) . ' MB'
                    : round($bytes / 1024, 2) . ' KB';

                return $guide;
            })
            ->toArray();

        return $data;
    }

    public static function isActive(): Toggle
    {
        return Toggle::make('is_active')
            ->label(__('resources/onboarding/strings.fields.is_active'))
            ->helperText(__('resources/onboarding/strings.fields.is_active_hint'))
            ->inline(false)
            ->default(false);
    }

    public static function mission(): RichEditor
    {
        return self::makeRichEditor('mission', false)
            ->helperText(__('resources/onboarding/strings.hints.mission'));
    }

    public static function schedule(): RichEditor
    {
        return self::makeRichEditor('schedule', false)
            ->helperText(__('resources/onboarding/strings.hints.schedule'));
    }

    public static function userId(): Select
    {
        return Select::make('user_id')
            ->label(__('resources/onboarding/strings.fields.user_id'))
            ->helperText(__('resources/onboarding/strings.fields.user_id_hint'))
            ->relationship('user', 'name')
            ->searchable()
            ->preload()
            ->nullable()
            ->validationMessages([
                'exists' => __('resources/onboarding/strings.validation.user_id.exists'),
                'in' => __('resources/onboarding/strings.validation.user_id.in')
            ]);
    }

    public static function videos(): Repeater
    {
        return Repeater::make('videos')
            ->label(__('resources/onboarding/strings.fields.videos'))
            ->schema([
                TextInput::make('title')
                    ->label(__('resources/onboarding/strings.repeater.video_title'))
                    ->maxLength(255)
                    ->columnSpanFull(),

                FileUpload::make('url')
                    ->label(__('resources/onboarding/strings.repeater.video_url'))
                    ->disk('public')
                    ->downloadable()
                    ->openable()
                    ->directory('onboarding/video')
                    ->acceptedFileTypes(['video/*'])
                    ->helperText(__('resources/onboarding/strings.hints.videos_file'))
                    ->maxSize(204800)
                    ->getUploadedFileNameForStorageUsing(
                        fn(TemporaryUploadedFile $file): string => Str::random(12) . '-' . time() . '.' . $file->getClientOriginalExtension()
                    ),

                FileUpload::make('thumbnail')
                    ->label(__('resources/onboarding/strings.repeater.video_thumbnail'))
                    ->disk('public')
                    ->directory('onboarding/thumbnail')
                    ->downloadable()
                    ->openable()
                    ->acceptedFileTypes(['image/*'])
                    ->maxSize(1024)
                    ->image()
                    ->imagePreviewHeight('60')
                    ->getUploadedFileNameForStorageUsing(
                        fn(TemporaryUploadedFile $file): string => Str::random(12) . '-' . time() . '.' . $file->getClientOriginalExtension()
                    )
                    ->nullable(),

                TextInput::make('duration')
                    ->label(__('resources/onboarding/strings.repeater.video_duration'))
                    ->placeholder('3:45')
                    ->maxLength(10)
                    ->nullable(),
            ])
            ->columns(2)
            ->collapsed()
            ->addActionLabel(__('resources/onboarding/strings.actions.add_video'))
            ->columnSpanFull()
            ->reorderable()
            ->validationMessages([
                'max' => __('resources/onboarding/strings.validation.videos.max'),
                'mimes' => __('resources/onboarding/strings.validation.videos.mimes')
            ]);
    }

    public static function vision(): RichEditor
    {
        return self::makeRichEditor('vision', false)
            ->helperText(__('resources/onboarding/strings.hints.vision'));
    }

    public static function welcome(): RichEditor
    {
        return self::makeRichEditor('welcome')
            ->helperText(__('resources/onboarding/strings.hints.welcome'));
    }

    private static function makeExtraValueEditor(): RichEditor
    {
        return RichEditor::make('value')
            ->label(__('resources/onboarding/strings.repeater.extra_value'))
            ->maxLength(20000)
            ->validationMessages([
                'max' => __('resources/onboarding/strings.validation.extra_value.max_length'),
                'required' => __('resources/onboarding/strings.validation.value.required')
            ])
            ->columnSpanFull()
            ->textColors([
                'primary' => TextColor::make('Primary', '#3b82f6', darkColor: '#60a5fa'),
                ...TextColor::getDefaults(),
            ])
            ->customTextColors()
            ->extraInputAttributes(['class' => 'fi-prose', 'style' => 'min-height: 160px;'])
            ->toolbarButtons([
                ['textColor', 'bold', 'italic', 'underline', 'strike', 'clearFormatting'],
                [ToolbarButtonGroup::make('Alignment', ['alignStart', 'alignCenter', 'alignEnd'])],
                ['blockquote', 'bulletList', 'orderedList'],
                ['highlight', 'link'],
                ['undo', 'redo'],
            ]);
    }

    private static function makeRichEditor(string $field, bool $required = true): RichEditor
    {
        $messages = [
            'max' => __("resources/onboarding/strings.validation.{$field}.max_length"),
        ];

        if ($required) {
            $messages['required'] = __("resources/onboarding/strings.validation.{$field}.required");
        }

        return RichEditor::make($field)
            ->label(__("resources/onboarding/strings.fields.{$field}"))
            ->required($required)
            ->maxLength(50000)
            ->validationMessages($messages)
            ->columnSpanFull()
            ->textColors([
                'primary' => TextColor::make('Primary', '#3b82f6', darkColor: '#60a5fa'),
                'success' => TextColor::make('Success', '#22c55e', darkColor: '#4ade80'),
                'warning' => TextColor::make('Warning', '#f59e0b', darkColor: '#fbbf24'),
                'danger' => TextColor::make('Danger', '#ef4444', darkColor: '#f87171'),
                ...TextColor::getDefaults(),
            ])
            ->extraInputAttributes(['class' => 'fi-prose', 'style' => 'min-height: 280px;'])
            ->customTextColors()
            ->toolbarButtons([
                ['textColor', 'bold', 'italic', 'underline', 'strike', 'code', 'clearFormatting'],
                [ToolbarButtonGroup::make('Headings', ['paragraph', 'h1', 'h2', 'h3', 'h4'])
                    ->icon('fi-o-heading')
                    ->textualButtons()],
                [ToolbarButtonGroup::make('Alignment', ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'])],
                ['blockquote', 'bulletList', 'orderedList'],
                ['highlight', 'link', 'horizontalRule'],
                ['table'],
                ['undo', 'redo'],
            ])
            ->floatingToolbars([
                'paragraph' => ['textColor', 'bold', 'italic', 'underline', 'strike', 'link', 'highlight'],
                'heading' => ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                'table' => [
                    'tableAddColumnBefore', 'tableAddColumnAfter', 'tableDeleteColumn',
                    'tableAddRowBefore', 'tableAddRowAfter', 'tableDeleteRow',
                    'tableMergeCells', 'tableSplitCell', 'tableToggleHeaderRow', 'tableDelete',
                ]]);
    }
}
