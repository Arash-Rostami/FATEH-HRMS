<?php

namespace App\Livewire\Dashboard\Contact;

use App\Livewire\Dashboard\Contact\Actions\DeleteMessageAction;
use App\Livewire\Dashboard\Contact\Actions\FetchContactsAction;
use App\Livewire\Dashboard\Contact\Actions\FocusMessageAction;
use App\Livewire\Dashboard\Contact\Actions\MarkMessagesAsReadAction;
use App\Livewire\Dashboard\Contact\Actions\SaveEditAction;
use App\Livewire\Dashboard\Contact\Actions\SearchMessagesAction;
use App\Livewire\Dashboard\Contact\Actions\SendMessageAction;
use App\Livewire\Dashboard\Contact\Actions\UndoDeleteAction;
use App\Livewire\Dashboard\Contact\Forms\EditMessageForm;
use App\Livewire\Dashboard\Contact\Forms\MessageComposerForm;
use App\Livewire\Dashboard\Contact\Presentation\ContactPresenter;
use App\Models\Message;
use App\Models\User;
use App\Traits\FocusOnRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\Response;

class Main extends Component
{
    use FocusOnRecord, WithFileUploads;

    public MessageComposerForm $composer;
    public EditMessageForm $edit;
    public ?int $activeUserId = null;
    public string $search = '';
    public string $filter = 'all';
    public bool $mobileShowChat = false;
    #[Locked]
    public int $editTimeLimit = 600;
    #[Locked]
    public ?array $lastDeleted = null;
    #[Locked]
    public int $messagesLimit = 10;
    public string $messageSearch = '';
    #[Locked]
    public ?int $focusAnchorId = null;
    #[Locked]
    public int $focusOlder = 5;
    public bool $hasOlder = false;

    #[Computed]
    public function activeContact(): ?User
    {
        return $this->activeUserId
            ? User::with(['profile.department'])->find($this->activeUserId)
            : null;
    }

    public function backToList(): void
    {
        $this->mobileShowChat = false;
        $this->activeUserId = null;
        $this->messageSearch = '';
        $this->focusAnchorId = null;
        $this->focusOlder = 5;
        $this->hasOlder = false;
        $this->messagesLimit = 10;
        $this->resetAllStates();
    }

    #[Computed]
    public function contacts(): array
    {
        $users = app(FetchContactsAction::class)->execute(auth()->id(), $this->search, $this->filter);

        $messageIds = $users->pluck('last_message_id')->filter()->unique();
        $lastMessages = Message::withoutTrashed()
            ->whereIn('id', $messageIds)
            ->get()
            ->keyBy('id');

        return $users->map(fn(User $user) => [
            'id' => (int)$user->id,
            'name' => $user->name,
            'profile' => $user->profile?->toArray(),
            'unit' => $user->profile?->detailsMap()->get('unit'),
            'section' => $user->profile?->detailsMap()->get('section'),
            'last_message' => $lastMessages->get($user->last_message_id)?->toArray(),
            'unread_count' => (int)($user->unread_count ?? 0),
            'is_online' => $user->isOnline() ?? false,
            'presence' => $user->presence,
        ])->when($this->filter === 'online', fn($c) => $c->filter(fn($u) => $u['is_online'] ?? false))
            ->values()->all();
    }

    public function deleteMessage(DeleteMessageAction $action, int $deletingId): void
    {
        $snapshot = $action->execute($deletingId, $this->editTimeLimit);
        if (!$snapshot) {
            $this->dispatch('show-toast', message: 'این پیام دیگر قابل حذف نیست.', type: 'error');
            return;
        }

        $this->lastDeleted = $snapshot;
        $this->invalidateMessageCache();
        unset($this->contacts);
        $this->dispatch('show-undo-toast', message: 'پیام حذف شد', type: 'warning');
    }

