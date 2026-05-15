<?php

namespace App\Filament\Resources\SuggestionResource\Schemas;

use App\Models\Department;
use App\Models\Suggestion;
use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class SuggestionFormPresenter
{
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
            ->maxSize(2048)
            ->validationMessages([
                'mimes' => __('resources/suggestion/strings.validation.attachment.mimes'),
                'max'   => __('resources/suggestion/strings.validation.attachment.max'),
            ])
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

    public static function description(): Textarea
    {
        return Textarea::make('description')
            ->label(__('resources/suggestion/strings.fields.description'))
            ->rows(5)
            ->required()
            ->minLength(10)
            ->validationMessages([
                'required' => __('resources/suggestion/strings.validation.description.required'),
                'min'      => __('resources/suggestion/strings.validation.description.min'),
            ])
            ->columnSpanFull();
    }

    public static function priority(): Select
    {
        return Select::make('priority')
            ->label(__('resources/suggestion/strings.fields.priority'))
            ->options(Suggestion::PRIORITIES)
            ->default('low')
            ->native(false);
    }

    public static function purpose(): CheckboxList
    {
        return CheckboxList::make('purpose')
            ->label(__('resources/suggestion/strings.fields.purpose'))
            ->options(Suggestion::PURPOSES)
            ->required()
            ->columns(3)
            ->bulkToggleable()
            ->validationMessages([
                'required' => __('resources/suggestion/strings.validation.purpose.required'),
            ])
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
            ->validationMessages([
                'required' => __('resources/suggestion/strings.validation.rule.required'),
            ])
            ->columnSpanFull();
    }

    public static function selfFill(): Toggle
    {
        return Toggle::make('self_fill')
            ->label(__('resources/suggestion/strings.fields.self_fill'))
            ->helperText(__('resources/suggestion/strings.form.self_fill_helper'))
            ->inline(false)
            ->default(false);
    }

    public static function title(): TextInput
    {
        return TextInput::make('title')
            ->label(__('resources/suggestion/strings.fields.title'))
            ->required()
            ->minLength(3)
            ->maxLength(255)
            ->validationMessages([
                'required' => __('resources/suggestion/strings.validation.title.required'),
                'min'      => __('resources/suggestion/strings.validation.title.min'),
                'max'      => __('resources/suggestion/strings.validation.title.max'),
            ])
            ->columnSpanFull();
    }

    public static function userId(): Select
    {
        return Select::make('user_id')
            ->label(__('resources/suggestion/strings.fields.submitter'))
            ->relationship('user', 'name')
            ->getOptionLabelFromRecordUsing(fn(User $record): string => $record->name)
            ->searchable(['name', 'email'])
            ->preload()
            ->required()
            ->default(fn() => auth()->id());
    }
}
