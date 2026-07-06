<?php

namespace App\Livewire\Dashboard\Channel;

use App\Livewire\Dashboard\Channel\Actions\CreateChannelAction;
use App\Livewire\Dashboard\Channel\Actions\DeleteChannelMessageAction;
use App\Livewire\Dashboard\Channel\Actions\DownloadChannelAttachmentAction;
use App\Livewire\Dashboard\Channel\Actions\FetchChannelsAction;
use App\Livewire\Dashboard\Channel\Actions\FetchJoinableChannelsAction;
use App\Livewire\Dashboard\Channel\Actions\FocusChannelMessageAction;
use App\Livewire\Dashboard\Channel\Actions\JoinChannelAction;
use App\Livewire\Dashboard\Channel\Actions\LeaveChannelAction;
use App\Livewire\Dashboard\Channel\Actions\MarkChannelReadAction;
use App\Livewire\Dashboard\Channel\Actions\SaveEditChannelMessageAction;
use App\Livewire\Dashboard\Channel\Actions\SearchChannelMessagesAction;
use App\Livewire\Dashboard\Channel\Actions\SendChannelMessageAction;
use App\Livewire\Dashboard\Channel\Actions\UndoDeleteChannelMessageAction;
use App\Livewire\Dashboard\Channel\Forms\ChannelMessageComposerForm;
use App\Livewire\Dashboard\Channel\Forms\CreateChannelForm;
use App\Livewire\Dashboard\Channel\Forms\EditChannelMessageForm;
use App\Livewire\Dashboard\Channel\Presentation\ChannelPresenter;
use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\ChannelMessage;
use App\Traits\FocusOnRecord;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Async;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\Js;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Isolate]
class Main extends Component
{
    use FocusOnRecord, WithFileUploads;

    public ChannelMessageComposerForm $composer;
    public EditChannelMessageForm $edit;
    public CreateChannelForm $create;

    #[Locked]
    public ?int $activeChannelId = null;

    public string $search = '';
    public string $messageSearch = '';
    public string $filter = 'all';
    public bool $browseMode = false;
    public bool $createMode = false;
    public bool $mobileShowChat = false;
    public int $editTimeLimit = 300;
    public ?array $editingMsg = null;
    public ?array $lastDeleted = null;
    public int $loadedLimit = 10;
    public bool $hasOlder = false;
    #[Locked]
    public ?int $focusAnchorId = null;
    #[Locked]
    public int $focusOlder = 5;

    public function mount(): void
    {
        $this->composer->fill(['body' => '', 'attachments' => [], 'replyToId' => null]);
        $this->edit->fill(['editingBody' => '']);
        $this->create->fill(['name' => '', 'slug' => '', 'description' => null, 'type' => 'open']);
    }

    public function updated(string $name): void
    {
        if ($name === 'composer.attachments') {
            $this->dispatch('attachments-updated')->self();
        }
    }

    public function syncAttachments(): void
    {
        unset($this->groupedMessages);
    }

    #[Computed]
    public function activeChannel(): ?Channel
    {
        return $this->activeChannelId
            ? Channel::with('owner')->withCount('members')->find($this->activeChannelId)
            : null;
    }

    #[Computed]
    public function channels(): array
    {
        $rows = app(FetchChannelsAction::class)->execute(auth()->id(), $this->search, $this->filter);

        $messageIds = $rows->pluck('last_message_id')->filter()->unique();
        $lastMessages = ChannelMessage::withoutTrashed()
            ->whereIn('id', $messageIds)
            ->get()
            ->keyBy('id');

        return $rows->map(function (Channel $ch) use ($lastMessages) {
            $last = $lastMessages->get($ch->last_message_id);
            return [
                'id' => (int)$ch->id,
                'name' => $ch->name,
                'slug' => $ch->slug,
                'description' => $ch->description,
                'type' => $ch->type->value,
                'owner_id' => $ch->owner_id,
                'unread_count' => (int)($ch->unread_count ?? 0),
                'last_message' => $last ? [
                    'body' => $last->body,
                    'sender_id' => $last->sender_id,
                    'created_at' => $last->created_at->toISOString(),
                ] : null,
            ];
        })->values()->all();
    }

