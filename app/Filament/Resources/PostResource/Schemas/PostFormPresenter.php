<?php

namespace App\Filament\Resources\PostResource\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\TextColor;
use Filament\Forms\Components\RichEditor\ToolbarButtonGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PostFormPresenter
{
    public static function userId(): Select
    {
        return Select::make('user_id')
            ->label(__('resources/post/strings.fields.user'))
            ->relationship('user', 'name')
            ->searchable()
            ->afterStateHydrated(fn($state, callable $set) => $set('user_id', $state ?? auth()->id()))
            ->default(fn() => auth()->id())
            ->disabled()
            ->dehydrated(true)
            ->helperText(__('resources/post/strings.hint.user_locked'))
            ->preload()
            ->required();
    }

    public static function title(): RichEditor
    {
        return RichEditor::make('title')
            ->label(__('resources/post/strings.fields.title'))
            ->required()
            ->maxLength(700)
            ->columnSpanFull()
            ->textColors([
                'primary' => TextColor::make('Primary', '#3b82f6', darkColor: '#60a5fa'),
                ...TextColor::getDefaults(),
            ])
            ->toolbarButtons([
                ['bold', 'italic', 'underline', 'textColor'],
                ['link'],
                ['bulletList', 'orderedList'],
                ['undo', 'redo'],
            ])
            ->floatingToolbars([
                'paragraph' => ['bold', 'italic', 'link'],
            ])
            ->validationMessages([
                'required' => __('resources/post/strings.validation.title_required'),
                'max' => __('resources/post/strings.validation.title_max_length', ['length' => 700]),
            ])
            ->extraInputAttributes(['class' => 'fi-prose', 'style' => 'min-height: 120px;']);
    }


    public static function body(): RichEditor
    {
        return RichEditor::make('body')
            ->label(__('resources/post/strings.fields.body'))
            ->required()
            ->maxLength(50000)
            ->columnSpanFull()
            ->textColors([
                'primary' => TextColor::make('Primary', '#3b82f6', darkColor: '#60a5fa'),
                'success' => TextColor::make('Success', '#22c55e', darkColor: '#4ade80'),
                'warning' => TextColor::make('Warning', '#f59e0b', darkColor: '#fbbf24'),
                'danger'  => TextColor::make('Danger', '#ef4444', darkColor: '#f87171'),
                ...TextColor::getDefaults(),
            ])
            ->extraInputAttributes(['class' => 'fi-prose', 'style' => 'min-height: 320px;'])
            ->customTextColors()
            ->toolbarButtons([
                ['textColor', 'bold', 'italic', 'underline', 'strike', 'code', 'clearFormatting'],
                [ToolbarButtonGroup::make('Headings', ['paragraph', 'h2', 'h3', 'h4'])
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
                'heading'   => ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                'table'     => [
                    'tableAddColumnBefore', 'tableAddColumnAfter', 'tableDeleteColumn',
                    'tableAddRowBefore', 'tableAddRowAfter', 'tableDeleteRow',
                    'tableMergeCells', 'tableSplitCell', 'tableToggleHeaderRow', 'tableDelete',
                ],
            ])
            ->validationMessages([
                'required' => __('resources/post/strings.validation.body_required'),
                'max'      => __('resources/post/strings.validation.body_max', ['length' => 50000]),
            ]);
    }

    public static function image(): FileUpload
    {
        return FileUpload::make('image')
            ->label(__('resources/post/strings.fields.image'))
            ->disk('public')
            ->directory('post/image')
            ->maxSize(2048)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
            ->getUploadedFileNameForStorageUsing(
                fn(TemporaryUploadedFile $file): string =>
                    Str::random(12) . '-' . time() . '.' . $file->getClientOriginalExtension()
            )
            ->image()
            ->imagePreviewHeight('160')
            ->columnSpanFull();
    }

    public static function pinned(): Toggle
    {
        return Toggle::make('pinned')
            ->label(__('resources/post/strings.fields.pinned'))
            ->helperText(__('resources/post/strings.fields.pinned_hint'))
            ->inline(false)
            ->default(false);
    }
}
