<?php

namespace App\Filament\Resources\AuthorityResource\Schemas;

use App\Filament\Resources\AuthorityResource\Enums\{DelegationLevel, ExecutionProcedure, ImpactScore, RepeatFrequency};
use App\Models\Department;
use App\Services\ContentSanitizerService;
use App\Traits\FilamentFormDivider;
use Filament\Forms\Components\{RichEditor,
    RichEditor\TextColor,
    RichEditor\ToolbarButtonGroup,
    Select,
    TextInput,
    Toggle};

class AuthorityFormPresenter
{
    use FilamentFormDivider;
    public static function approvedDelegation(): Select
    {
        return Select::make('details.approved_delegation')
            ->validationMessages([
                'required' => __('resources/authority/strings.validation.generic.required'),
                'unique' => __('resources/authority/strings.validation.generic.unique'),
                'max' => __('resources/authority/strings.validation.generic.max'),
                'min' => __('resources/authority/strings.validation.generic.min'),
                'email' => __('resources/authority/strings.validation.generic.email'),
                'numeric' => __('resources/authority/strings.validation.generic.numeric'),
                'mimes' => __('resources/authority/strings.validation.generic.mimes'),
                'url' => __('resources/authority/strings.validation.generic.url'),
                'in' => __('resources/authority/strings.validation.generic.in'),
                'exists' => __('resources/authority/strings.validation.generic.exists')
            ])
            ->label(__('resources/authority/strings.fields.approved_delegation'))
            ->options(DelegationLevel::class)
            ->nullable()
            ->helperText(__('resources/authority/strings.hints.approved_delegation'));
    }

    public static function coDelegate(): TextInput
    {
        return TextInput::make('details.co_delegate')
            ->validationMessages([
                'required' => __('resources/authority/strings.validation.generic.required'),
                'unique' => __('resources/authority/strings.validation.generic.unique'),
                'max' => __('resources/authority/strings.validation.generic.max'),
                'min' => __('resources/authority/strings.validation.generic.min'),
                'email' => __('resources/authority/strings.validation.generic.email'),
                'numeric' => __('resources/authority/strings.validation.generic.numeric'),
                'mimes' => __('resources/authority/strings.validation.generic.mimes'),
                'url' => __('resources/authority/strings.validation.generic.url'),
                'in' => __('resources/authority/strings.validation.generic.in'),
                'exists' => __('resources/authority/strings.validation.generic.exists')
            ])
            ->label(__('resources/authority/strings.fields.co_delegate'))
            ->maxLength(255)
            ->nullable()
            ->helperText(__('resources/authority/strings.hints.co_delegate'));
    }

    public static function departmentId(): Select
    {
        return Select::make('department_id')
            ->validationMessages([
                'required' => __('resources/authority/strings.validation.generic.required'),
                'unique' => __('resources/authority/strings.validation.generic.unique'),
                'max' => __('resources/authority/strings.validation.generic.max'),
                'min' => __('resources/authority/strings.validation.generic.min'),
                'email' => __('resources/authority/strings.validation.generic.email'),
                'numeric' => __('resources/authority/strings.validation.generic.numeric'),
                'mimes' => __('resources/authority/strings.validation.generic.mimes'),
                'url' => __('resources/authority/strings.validation.generic.url'),
                'in' => __('resources/authority/strings.validation.generic.in'),
                'exists' => __('resources/authority/strings.validation.generic.exists')
            ])
            ->label(__('resources/authority/strings.fields.department'))
            ->options(fn() => Department::getCachedOptions()->toArray())
            ->searchable()
            ->preload()
            ->nullable()
            ->helperText(__('resources/authority/strings.hints.department_id'));
    }

