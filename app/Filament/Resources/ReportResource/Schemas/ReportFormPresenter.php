<?php

namespace App\Filament\Resources\ReportResource\Schemas;

use App\Models\Department;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\TextColor;
use Filament\Forms\Components\RichEditor\ToolbarButtonGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Cache;

class ReportFormPresenter
{
    public static function active(): Toggle
    {
        return Toggle::make('active')
            ->label(__('resources/report/strings.fields.active'))
            ->default(true)
            ->inline(false);
    }

    public static function coverImage(): FileUpload
    {
        return FileUpload::make('cover_image')
            ->label(__('resources/report/strings.fields.cover_image'))
            ->image()
            ->downloadable()
            ->openable()
            ->previewable()
            ->disk('public')
            ->directory('reports/covers')
            ->imageEditor()
            ->maxSize(2048)
            ->nullable();
    }

    public static function departmentId(): Select
    {
        return Select::make('department_id')
            ->label(__('resources/report/strings.fields.department'))
            ->options(fn() => Cache::remember(
                'department_filter_options', now()->addDay(),
                fn() => Department::orderBy('name')->pluck('description', 'code')->toArray()
            ))
            ->searchable()
            ->nullable();
    }

    public static function description(): RichEditor
    {
        return RichEditor::make('description')
            ->label(__('resources/report/strings.fields.description'))
            ->nullable()
            ->maxLength(65535)
            ->columnSpanFull()
            ->placeholder(__('resources/report/strings.placeholders.description'))
            ->helperText(__('resources/report/strings.helper_text.description'))
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
            ->validationMessages([
                'required' => __('resources/report/strings.validation.description.required'),
                'max' => __('resources/report/strings.validation.description.max_length'),
            ]);
    }

    public static function filePath(): FileUpload
    {
        return FileUpload::make('file_path')
            ->label(__('resources/report/strings.fields.file_path'))
            ->downloadable()
            ->openable()
            ->previewable()
            ->disk('public')
            ->directory('reports/files')
            ->acceptedFileTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])
            ->maxSize(5048)
            ->required()
            ->validationMessages([
                'required' => __('resources/report/strings.validation.file_path.required'),
            ]);
    }

    public static function title(): TextInput
    {
        return TextInput::make('title')
            ->label(__('resources/report/strings.fields.title'))
            ->required()
            ->maxLength(255)
            ->validationMessages([
                'required' => __('resources/report/strings.validation.title.required'),
                'max' => __('resources/report/strings.validation.title.max_length'),
            ]);
    }

    public static function userId(): Select
    {
        return Select::make('user_id')
            ->label(__('resources/report/strings.fields.user'))
            ->relationship('user', 'name')
            ->searchable()
            ->preload()
            ->required()
            ->validationMessages([
                'required' => __('resources/report/strings.validation.user_id.required'),
            ]);
    }
}
