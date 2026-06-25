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
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
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
            ->helperText(__('resources/feed/strings.hints.category'));
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
            ]);
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

    public static function packPollSettings(array $data): array
    {
        $category = $data['category'] ?? null;
        $categoryValue = $category instanceof FeedCategory ? $category->value : $category;

        if ($categoryValue !== FeedCategory::Poll->value) {
            $data['poll_options'] = null;

            unset($data['poll_mode'], $data['poll_comments_enabled'], $data['poll_reactions_enabled']);

            return $data;
        }

        $mode = ($data['poll_mode'] ?? 'single') === 'multiple' ? 'multiple' : 'single';
        $comments = !empty($data['poll_comments_enabled']) ? '1' : '0';
        $reactions = !empty($data['poll_reactions_enabled']) ? '1' : '0';
        $choices = array_values(Arr::wrap($data['poll_options'] ?? []));

        $data['poll_options'] = array_merge([$mode, $comments, $reactions], $choices);

        unset($data['poll_mode'], $data['poll_comments_enabled'], $data['poll_reactions_enabled']);

        return $data;
    }

    public static function unpackPollSettings(array $data): array
    {
        $options = $data['poll_options'] ?? [];

        $extracted = Feed::extractPollSettings(is_array($options) ? $options : []);

        $data['poll_mode'] = $extracted['mode'];
        $data['poll_comments_enabled'] = $extracted['comments'];
        $data['poll_reactions_enabled'] = $extracted['reactions'];
        $data['poll_options'] = $extracted['choices'];

        return $data;
    }

    public static function pollSettings(): Grid
    {
        return Grid::make(3)
            ->schema([
                Select::make('poll_mode')
                    ->label(__('resources/feed/strings.fields.poll_mode'))
                    ->options([
                        'single'   => __('resources/feed/strings.poll.mode_single'),
                        'multiple' => __('resources/feed/strings.poll.mode_multiple'),
                    ])
                    ->default('single')
                    ->required()
                    ->selectablePlaceholder(false)
                    ->helperText(__('resources/feed/strings.hints.poll_mode')),

                Toggle::make('poll_comments_enabled')
                    ->label(__('resources/feed/strings.fields.poll_comments_enabled'))
                    ->default(true)
                    ->helperText(__('resources/feed/strings.hints.poll_comments_enabled')),

                Toggle::make('poll_reactions_enabled')
                    ->label(__('resources/feed/strings.fields.poll_reactions_enabled'))
                    ->default(true)
                    ->helperText(__('resources/feed/strings.hints.poll_reactions_enabled')),
            ])
            ->visible(fn($get) => ($get('category')?->value ?? $get('category')) === FeedCategory::Poll->value)
            ->columnSpanFull();
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
            ->helperText(__('resources/feed/strings.hints.user_id'));
    }
}