    #[Computed]
    public function joinableChannels(): array
    {
        return app(FetchJoinableChannelsAction::class)->execute(auth()->id())
            ->map(fn(Channel $ch) => [
                'id' => (int)$ch->id,
                'name' => $ch->name,
                'slug' => $ch->slug,
                'description' => $ch->description,
                'type' => $ch->type->value,
                'owner_name' => $ch->owner?->name ?? '—',
            ])->values()->all();
    }

    #[Computed]
    public function messages(): array
    {
        if (!$this->activeChannelId) {
            $this->hasOlder = false;
            return [];
        }

        if ($this->focusAnchorId !== null) {
            $anchor = (int) $this->focusAnchorId;
            $newer = ChannelMessage::withoutTrashed()
                ->where('channel_id', $this->activeChannelId)
                ->with(['sender', 'replyTo.sender'])
                ->where('id', '>=', $anchor)
                ->oldest('id')
                ->take(6)
                ->get();
            $older = ChannelMessage::withoutTrashed()
                ->where('channel_id', $this->activeChannelId)
                ->with(['sender', 'replyTo.sender'])
                ->where('id', '<', $anchor)
                ->latest('id')
                ->take($this->focusOlder + 1)
                ->get();
            $this->hasOlder = $older->count() > $this->focusOlder;
            $rows = $older->take($this->focusOlder)->merge($newer)->sortBy('id')->values();
        } else {
            $rows = ChannelMessage::withoutTrashed()
                ->where('channel_id', $this->activeChannelId)
                ->with(['sender', 'replyTo.sender'])
                ->latest('id')
                ->take($this->loadedLimit + 1)
                ->get();
            $this->hasOlder = $rows->count() > $this->loadedLimit;
            $rows = $rows->take($this->loadedLimit)->sortBy('id')->values();
        }

        return $rows->map(fn($m) => [
            'id' => (int)$m->id,
            'sender_id' => (int)$m->sender_id,
            'body' => $m->body,
            'attachments' => $m->attachments,
            'reply_to_id' => $m->reply_to_id,
            'is_edited' => (bool)$m->is_edited,
            'created_at' => $m->created_at->toISOString(),
            'deleted_at' => $m->deleted_at?->toISOString(),
            'reply_to' => $m->replyTo ? [
                'id' => $m->replyTo->id,
                'body' => $m->replyTo->body,
                'sender' => ['name' => $m->replyTo->sender?->name ?? 'ناشناس'],
            ] : null,
            'sender' => ['name' => $m->sender?->name ?? 'ناشناس'],
        ])->values()->all();
    }

    #[Computed]
    public function groupedMessages(): array
    {
        return collect($this->messages)
            ->groupBy(fn($m) => Carbon::parse($m['created_at'])->toDateString())
            ->map(fn($group) => $group->values()->all())
            ->all();
    }

    #[Computed]
    public function presenter(): ChannelPresenter
    {
        return new ChannelPresenter();
    }

    #[Computed]
    public function messageSearchResults(): array
    {
        if (!$this->activeChannelId) {
            return [];
        }

        return app(SearchChannelMessagesAction::class)->execute(
            (int) $this->activeChannelId,
            $this->messageSearch,
            (int) auth()->id(),
        );
    }

    public function selectChannel(int $channelId): void
    {
        $channel = Channel::withoutTrashed()->find($channelId);
        if (!$channel || !ChannelMember::where('channel_id', $channelId)->where('user_id', auth()->id())->exists()) {
            return;
        }

        $this->activeChannelId = $channelId;
        $this->mobileShowChat = true;
        $this->browseMode = false;
        $this->loadedLimit = 10;
        $this->hasOlder = false;
        $this->focusAnchorId = null;
        $this->focusOlder = 5;
        $this->editingMsg = null;
        $this->lastDeleted = null;
        $this->messageSearch = '';
        $this->composer->reset();
        $this->edit->reset();

        $this->markRead($channelId);

        unset($this->channels, $this->messages, $this->activeChannel);
    }

