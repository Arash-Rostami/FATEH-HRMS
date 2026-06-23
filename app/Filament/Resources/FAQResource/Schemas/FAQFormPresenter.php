<?php

namespace App\Filament\Resources\FAQResource\Schemas;

use App\Models\Department;
use App\Models\FAQ;
use App\Traits\FilamentFormDivider;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\TextColor;
use Filament\Forms\Components\RichEditor\ToolbarButtonGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class FAQFormPresenter
{
    use FilamentFormDivider;

    public static function answer(): RichEditor
    {
        return RichEditor::make('answer')
            ->label(__('resources/faq/strings.fields.answer'))
            ->required()
            ->maxLength(5000)
            ->columnSpanFull()
            ->placeholder(__('resources/faq/strings.placeholders.answer'))
            ->helperText(__('resources/faq/strings.helper_text.answer'))
            ->hint(__('resources/faq/strings.hint.answer'))
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
                ['textColor', 'bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'code', 'clearFormatting'],
                [ToolbarButtonGroup::make('Headings', ['paragraph', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'])
                    ->icon('fi-o-heading')
                    ->textualButtons()],
                [ToolbarButtonGroup::make('Alignment', ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'])],
                ['blockquote', 'bulletList', 'orderedList'],
                ['codeBlock', 'horizontalRule', 'highlight', 'small'],
                ['link'],
                ['table'],
                ['undo', 'redo'],
            ])
            ->floatingToolbars([
                'paragraph' => ['textColor', 'bold', 'italic', 'underline', 'strike', 'link', 'highlight'],
                'heading' => ['h1', 'h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                'table' => [
                    'tableAddColumnBefore', 'tableAddColumnAfter', 'tableDeleteColumn',
                    'tableAddRowBefore', 'tableAddRowAfter', 'tableDeleteRow',
                    'tableMergeCells', 'tableSplitCell', 'tableToggleHeaderRow', 'tableDelete',
                ],
            ])
            ,
            ]);
    }

    public static function category(): Select
    {
        return Select::make('category')
            ->label(__('resources/faq/strings.fields.category'))
            ->searchable()
            ->options(fn(): array => FAQ::distinct()->orderBy('category')->pluck('category', 'category')->toArray())
            ->createOptionForm([
                TextInput::make('category')
                    ->label(__('resources/faq/strings.fields.category'))
                    ->required()
                    ->maxLength(100),
            ])
            ->createOptionUsing(fn(array $data): string => $data['category'])
            ->extraFieldWrapperAttributes(['class' => 'no-shell'])
            ->required()
            ->helperText(__('resources/faq/strings.hints.category'))
            ;
    }

    public static function departmentId(): Select
    {
        return Select::make('department_id')
            ->label(__('resources/faq/strings.fields.department'))
            ->searchable()
            ->options(fn() => Department::getCachedOptions()->toArray())
            ->nullable()
            ->helperText(__('resources/faq/strings.hints.department_id'));
    }

    public static function question(): RichEditor
    {
        return RichEditor::make('question')
            ->label(__('resources/faq/strings.fields.question'))
            ->required()
            ->maxLength(1000)
            ->columnSpanFull()
            ->placeholder(__('resources/faq/strings.placeholders.question'))
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
            ->helperText(__('resources/faq/strings.hints.question'))
            ,
            ])
            ->extraInputAttributes(['class' => 'fi-prose', 'style' => 'min-height: 120px;']);
    }

    public static function userId(): Select
    {
        return Select::make('user_id')
            ->label(__('resources/faq/strings.fields.user'))
            ->relationship('user', 'name')
            ->afterStateHydrated(fn($state, callable $set) => $set('user_id', $state ?? auth()->id()))
            ->default(fn() => auth()->id())
            ->disabled()
            ->dehydrated(true)
            ->hint(__('resources/faq/strings.hint.user_locked'))
            ->required()
            ;
    }
}
