<?php

namespace App\Livewire\Dashboard\Contact;

use App\Livewire\Dashboard\Contact\Actions\DeleteMessageAction;
use App\Livewire\Dashboard\Contact\Actions\FetchContactsAction;
use App\Livewire\Dashboard\Contact\Actions\MarkMessagesAsReadAction;
use App\Livewire\Dashboard\Contact\Actions\SaveEditAction;
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
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class Main extends Component
{
    use FocusOnRecord, WithFileUploads;

    public MessageComposerForm $composer;
    public EditMessageForm $edit;
    public ?int $activeUserId = null;
    public string $search = '';
    public string $filter = 'all';
    public bool $mobileShowChat = false;
    public ?int $replyingToId = null;
    public ?array $replyingTo = null;
    public ?int $editingId = null;
    public int $editTimeLimit = 300;
    public ?int $deletingId = null;
    public ?array $lastDeleted = null;
    public int $messagesLimit = 10;
    private ?int $_cachedMessageTotal = null;

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
        $this->resetAllStates();
    }

    public function cancelDelete(): void { $this->deletingId = null; }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->edit->reset();
    }

    public function cancelReply(): void
    {
        $this->replyingToId = null;
        $this->replyingTo = null;
    }

    public function confirmDelete(int $messageId): void
    {
        $message = Message::withoutTrashed()->find($messageId);
        if (!$message || $message->sender_id !== auth()->id()) return;

        $this->deletingId = $messageId;
        $this->editingId = null;
        $this->edit->reset();
        $this->replyingToId = null;
        $this->replyingTo = null;
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
            'last_message' => $lastMessages->get($user->last_message_id)?->toArray(),
            'unread_count' => (int)($user->unread_count ?? 0),
            'is_online' => $user->isOnline() ?? false,
            'presence' => $user->presence,
            'last_seen_at' => $user->last_seen?->toISOString(),
        ])->when($this->filter === 'online', fn($c) => $c->filter(fn($u) => $u['is_online'] ?? false))
            ->values()->all();
    }

    public function deleteMessage(DeleteMessageAction $action): void
    {
        if (!$this->deletingId) return;
        $snapshot = $action->execute($this->deletingId);
        if (!$snapshot) {
            $this->cancelDelete();
            return;
        }

        $this->lastDeleted = $snapshot;
        $this->deletingId = null;
        $this->invalidateMessageCache();
        unset($this->contacts);
        $this->dispatch('show-undo-toast', message: 'پیام حذف شد', type: 'warning');
    }

    public function downloadAttachment(int $messageId, int $index)
    {
        $message = Message::withoutTrashed()->find($messageId);
        if (!$message) return;

        $me = auth()->id();
        if (!in_array($me, [$message->sender_id, $message->recipient_id], true)) return;

        $attachment = ($message->attachments ?? [])[$index] ?? null;
        if (!$attachment) return;

        $filePath = Storage::disk('public')->path($attachment['path']);
        if (!file_exists($filePath) || !is_file($filePath)) return;

        return response()->download($filePath, $attachment['name']);
    }

    #[Computed]
    public function filteredContacts(): array
    {
        return $this->contacts;
    }

    public function focusRecord(int $userId): void
    {
        if (User::whereKey($userId)->exists()) {
            $this->selectContact($userId, app(\App\Livewire\Dashboard\Contact\Actions\MarkMessagesAsReadAction::class));
        }
    }

    #[Computed]
    public function groupedMessages(): array
    {
        return collect($this->messages)
            ->groupBy(fn($m) => Carbon::parse($m['created_at'])->toDateString())
            ->map(fn($group) => $group->values()->all())
            ->all();
    }

    public function loadMoreMessages(): void
    {
        $this->messagesLimit += 10;
    }

    #[Computed]
    public function messages(): array
    {
        if (!$this->activeUserId) {
            $this->_cachedMessageTotal = 0;
            return [];
        }

        $me = auth()->id();
        $baseQuery = Message::withoutTrashed()
            ->where(fn($q) => $q
                ->where(fn($q) => $q->where('sender_id', $me)->where('recipient_id', $this->activeUserId))
                ->orWhere(fn($q) => $q->where('sender_id', $this->activeUserId)->where('recipient_id', $me))
            );

        if ($this->_cachedMessageTotal === null) {
            $this->_cachedMessageTotal = (clone $baseQuery)->count();
        }

        return $baseQuery->with(['sender', 'replyTo.sender'])
            ->latest()
            ->take($this->messagesLimit)
            ->get()
            ->sortBy('created_at')
            ->map(fn($m) => [
                'id' => (int)$m->id,
                'sender_id' => (int)$m->sender_id,
                'recipient_id' => (int)$m->recipient_id,
                'body' => $m->body,
                'attachments' => $m->attachments,
                'reply_to_id' => $m->reply_to_id,
                'is_edited' => (bool)$m->is_edited,
                'read_at' => $m->read_at?->toISOString(),
                'created_at' => $m->created_at->toISOString(),
                'updated_at' => $m->updated_at?->toISOString(),
                'deleted_at' => $m->deleted_at?->toISOString(),
                'reply_to' => $m->replyTo ? [
                    'id' => $m->replyTo->id,
                    'body' => $m->replyTo->body,
                    'sender' => ['name' => $m->replyTo->sender?->name ?? 'ناشناس'],
                ] : null,
                'sender' => ['name' => $m->sender?->name ?? ''],
            ])->values()->all();
    }

    public function mount(): void { }

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

    public function saveEdit(SaveEditAction $action): void
    {
        if (!$this->editingId) return;
        if (!$action->execute($this->edit, $this->editingId, $this->editTimeLimit)) {
            $this->cancelEdit();
            return;
        }
        $this->cancelEdit();
        $this->invalidateMessageCache();
        $this->dispatch('show-toast', message: 'پیام ویرایش شد', type: 'success');
    }

    public function selectContact(int $userId, MarkMessagesAsReadAction $markRead): void
    {
        if (!User::getCachedAllOptions()->has($userId)) return;

        $this->activeUserId = $userId;
        $this->mobileShowChat = true;
        $this->messagesLimit = 10;
        $this->invalidateMessageCache();
        $this->resetAllStates();

        $markRead->execute($userId, auth()->id());

        unset($this->contacts);
        $this->dispatch('chat-ready');
    }

    public function send(SendMessageAction $action): void
    {
        try {
            $action->execute($this->composer, $this->activeUserId, $this->replyingToId);
            $this->composer->reset();
            $this->replyingToId = null;
            $this->replyingTo = null;
            $this->invalidateMessageCache();
            unset($this->contacts);
            $this->dispatch('message-sent');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('message-error');
            $this->dispatch('show-toast', message: collect($e->errors())->first()[0] ?? 'خطا در ارسال پیام', type: 'error');
        } catch (\Exception $e) {
            $this->dispatch('message-error');
            $this->dispatch('show-toast', message: 'خطای سیستمی رخ داده است', type: 'error');
        }
    }

    public function setFilter(string $filter): void { $this->filter = $filter; }

    public function startEdit(int $messageId): void
    {
        $message = Message::withoutTrashed()->find($messageId);
        if (!$message || $message->sender_id !== auth()->id()) return;
        if ($message->created_at->diffInSeconds(now()) > $this->editTimeLimit) return;

        $this->editingId = $messageId;
        $this->edit->editingBody = $message->body;
        $this->deletingId = null;
        $this->replyingToId = null;
        $this->replyingTo = null;
        $this->dispatch('edit-mode-entered');
    }

    public function startReply(int $messageId): void
    {
        $message = Message::withoutTrashed()->find($messageId);
        if (!$message) return;

        $me = auth()->id();
        $isValidContext = ($message->sender_id == $me && $message->recipient_id == $this->activeUserId) ||
            ($message->sender_id == $this->activeUserId && $message->recipient_id == $me);
        if (!$isValidContext) return;

        $this->replyingToId = $messageId;
        $this->replyingTo = [
            'id' => $message->id, 'body' => $message->body,
            'sender' => ['name' => $message->sender?->name ?? 'ناشناس'],
        ];
        $this->editingId = null;
        $this->edit->reset();
        $this->deletingId = null;
    }

    #[Computed]
    public function totalMessages(): int
    {
        if (!$this->activeUserId) return 0;
        if ($this->_cachedMessageTotal !== null) return $this->_cachedMessageTotal;

        $me = auth()->id();
        $count = Message::withoutTrashed()
            ->where(fn($q) => $q
                ->where(fn($q) => $q->where('sender_id', $me)->where('recipient_id', $this->activeUserId))
                ->orWhere(fn($q) => $q->where('sender_id', $this->activeUserId)->where('recipient_id', $me))
            )->count();

        $this->_cachedMessageTotal = $count;

        return $count;
    }

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
        $this->_cachedMessageTotal = null;
        unset($this->messages);
    }

    private function resetAllStates(): void
    {
        $this->composer->reset();
        $this->edit->reset();
        $this->editingId = null;
        $this->deletingId = null;
        $this->lastDeleted = null;
        $this->replyingToId = null;
        $this->replyingTo = null;
    }
}