    public function focusRecord(int $channelId): void
    {
        if (ChannelMember::where('channel_id', $channelId)->where('user_id', auth()->id())->exists()) {
            $this->selectChannel($channelId);

            $focusMsg = (int) request()->query('focus_msg', 0);
            if ($focusMsg > 0) {
                $this->focusMessage($focusMsg);
            }
        }
    }

    public function focusMessage(int $id): void
    {
        if (!$this->activeChannelId || $id <= 0) {
            return;
        }

        $overLimit = app(FocusChannelMessageAction::class)->execute(
            (int) $this->activeChannelId,
            $id,
            (int) auth()->id(),
            $this->loadedLimit
        );

        if ($overLimit === null) {
            return;
        }

        $this->messageSearch = '';

        if (!$overLimit) {
            if ($this->focusAnchorId !== null) {
                $this->focusAnchorId = null;
                $this->focusOlder = 5;
                unset($this->messages, $this->groupedMessages);
            }
            $this->dispatch('record-focus', type: 'channel-message', id: $id);
            return;
        }

        $this->focusAnchorId = $id;
        $this->focusOlder = 5;
        unset($this->messages, $this->groupedMessages);
        $this->dispatch('record-focus', type: 'channel-message', id: $id);
    }

    public function refreshUnread(): void
    {
        unset($this->channels);
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        unset($this->channels);
    }

    public function toggleBrowse(): void
    {
        $this->browseMode = !$this->browseMode;
        $this->mobileShowChat = $this->browseMode || $this->mobileShowChat;
        unset($this->joinableChannels);
    }

    public function openCreate(): void
    {
        $this->browseMode = false;
        $this->createMode = true;
        $this->mobileShowChat = true;
        $this->create->reset();
        $this->create->type = 'open';
    }

    public function closeCreate(): void
    {
        $this->createMode = false;
        $this->mobileShowChat = false;
    }