    public static function duty(): RichEditor
    {
        return RichEditor::make('details.duty')
            ->validationMessages([
                'required' => __('resources/authority/strings.validation.generic.required'),
                'unique' => __('resources/authority/strings.validation.generic.unique'),
                'max' => __('resources/authority/strings.validation.generic.max'),
                'min' => __('resources/authority/strings.validation.generic.min'),
                'email' => __('resources/authority/strings.validation.generic.email'),
                'numeric' => __('resources/authority/strings.validation.generic.numeric'),
                'mimes' => __('resources/authority/strings.validation.generic.mimes'),
                'url' => __('resources/authority/strings.validation.generic.url'),
                'in' => __('resources/authority/strings.validation.generic.in'),
                'exists' => __('resources/authority/strings.validation.generic.exists')
            ])
            ->label(__('resources/authority/strings.fields.duty'))
            ->nullable()
            ->maxLength(2000)
            ->columnSpanFull()
            ->placeholder(__('resources/authority/strings.placeholders.description'))
            ->helperText(__('resources/authority/strings.helper_text.description'))
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
                'required' => __('resources/authority/strings.validation.authority_required'),
                'max' => __('resources/authority/strings.validation.authority_max_length', ['length' => 2000]),
            ])
            ->mutateDehydratedStateUsing(fn(?string $state): ?string => ContentSanitizerService::clean($state));
    }

    public static function executionProcedure(): Select
    {
        return Select::make('details.execution_procedure')
            ->validationMessages([
                'required' => __('resources/authority/strings.validation.generic.required'),
                'unique' => __('resources/authority/strings.validation.generic.unique'),
                'max' => __('resources/authority/strings.validation.generic.max'),
                'min' => __('resources/authority/strings.validation.generic.min'),
                'email' => __('resources/authority/strings.validation.generic.email'),
                'numeric' => __('resources/authority/strings.validation.generic.numeric'),
                'mimes' => __('resources/authority/strings.validation.generic.mimes'),
                'url' => __('resources/authority/strings.validation.generic.url'),
                'in' => __('resources/authority/strings.validation.generic.in'),
                'exists' => __('resources/authority/strings.validation.generic.exists')
            ])
            ->label(__('resources/authority/strings.fields.execution_procedure'))
            ->options(ExecutionProcedure::class)
            ->nullable()
            ->helperText(__('resources/authority/strings.hints.execution_procedure'));
    }

    public static function impactScore(): Select
    {
        return Select::make('details.impact_score')
            ->validationMessages([
                'required' => __('resources/authority/strings.validation.generic.required'),
                'unique' => __('resources/authority/strings.validation.generic.unique'),
                'max' => __('resources/authority/strings.validation.generic.max'),
                'min' => __('resources/authority/strings.validation.generic.min'),
                'email' => __('resources/authority/strings.validation.generic.email'),
                'numeric' => __('resources/authority/strings.validation.generic.numeric'),
                'mimes' => __('resources/authority/strings.validation.generic.mimes'),
                'url' => __('resources/authority/strings.validation.generic.url'),
                'in' => __('resources/authority/strings.validation.generic.in'),
                'exists' => __('resources/authority/strings.validation.generic.exists')
            ])
            ->label(__('resources/authority/strings.fields.impact_score'))
            ->options(ImpactScore::class)
            ->nullable()
            ->helperText(__('resources/authority/strings.hints.impact_score'));
    }

    public static function proposedDelegation(): Select
    {
        return Select::make('details.proposed_delegation')
            ->validationMessages([
                'required' => __('resources/authority/strings.validation.generic.required'),
                'unique' => __('resources/authority/strings.validation.generic.unique'),
                'max' => __('resources/authority/strings.validation.generic.max'),
                'min' => __('resources/authority/strings.validation.generic.min'),
                'email' => __('resources/authority/strings.validation.generic.email'),
                'numeric' => __('resources/authority/strings.validation.generic.numeric'),
                'mimes' => __('resources/authority/strings.validation.generic.mimes'),
                'url' => __('resources/authority/strings.validation.generic.url'),
                'in' => __('resources/authority/strings.validation.generic.in'),
                'exists' => __('resources/authority/strings.validation.generic.exists')
            ])
            ->label(__('resources/authority/strings.fields.proposed_delegation'))
            ->options(DelegationLevel::class)
            ->nullable()
            ->helperText(__('resources/authority/strings.hints.proposed_delegation'));
    }

    public static function repeatFrequency(): Select
    {
        return Select::make('details.repeat_frequency')
            ->validationMessages([
                'required' => __('resources/authority/strings.validation.generic.required'),
                'unique' => __('resources/authority/strings.validation.generic.unique'),
                'max' => __('resources/authority/strings.validation.generic.max'),
                'min' => __('resources/authority/strings.validation.generic.min'),
                'email' => __('resources/authority/strings.validation.generic.email'),
                'numeric' => __('resources/authority/strings.validation.generic.numeric'),
                'mimes' => __('resources/authority/strings.validation.generic.mimes'),
                'url' => __('resources/authority/strings.validation.generic.url'),
                'in' => __('resources/authority/strings.validation.generic.in'),
                'exists' => __('resources/authority/strings.validation.generic.exists')
            ])
            ->label(__('resources/authority/strings.fields.repeat_frequency'))
            ->options(RepeatFrequency::class)
            ->nullable()
            ->helperText(__('resources/authority/strings.hints.repeat_frequency'));
    }

    public static function subDuty(): Toggle
    {
        return Toggle::make('sub_duty')
            ->validationMessages([
                'required' => __('resources/authority/strings.validation.generic.required'),
                'unique' => __('resources/authority/strings.validation.generic.unique'),
                'max' => __('resources/authority/strings.validation.generic.max'),
                'min' => __('resources/authority/strings.validation.generic.min'),
                'email' => __('resources/authority/strings.validation.generic.email'),
                'numeric' => __('resources/authority/strings.validation.generic.numeric'),
                'mimes' => __('resources/authority/strings.validation.generic.mimes'),
                'url' => __('resources/authority/strings.validation.generic.url'),
                'in' => __('resources/authority/strings.validation.generic.in'),
                'exists' => __('resources/authority/strings.validation.generic.exists')
            ])
            ->label(__('resources/authority/strings.fields.sub_duty'))
            ->inline(false)
            ->helperText(__('resources/authority/strings.hints.sub_duty'));
    }

    public static function userId(): Select
    {
        return Select::make('user_id')
            ->validationMessages([
                'required' => __('resources/authority/strings.validation.generic.required'),
                'unique' => __('resources/authority/strings.validation.generic.unique'),
                'max' => __('resources/authority/strings.validation.generic.max'),
                'min' => __('resources/authority/strings.validation.generic.min'),
                'email' => __('resources/authority/strings.validation.generic.email'),
                'numeric' => __('resources/authority/strings.validation.generic.numeric'),
                'mimes' => __('resources/authority/strings.validation.generic.mimes'),
                'url' => __('resources/authority/strings.validation.generic.url'),
                'in' => __('resources/authority/strings.validation.generic.in'),
                'exists' => __('resources/authority/strings.validation.generic.exists')
            ])
            ->label(__('resources/authority/strings.fields.user'))
            ->relationship('user', 'name')
            ->default(auth()->id() ?? null)
            ->searchable()
            ->preload()
            ->nullable()
            ->helperText(__('resources/authority/strings.hints.user_id'));
    }
}
