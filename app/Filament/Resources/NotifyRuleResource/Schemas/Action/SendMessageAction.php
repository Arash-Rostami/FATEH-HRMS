<?php

namespace App\Filament\Resources\NotifyRuleResource\Schemas\Action;

use App\Filament\Resources\NotifyRuleResource;
use App\Filament\Resources\NotifyRuleResource\Schemas\NotifyRuleFormPresenter;
use App\Jobs\SendNotifyRule;
use App\Models\Department;
use App\Models\NotifyRule;
use App\Services\Notify\NotifyRegistry;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class SendMessageAction
{
    public static function make(): Action
    {
        return Action::make('sendMessage')
            ->label(__('resources/notify_rule/strings.actions.send_message'))
            ->icon(svg('heroicon-o-paper-airplane', 'size-5 rtl:-scale-x-100'))
            ->iconPosition(IconPosition::After)
            ->color('info')
            ->modalHeading(__('resources/notify_rule/strings.actions.send_message_heading'))
            ->modalDescription(__('resources/notify_rule/strings.actions.send_message_body'))
            ->modalSubmitActionLabel(__('resources/notify_rule/strings.actions.send_message_submit'))
            ->modalCancelActionLabel(__('resources/notify_rule/strings.actions.send_message_cancel'))
            ->modalWidth('3xl')
            ->visible(fn () => NotifyRuleResource::canCreate())
            ->steps([
                Step::make('recipients')
                    ->label(__('resources/notify_rule/strings.wizard.recipients'))
                    ->description(__('resources/notify_rule/strings.wizard.recipients_description'))
                    ->icon('heroicon-o-users')
                    ->schema([
                        NotifyRuleFormPresenter::channels(),
                        ...NotifyRuleFormPresenter::recipientFields(),
                    ]),
                Step::make('message')
                    ->label(__('resources/notify_rule/strings.wizard.message'))
                    ->description(__('resources/notify_rule/strings.wizard.message_description'))
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->schema([
                        Text::make(function (Get $get): string {
                            $users = count($get('user_ids') ?? []);
                            $contacts = count($get('extra.contacts') ?? []);
                            $to = match ($get('extra.audience') ?? 'users') {
                                'all' => __('resources/notify_rule/strings.audience.all'),
                                'departments' => Department::getCachedOptions()->only($get('extra.departments') ?? [])->implode('، '),
                                default => collect([
                                    $users ? __('resources/notify_rule/strings.wizard.selected_users', ['count' => $users]) : null,
                                    $contacts ? __('resources/notify_rule/strings.wizard.selected_contacts', ['count' => $contacts]) : null,
                                ])->filter()->implode(' + '),
                            };
                            $options = NotifyRegistry::channelOptions();

                            return __('resources/notify_rule/strings.wizard.summary', [
                                'to' => $to !== '' ? $to : __('resources/notify_rule/strings.wizard.nobody'),
                                'channels' => collect($get('channels') ?? [])->map(fn ($key) => $options[$key] ?? $key)->implode('، '),
                            ]);
                        })->color('info')->icon('heroicon-o-paper-airplane'),
                        ...NotifyRuleFormPresenter::messageFields(oneOff: true),
                    ]),
            ])
            ->action(function (array $data, Action $action): void {
                if (! RateLimiter::attempt('notify-manual:' . Auth::id(), 3, fn () => true, 60)) {
                    Notification::make()
                        ->title(__('resources/notify_rule/strings.manual.throttled'))
                        ->warning()
                        ->send();

                    $action->halt();
                }

                $data = NotifyRuleFormPresenter::mergeExtra($data, null);

                $rule = NotifyRule::create([
                    'name' => __('resources/notify_rule/strings.manual.name', ['at' => toJalali(now(), 'Y/m/d H:i')]),
                    'model' => NotifyRule::ONE_OFF,
                    'events' => [],
                    'channels' => $data['channels'],
                    'user_ids' => $data['user_ids'] ?? [],
                    'extra' => $data['extra'],
                    'subject' => $data['subject'],
                    'lines' => $data['lines'],
                    'is_active' => true,
                ]);

                try {
                    dispatch(new SendNotifyRule($rule->id, [
                        'model' => NotifyRule::ONE_OFF,
                        'id' => $rule->id,
                        'label' => __('resources/notify_rule/strings.manual.label'),
                        'values' => [],
                        'changed' => [],
                        'actor' => Auth::user()?->name,
                        'at' => now()->toDateTimeString(),
                    ]));
                } catch (Throwable $e) {
                    report($e);
                    $rule->delete();

                    Notification::make()
                        ->title(__('resources/notify_rule/strings.manual.failed'))
                        ->danger()
                        ->send();

                    $action->halt();
                }

                Notification::make()
                    ->title(__('resources/notify_rule/strings.manual.queued'))
                    ->body(__('resources/notify_rule/strings.manual.queued_body'))
                    ->success()
                    ->send();
            });
    }
}