    public function createChannel(): void
    {
        try {
            $this->create->type = 'open';

            $channel = app(CreateChannelAction::class)->execute($this->create);

            $this->createMode = false;
            unset($this->channels, $this->joinableChannels, $this->activeChannel);
            $this->selectChannel($channel->id);
            $this->dispatch('show-toast', message: 'کانال ایجاد شد', type: 'success');
        } catch (ValidationException $e) {
            $this->dispatch('show-toast', message: collect($e->errors())->first()[0] ?? 'خطا در ایجاد کانال', type: 'error');
        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'خطای سیستمی رخ داده است', type: 'error');
        }
    }

    public function backToList(): void
    {
        $this->mobileShowChat = false;
        $this->browseMode = false;
        $this->createMode = false;
        $this->activeChannelId = null;
        $this->loadedLimit = 10;
        $this->hasOlder = false;
        $this->focusAnchorId = null;
        $this->focusOlder = 5;
        $this->editingMsg = null;
        $this->messageSearch = '';
        $this->composer->reset();
        $this->edit->reset();
        unset($this->channels, $this->messages, $this->activeChannel, $this->joinableChannels);
    }

    public function replyTo(int $messageId): void
    {
        $this->composer->replyToId = $messageId;
        $this->editingMsg = null;
    }

    public function editMessage(int $messageId): void
    {
        $msg = ChannelMessage::withoutTrashed()
            ->where('channel_id', $this->activeChannelId)
            ->where('sender_id', auth()->id())
            ->find($messageId);
        if (!$msg) {
            return;
        }

        $this->editingMsg = ['id' => $messageId, 'body' => $msg->body];
        $this->edit->editingBody = $msg->body;
        $this->composer->replyToId = null;
    }

    public function loadOlder(): void
    {
        if ($this->focusAnchorId !== null) {
            $this->focusOlder += 10;
        } else {
            $this->loadedLimit += 10;
        }
        unset($this->messages);
    }

    public function send(SendChannelMessageAction $action): void
    {
        if (!$this->activeChannelId) {
            return;
        }

        try {
            $action->execute($this->composer, $this->activeChannelId);
            $this->composer->reset();
            $this->editingMsg = null;
            $this->focusAnchorId = null;
            $this->focusOlder = 5;
            unset($this->messages, $this->channels);
            $this->dispatch('message-sent');
        } catch (ValidationException $e) {
            $this->dispatch('show-toast', message: collect($e->errors())->first()[0] ?? 'خطا در ارسال پیام', type: 'error');
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'خطای سیستمی رخ داده است', type: 'error');
        }
    }

    public function saveEdit(SaveEditChannelMessageAction $action, int $editingId, string $body): void
    {
        $this->edit->editingBody = $body;
        if (!$action->execute($this->edit, $editingId)) {
            $this->dispatch('show-toast', message: 'مهلت ویرایش این پیام به پایان رسیده است.', type: 'error');
            return;
        }
        $this->edit->reset();
        $this->editingMsg = null;
        unset($this->messages);
        $this->dispatch('show-toast', message: 'پیام ویرایش شد', type: 'success');
    }

    public function deleteMessage(DeleteChannelMessageAction $action, int $deletingId): void
    {
        $snapshot = $action->execute($deletingId);
        if (!$snapshot) {
            $this->dispatch('show-toast', message: 'این پیام دیگر قابل حذف نیست.', type: 'error');
            return;
        }
        $this->lastDeleted = $snapshot;
        unset($this->messages);
        $this->dispatch('show-undo-toast', message: 'پیام حذف شد', type: 'warning');
    }

    public function undoDelete(UndoDeleteChannelMessageAction $action): void
    {
        if (!$this->lastDeleted) {
            return;
        }
        $action->execute($this->lastDeleted);
        $this->lastDeleted = null;
        unset($this->messages);
        $this->dispatch('messages-updated');
        $this->dispatch('show-toast', message: 'پیام بازیابی شد', type: 'success');
    }

    public function joinChannel(JoinChannelAction $action, int $channelId): void
    {
        $action->execute($channelId, auth()->id());
        $this->browseMode = false;
        unset($this->channels, $this->joinableChannels);
        $this->selectChannel($channelId);
    }

    public function leaveChannel(LeaveChannelAction $action, int $channelId): void
    {
        $action->execute($channelId, auth()->id());
        if ($this->activeChannelId === $channelId) {
            $this->activeChannelId = null;
            $this->mobileShowChat = false;
            $this->loadedLimit = 10;
            $this->hasOlder = false;
            $this->focusAnchorId = null;
            $this->focusOlder = 5;
            $this->messageSearch = '';
        }
        unset($this->channels, $this->joinableChannels, $this->messages, $this->activeChannel);
        $this->dispatch('show-toast', message: 'از کانال خارج شدید', type: 'info');
    }

    public function removeAttachment(int $index): void
    {
        $attachments = $this->composer->attachments;
        unset($attachments[$index]);
        $this->composer->attachments = array_values($attachments);
    }

    public function downloadAttachment(int $messageId, int $index)
    {
        return app(DownloadChannelAttachmentAction::class)->execute(
            $messageId,
            $index,
            (int) auth()->id()
        );
    }

    #[Async]
    public function markRead(int $channelId): void
    {
        app(MarkChannelReadAction::class)->execute($channelId, auth()->id());
    }

    #[Js]
    public function resetComposer()
    {
        return <<<'JS'
            $wire.composer.body = ''
            $wire.composer.attachments = []
            $wire.composer.replyToId = null
            $wire.editingMsg = null
        JS;
    }

    #[Js]
    public function cancelReply()
    {
        return <<<'JS'
            $wire.composer.replyToId = null
        JS;
    }

    #[Js]
    public function cancelEdit()
    {
        return <<<'JS'
            $wire.editingMsg = null
        JS;
    }

    public function render()
    {
        return view('livewire.dashboard.channel')->layout('layouts.app');
    }

    protected function recordFocusType(): string
    {
        return 'channel';
    }
}
