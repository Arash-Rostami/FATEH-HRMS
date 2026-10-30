<?php

namespace App\Filament\Resources\NotifyRuleResource\Schemas\Action;

use App\Filament\Resources\NotifyRuleResource;
use App\Models\NotifyRule;
use App\Services\Notify\Message;
use App\Services\Notify\MessageBuilder;
use App\Services\Notify\NotifyRegistry;
use App\Services\Notify\NotifyService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Throwable;

class SendTestAction
{
    public static function make(string $channelKey = 'mail'): Action
    {
        return Action::make('sendTest'.ucfirst($channelKey))
            ->label(__("resources/notify_rule/strings.actions.send_test_{$channelKey}"))
            ->icon($channelKey === 'mail'
                ? svg('heroicon-o-paper-airplane', 'size-5 rtl:-scale-x-100')
                : 'heroicon-o-device-phone-mobile')
            ->iconPosition(IconPosition::After)
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading(__("resources/notify_rule/strings.actions.send_test_{$channelKey}_confirm"))
            ->modalDescription(__('resources/notify_rule/strings.actions.send_test_body'))
            ->visible(fn (NotifyRule $record) => NotifyRuleResource::canEdit($record)
                && in_array($channelKey, $record->channels ?? [], true)
                && NotifyRegistry::channel($channelKey) !== null
                && array_key_exists($record->model, NotifyRegistry::permitted()))
            ->action(function (NotifyRule $record) use ($channelKey): void {
                $built = app(MessageBuilder::class)->build($record, NotifyService::samplePayload($record));
                $message = new Message(
                    MessageBuilder::isolate(__('resources/notify_rule/strings.actions.test_prefix').$built->subject),
                    $built->lines,
                    $built->ruleName,
                    $built->modelLabel,
                    $built->event,
                    $built->template,
                );

                $admin = Auth::user();
                $results = [];

                $channel = NotifyRegistry::channel($channelKey);

                if ($channel === null) {
                    $results[] = [$channelKey, 'unknown', false];
                } elseif (! $channel->isConfigured()) {
                    $results[] = [$channel->label(), 'not_configured', false];
                } elseif (blank($address = $channel->addressFor($admin))) {
                    $results[] = [$channel->label(), 'no_address', false];
                } else {
                    try {
                        $ok = $channel->send($message, $address);
                        $results[] = [$channel->label(), $ok ? 'sent' : 'failed', $ok];
                    } catch (Throwable $e) {
                        $results[] = [$channel->label(), 'failed', false, Str::limit($e->getMessage(), 120)];
                    }
                }

                $lines = array_map(
                    fn (array $r) => e($r[0].': '.__("resources/notify_rule/strings.test.{$r[1]}").(isset($r[3]) ? " ({$r[3]})" : '')),
                    $results
                );

                $notification = Notification::make()
                    ->title(__('resources/notify_rule/strings.actions.send_test_done'))
                    ->body(new HtmlString(implode('<br>', $lines)));

                (collect($results)->every(fn (array $r) => $r[2]) ? $notification->success() : $notification->warning())->send();
            });
    }
}
