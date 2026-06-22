<?php

namespace App\Filament\Resources\ContactResource\Schemas;

use App\Traits\FilamentFormDivider;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class ContactFormPresenter
{
    use FilamentFormDivider;
    public static function body(): Textarea
    {
        return Textarea::make('body')
            ->label(__('resources/contact/strings.fields.body'))
            ->required()
            ->rows(5)
            ->maxLength(10000)
            ->columnSpanFull()
            ->helperText(__('resources/contact/strings.hints.body'))
            ->validationMessages(['required' => __('resources/contact/strings.validation.body_required'),
                'max' => __('resources/contact/strings.validation.body.max')
            ]);
    }

    public static function recipient(): Select
    {
        return Select::make('recipient_id')
            ->label(__('resources/contact/strings.fields.recipient'))
            ->relationship('recipient', 'name')
            ->disabled()
            ->dehydrated(false)
            ->validationMessages([
                'exists' => __('resources/contact/strings.validation.recipient_id.exists'),
                'in' => __('resources/contact/strings.validation.recipient_id.in')
            ]);
    }

    public static function replyToPreview(): TextInput
    {
        return TextInput::make('reply_preview')
            ->label(__('resources/contact/strings.fields.reply_to'))
            ->formatStateUsing(fn($state, $record) => $record?->replyTo
                ? '[' . ($record->replyTo->sender?->name ?? '?') . ']: ' . mb_substr(strip_tags($record->replyTo->body ?? ''), 0, 120)
                : '—'
            )
            ->disabled()
            ->dehydrated(false)
            ->columnSpanFull();
    }

    public static function sender(): Select
    {
        return Select::make('sender_id')
            ->label(__('resources/contact/strings.fields.sender'))
            ->relationship('sender', 'name')
            ->disabled()
            ->dehydrated(false);
    }
}
