<?php

namespace App\Filament\Resources\FeedResource\Schemas;

use App\Filament\Resources\FeedResource\Enums\FeedCategory;
use App\Models\Feed;
use App\Traits\FilamentFormDivider;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\TextColor;
use Filament\Forms\Components\RichEditor\ToolbarButtonGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class FeedFormPresenter
{
    use FilamentFormDivider;

    public static function category(): Select
    {
        return Select::make('category')
            ->label(__('resources/feed/strings.fields.category'))
            ->options(FeedCategory::class)
            ->required()
            ->live()
            ->helperText(__('resources/feed/strings.hints.category'))
            ;
    }

    public static function content(): RichEditor
    {
        return RichEditor::make('content')
            ->label(__('resources/feed/strings.fields.content'))
            ->required()
            ->maxLength(10000)
            ->columnSpanFull()
            ->placeholder(__('resources/feed/strings.placeholders.content'))
            ->helperText(__('resources/feed/strings.helper_text.content'))
            ->textColors([
                'primary' => TextColor::make('Primary', '#3b82f6', darkColor: '#60a5fa'),
                'success' => TextColor::make('Success', '#22c55e', darkColor: '#4ade80'),
                'warning' => TextColor::make('Warning', '#f59e0b', darkColor: '#fbbf24'),
                'danger' => TextColor::make('Danger', '#ef4444', darkColor: '#f87171'),
                ...TextColor::getDefaults(),
            ])
            ->extraInputAttributes(['class' => 'fi-prose', 'style' => 'min-height: 240px;'])
            ->customTextColors()
            ->toolbarButtons([
                ['textColor', 'bold', 'italic', 'underline', 'strike', 'code', 'clearFormatting'],
                [ToolbarButtonGroup::make('Headings', ['paragraph', 'h2', 'h3', 'h4'])
                    ->icon('fi-o-heading')
                    ->textualButtons()],
                [ToolbarButtonGroup::make('Alignment', ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'])],
                ['blockquote', 'bulletList', 'orderedList'],
                ['highlight', 'link'],
                ['undo', 'redo'],
            ])
            ->floatingToolbars([
                'paragraph' => ['textColor', 'bold', 'italic', 'underline', 'strike', 'link', 'highlight'],
                'heading' => ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
            ])
            ;
    }

    public static function mediaImages(): FileUpload
    {
        return FileUpload::make('media_images')
            ->label(__('resources/feed/strings.fields.media_images'))
            ->multiple()
            ->disk('public')
            ->directory('feed/image')
            ->maxSize(10240)
            ->maxFiles(8)
            ->previewable()
            ->downloadable()
            ->openable()
            ->acceptedFileTypes(['image/*'])
            ->getUploadedFileNameForStorageUsing(
                fn(TemporaryUploadedFile $file): string => Str::random(12) . '-' . time() . '.' . $file->getClientOriginalExtension()
            )
            ->image()
            ->imagePreviewHeight('80')
            ->panelLayout('grid')
            ->reorderable()
            ->columnSpanFull()
            ->helperText(__('resources/feed/strings.hints.media_images'));
    }

    public static function mediaVideos(): FileUpload
    {
        return FileUpload::make('media_videos')
            ->label(__('resources/feed/strings.fields.media_videos'))
            ->disk('public')
            ->directory('feed/video')
            ->previewable()
            ->downloadable()
            ->openable()
            ->maxSize(102400)
            ->maxFiles(1)
            ->acceptedFileTypes([
                'video/mp4',
                'video/mpeg',
                'video/ogg',
                'video/webm',
                'video/quicktime',
                'video/x-msvideo',
                'video/x-flv',
            ])
            ->getUploadedFileNameForStorageUsing(
                fn(TemporaryUploadedFile $file): string => Str::random(12) . '-' . time() . '.' . $file->getClientOriginalExtension()
            )
            ->columnSpanFull()
            ->reorderable()
            ->helperText(__('resources/feed/strings.hints.media_videos'));
    }

    public static function mergeMediaPaths(array $data): array
    {
        $images = Arr::wrap($data['media_images'] ?? []);
        $videos = Arr::wrap($data['media_videos'] ?? []);

        $data['media_paths'] = array_values(array_merge($images, $videos));

        unset($data['media_images'], $data['media_videos']);

        return $data;
    }

    public static function pollOptions(): Repeater
    {
        return Repeater::make('poll_options')
            ->label(__('resources/feed/strings.fields.poll_options'))
            ->schema([
                TextInput::make('option')
                    ->label(__('resources/feed/strings.fields.poll_option'))
                    ->placeholder('✎')
                    ->required()
                    ->maxLength(200),
            ])
            ->afterStateHydrated(function (Repeater $component, $state) {
                if (blank($state) || !is_array($state)) return $component->state([]);

                $firstItem = reset($state);
                if (is_string($firstItem)) {
                    $mapped = collect($state)
                        ->filter()
                        ->map(fn($value) => ['option' => $value])
                        ->values()
                        ->all();

                    return $component->state($mapped);
                }

                return $component->state($state);
            })
            ->dehydrateStateUsing(fn($state) => collect($state)->pluck('option')->filter()->values()->all())
            ->itemLabel(fn(array $state): ?string => $state['option'] ?? null)
            ->minItems(2)
            ->maxItems(10)
            ->addActionLabel(__('resources/feed/strings.actions.add_poll_option'))
            ->visible(fn($get) => ($get('category')?->value ?? $get('category')) === FeedCategory::Poll->value)
            ->columnSpanFull()
            ->helperText(__('resources/feed/strings.hints.poll_options'));
    }

    public static function splitMediaPaths(array $data): array
    {
        $paths = $data['media_paths'] ?? [];

        [$images, $videos] = collect($paths)->partition(function ($p) {
            return in_array(strtolower(pathinfo($p, PATHINFO_EXTENSION)), Feed::IMAGE_EXTENSIONS);
        });

        $data['media_images'] = $images->values()->all();
        $data['media_videos'] = $videos->first();

        return $data;
    }

    public static function userId(): Select
    {
        return Select::make('user_id')
            ->label(__('resources/feed/strings.fields.creator'))
            ->relationship('user', 'name')
            ->default(auth()->id())
            ->disabledOn('edit')
            ->searchable()
            ->preload()
            ->required()
            ->helperText(__('resources/feed/strings.hints.user_id'))
            ;
    }
}