    public function downloadAttachment(int $messageId, int $index): ?Response
    {
        $message = Message::withoutTrashed()->find($messageId);
        if (!$message) return null;

        $me = auth()->id();
        if (!in_array($me, [$message->sender_id, $message->recipient_id], true)) return null;

        $attachment = ($message->attachments ?? [])[$index] ?? null;
        if (!is_array($attachment) || !isset($attachment['path'], $attachment['name'])) return null;

        $disk = Storage::disk('public');
        $root = realpath($disk->path(''));
        $real = realpath($disk->path($attachment['path']));

        if ($root === false || $real === false || !is_file($real) || !str_starts_with($real, $root . DIRECTORY_SEPARATOR)) return null;

        return tap(response()->download($real), fn($r) => $r->setContentDisposition(
            'attachment',
            $attachment['name'],
            basename($attachment['path'])
        ));
    }

    #[Computed]
    public function messageSearchResults(): array
    {
        if (!$this->activeUserId) {
            return [];
        }

        return app(SearchMessagesAction::class)->execute(
            (int) $this->activeUserId,
            $this->messageSearch,
            (int) auth()->id(),
        );
    }

    public function focusMessage(int $id): void
    {
        if (!$this->activeUserId || $id <= 0) {
            return;
        }

        $overLimit = app(FocusMessageAction::class)->execute(
            (int) $this->activeUserId,
            $id,
            (int) auth()->id(),
            $this->messagesLimit
        );

        if ($overLimit === null) {
            return;
        }

        $this->messageSearch = '';

        if (!$overLimit) {
            if ($this->focusAnchorId !== null) {
                $this->focusAnchorId = null;
                $this->focusOlder = 5;
                $this->invalidateMessageCache();
            }
            $this->dispatch('record-focus', type: 'message', id: $id);
            return;
        }

        $this->focusAnchorId = $id;
        $this->focusOlder = 5;
        $this->invalidateMessageCache();
        $this->dispatch('record-focus', type: 'message', id: $id);
    }

    public function focusRecord(int $userId): void
    {
        if (User::whereKey($userId)->exists()) {
            $this->selectContact($userId, app(MarkMessagesAsReadAction::class));

            $focusMsg = (int) request()->query('focus_msg', 0);
            if ($focusMsg > 0) {
                $this->focusMessage($focusMsg);
            }
        }
    }

    #[Computed]
    public function groupedMessages(): array
    {
        return collect($this->messages)
            ->groupBy(fn($m) => Carbon::parse($m['created_at'])->setTimezone(config('app.timezone'))->toDateString())
            ->map(fn($group) => $group->values()->all())
            ->all();
    }

    #[Computed]
    public function lastMessageId(): ?int
    {
        $id = collect($this->messages)->max('id');
        return $id === null ? null : (int) $id;
    }

    public function loadMoreMessages(): void
    {
        if ($this->focusAnchorId !== null) {
            $this->focusOlder += 10;
        } else {
            $this->messagesLimit += 10;
        }
        $this->invalidateMessageCache();
    }

    #[Computed]
    public function messages(): array
    {
        if (!$this->activeUserId) {
            return [];
        }

        $me = auth()->id();
        $baseQuery = Message::withoutTrashed()
            ->where(fn($q) => $q
                ->where(fn($q) => $q->where('sender_id', $me)->where('recipient_id', $this->activeUserId))
                ->orWhere(fn($q) => $q->where('sender_id', $this->activeUserId)->where('recipient_id', $me))
            );

        if ($this->focusAnchorId !== null) {
            $anchor = (int) $this->focusAnchorId;
            $newer = (clone $baseQuery)->with(['sender.profile', 'replyTo.sender'])
                ->where('id', '>=', $anchor)
                ->oldest('id')
                ->take(6)
                ->get();
            $older = (clone $baseQuery)->with(['sender.profile', 'replyTo.sender'])
                ->where('id', '<', $anchor)
                ->latest('id')
                ->take($this->focusOlder + 1)
                ->get();
            $this->hasOlder = $older->count() > $this->focusOlder;
            $rows = $older->take($this->focusOlder)->merge($newer)->sortBy('id')->values();
        } else {
            $rows = (clone $baseQuery)->with(['sender.profile', 'replyTo.sender'])
                ->latest('id')
                ->take($this->messagesLimit + 1)
                ->get();
            $this->hasOlder = $rows->count() > $this->messagesLimit;
            $rows = $rows->take($this->messagesLimit)->sortBy('id')->values();
        }

        return collect($rows)->map(fn($m) => [
                'id' => (int)$m->id,
                'sender_id' => (int)$m->sender_id,
                'body' => $m->body,
                'attachments' => $m->attachments,
                'is_edited' => (bool)$m->is_edited,
                'read_at' => $m->read_at?->toISOString(),
                'created_at' => $m->created_at->toISOString(),
                'deleted_at' => $m->deleted_at?->toISOString(),
                'reply_to' => $m->replyTo ? [
                    'id' => $m->replyTo->id,
                    'body' => $m->replyTo->body,
                    'sender' => ['name' => $m->replyTo->sender?->name ?? 'ناشناس'],
                ] : null,
                'sender' => ['name' => $m->sender?->name ?? '', 'avatar' => $m->sender?->getProfileImageUrl()],
            ])->values()->all();
    }

