<?php

namespace App\Jobs;

use App\Models\Channel;
use App\Models\Message;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\Menu\Notifications\ChannelNudge;
use App\Services\Menu\Notifications\ContactNudge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use DateTimeInterface;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class SendChatPushJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $maxExceptions = 1;

    public int $uniqueFor = 60;

    public int $timeout = 120;

    public bool $failOnTimeout = true;

    public function __construct(
        public string $scope,
        public int $subjectId,
        public int $senderId,
        public array $only = [],
        public int $round = 0,
    ) {}

    public static function fire(string $scope, int $subjectId, int $senderId): void
    {
        if (blank(config('services.webpush.private')) || blank(config('services.webpush.public')) || blank(config('services.webpush.subject'))) {
            return;
        }

        DB::afterCommit(function () use ($scope, $subjectId, $senderId) {
            try {
                dispatch(new static($scope, $subjectId, $senderId));
            } catch (Throwable $e) {
                report($e);
            }
        });
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('chat-push'))->releaseAfter(2)->expireAfter(150)];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(15);
    }

    public function uniqueId(): string
    {
        return "{$this->scope}|{$this->subjectId}|{$this->senderId}|{$this->round}";
    }

    public function handle(): void
    {
        match ($this->scope) {
            'confirm' => $this->confirm(),
            'contact' => $this->contact(),
            'channel' => $this->channel(),
            default => null,
        };
    }

    private function confirm(): void
    {
        $subscriptions = PushSubscription::query()->whereKey($this->subjectId)->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        PushSubscription::send($subscriptions, fn (PushSubscription $subscription) => [
            'type' => 'confirm',
            'title' => 'اعلان‌ها فعال شد',
            'body' => 'از این پس پیام‌های جدید را اینجا می‌بینید.',
            'url' => '/',
            'tag' => 'push-confirm',
            'endpoint' => $subscription->endpoint,
        ]);
    }

    private function contact(): void
    {
        if (tenantOff('contact') || !$sender = User::visibleOnBoard()->find($this->senderId)) {
            return;
        }

        $unread = Message::unreadCountsFrom($this->senderId)[$this->subjectId] ?? 0;

        if ($unread > 0) {
            $this->deliver([$this->subjectId => $unread], new ContactNudge, $sender);
        }
    }

    private function channel(): void
    {
        if (tenantOff('channel') || !$channel = Channel::find($this->subjectId)) {
            return;
        }

        $this->deliver(Arr::except(Channel::unreadCountsFor($this->subjectId), $this->senderId), new ChannelNudge, $channel);
    }

    private function deliver(array $counts, ContactNudge|ChannelNudge $nudge, User|Channel $subject): void
    {
        if ($counts === []) {
            return;
        }

        $users = User::visibleOnBoard()->whereIn('users.id', array_keys($counts))->get(['users.id', 'users.name'])->keyBy('id');

        $subscriptions = PushSubscription::query()
            ->whereIn('user_id', $users->keys())
            ->where($this->scope, true)
            ->when($this->only !== [], fn ($query) => $query->whereKey($this->only))
            ->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $tag = $this->scope . '-' . ($this->scope === 'contact' ? $this->senderId : $this->subjectId);
        $url = $nudge->url($subject);

        $skipped = PushSubscription::send($subscriptions, fn (PushSubscription $subscription) => [
            'type' => 'chat',
            'title' => $nudge->title($subject, $users[$subscription->user_id]),
            'body' => convertToPersian($counts[$subscription->user_id]) . ' پیام خوانده‌نشده',
            'url' => $url,
            'tag' => $tag,
            'scope' => $this->scope,
            'badge' => (int) $counts[$subscription->user_id],
            'endpoint' => $subscription->endpoint,
        ], $tag);

        if ($skipped !== [] && $this->round < 5) {
            dispatch(new static($this->scope, $this->subjectId, $this->senderId, $skipped, $this->round + 1))->delay(65);
        }
    }
}
