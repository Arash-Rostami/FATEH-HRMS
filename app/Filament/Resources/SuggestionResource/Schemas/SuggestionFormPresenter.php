<?php

namespace App\Filament\Resources\SuggestionResource\Schemas;

use App\Models\Department;
use App\Models\Suggestion;
use App\Rules\SuggestionSubmitterNotFromMaOrMg;
use App\Traits\FilamentFormDivider;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\TextColor;
use Filament\Forms\Components\RichEditor\ToolbarButtonGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class SuggestionFormPresenter
{
    use FilamentFormDivider;

    public static function attachment(): FileUpload
    {
        return FileUpload::make('attachment')
            ->label(__('resources/suggestion/strings.fields.attachment'))
            ->disk('public')
            ->directory('suggestions')
            ->openable()
            ->downloadable()
            ->previewable()
            ->acceptedFileTypes(['application/pdf', 'image/png', 'image/jpeg'])
            ->maxSize(10240)
            ->helperText(__('resources/suggestion/strings.hints.attachment'))
            ->columnSpanFull();
    }

    public static function departments(): Select
    {
        return Select::make('departments')
            ->label(__('resources/suggestion/strings.fields.departments'))
            ->helperText(__('resources/suggestion/strings.form.departments_helper'))
            ->multiple()
            ->options(fn(): array => Department::getCachedOptions()->toArray())
            ->searchable()
            ->preload()
            ->columnSpanFull();
    }

    public static function description(): RichEditor
    {
        return RichEditor::make('description')
            ->label(__('resources/suggestion/strings.fields.description'))
            ->required()
            ->minLength(10)
            ->maxLength(50000)
            ->columnSpanFull()
            ->textColors([
                'primary' => TextColor::make('Primary', '#3b82f6', darkColor: '#60a5fa'),
                'success' => TextColor::make('Success', '#22c55e', darkColor: '#4ade80'),
                'warning' => TextColor::make('Warning', '#f59e0b', darkColor: '#fbbf24'),
                'danger' => TextColor::make('Danger', '#ef4444', darkColor: '#f87171'),
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
                'heading' => ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                'table' => [
                    'tableAddColumnBefore', 'tableAddColumnAfter', 'tableDeleteColumn',
                    'tableAddRowBefore', 'tableAddRowAfter', 'tableDeleteRow',
                    'tableMergeCells', 'tableSplitCell', 'tableToggleHeaderRow', 'tableDelete',
                ],
            ])
            ->helperText(__('resources/suggestion/strings.hints.description'));
    }

    public static function priority(): Select
    {
        return Select::make('priority')
            ->label(__('resources/suggestion/strings.fields.priority'))
            ->options(Suggestion::PRIORITIES)
            ->default('medium')
            ->native(false)
            ->helperText(__('resources/suggestion/strings.hints.priority'));
    }

    public static function purpose(): CheckboxList
    {
        return CheckboxList::make('purpose')
            ->label(__('resources/suggestion/strings.fields.purpose'))
            ->options(Suggestion::PURPOSES)
            ->required()
            ->columns(3)
            ->bulkToggleable()
            ->helperText(__('resources/suggestion/strings.hints.purpose'))
            ->columnSpanFull();
    }

    public static function rule(): CheckboxList
    {
        return CheckboxList::make('rule')
            ->label(__('resources/suggestion/strings.fields.rule'))
            ->options(Suggestion::RULES)
            ->required()
            ->columns(2)
            ->bulkToggleable()
            ->helperText(__('resources/suggestion/strings.hints.rule'))
            ->columnSpanFull();
    }

    public static function selfFill(): Toggle
    {
        return Toggle::make('self_fill')
            ->label(__('resources/suggestion/strings.fields.self_fill'))
            ->helperText(__('resources/suggestion/strings.form.self_fill_helper'))
            ->inline(false)
            ->disabled()
            ->default(false);
    }

    public static function title(): TextInput
    {
        return TextInput::make('title')
            ->label(__('resources/suggestion/strings.fields.title'))
            ->required()
            ->minLength(3)
            ->maxLength(255)
            ->helperText(__('resources/suggestion/strings.hints.title'))
            ->columnSpanFull();
    }

    public static function userId(): Select
    {
        return Select::make('user_id')
            ->label(__('resources/suggestion/strings.fields.submitter'))
            ->relationship('user', 'name')
            ->searchable(['name', 'email'])
            ->afterStateHydrated(fn($state, callable $set) => $set('user_id', $state ?? auth()->id()))
            ->default(fn() => auth()->id())
            ->disabled()
            ->dehydrated(true)
            ->helperText(__('resources/suggestion/strings.fields.user_locked'))
            ->preload()
            ->required()
            ->rule(new SuggestionSubmitterNotFromMaOrMg())
            ->default(fn() => auth()->id());
    }
}