    public function removeAttachment(int $index): void
    {
        $attachments = $this->composer->attachments;
        unset($attachments[$index]);
        $this->composer->attachments = array_values($attachments);
    }

    public function render()
    {
        return view('livewire.dashboard.contact', [
            'activeContact' => $this->activeContact,
            'p' => new ContactPresenter(),
        ])->layout('layouts.app');
    }

    public function saveEdit(SaveEditAction $action, int $editingId): void
    {
        if (!$action->execute($this->edit, $editingId, $this->editTimeLimit)) {
            $this->dispatch('show-toast', message: 'مهلت ویرایش این پیام به پایان رسیده است.', type: 'error');
            return;
        }
        $this->edit->reset();
        $this->invalidateMessageCache();
        $this->dispatch('show-toast', message: 'پیام ویرایش شد', type: 'success');
    }

    public function selectContact(int $userId, MarkMessagesAsReadAction $markRead): void
    {
        if (!User::getCachedAllOptions()->has($userId)) return;

        $this->activeUserId = $userId;
        $this->mobileShowChat = true;
        $this->messagesLimit = 10;
        $this->messageSearch = '';
        $this->focusAnchorId = null;
        $this->focusOlder = 5;
        $this->hasOlder = false;
        $this->invalidateMessageCache();
        $this->resetAllStates();

        $markRead->execute($userId, auth()->id());

        unset($this->contacts);
        $this->dispatch('chat-ready');
    }

    public function send(SendMessageAction $action, ?int $replyToId = null): void
    {
        try {
            $action->execute($this->composer, $this->activeUserId, $replyToId);
            $this->composer->reset();
            $this->focusAnchorId = null;
            $this->focusOlder = 5;
            $this->invalidateMessageCache();
            unset($this->contacts);
            $this->dispatch('message-sent');
        } catch (ValidationException $e) {
            $this->dispatch('message-error');
            $this->dispatch('show-toast', message: collect($e->errors())->first()[0] ?? 'خطا در ارسال پیام', type: 'error');
        } catch (\Exception $e) {
            report($e);
            $this->dispatch('message-error');
            $this->dispatch('show-toast', message: 'خطای سیستمی رخ داده است', type: 'error');
        }
    }

    public function setFilter(string $filter): void { $this->filter = $filter; }

    #[Computed]
    public function totalStaff(): int
    {
        return User::active()->count();
    }

    public function undoDelete(UndoDeleteAction $action): void
    {
        if (!$this->lastDeleted) return;
        $action->execute($this->lastDeleted);
        $this->lastDeleted = null;
        $this->invalidateMessageCache();
        unset($this->contacts);
        $this->dispatch('show-toast', message: 'پیام بازیابی شد', type: 'success');
    }

    protected function recordFocusType(): string { return 'people'; }

    private function invalidateMessageCache(): void
    {
        unset($this->messages, $this->lastMessageId);
    }

    private function resetAllStates(): void
    {
        $this->composer->reset();
        $this->edit->reset();
        $this->lastDeleted = null;
    }
}