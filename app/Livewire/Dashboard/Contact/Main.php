<?php

namespace App\Livewire\Dashboard\Contact;

use App\Livewire\Dashboard\Contact\Actions\DeleteMessageAction;
use App\Livewire\Dashboard\Contact\Actions\EnhanceMessageAction;
use App\Livewire\Dashboard\Contact\Actions\FetchArchivedContactsAction;
use App\Livewire\Dashboard\Contact\Actions\FetchContactsAction;
use App\Livewire\Dashboard\Contact\Actions\FocusMessageAction;
use App\Livewire\Dashboard\Contact\Actions\MarkMessagesAsReadAction;
use App\Livewire\Dashboard\Contact\Actions\SaveEditAction;
use App\Livewire\Dashboard\Contact\Actions\SearchMessagesAction;
use App\Livewire\Dashboard\Contact\Actions\SendMessageAction;
use App\Livewire\Dashboard\Contact\Actions\ToggleMessagePinAction;
use App\Livewire\Dashboard\Contact\Actions\ToggleMessageReactionAction;
use App\Livewire\Dashboard\Contact\Actions\UndoDeleteAction;
use App\Livewire\Dashboard\Contact\Forms\EditMessageForm;
use App\Livewire\Dashboard\Contact\Forms\MessageComposerForm;
use App\Livewire\Dashboard\Contact\Presentation\ContactPresenter;
use App\Models\Message;
use App\Models\NotifyLog;
use App\Models\User;
use App\Services\Messaging\PollDigestService;
use App\Services\Template\Words\Sms;
use App\Traits\Livewire\ChatComposer;
use App\Traits\Livewire\FocusOnRecord;
use App\Traits\Livewire\ForwardsMessages;
use App\Traits\Livewire\SendsSms;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Async;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\Js;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Livewire\WithFileUploads;
use Morilog\Jalali\Jalalian;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\View\View;
use Throwable;

#[Isolate]
#[Lazy]
class Main extends Component
{
    use ChatComposer, ForwardsMessages, SendsSms, WithFileUploads;
    use FocusOnRecord {
        clearFocus as baseClearFocus;
    }

    protected function forwardSourceType(): string { return 'contact'; }

    #[Computed]
    public function smsTemplates(): array
    {
        return [
            'birthday' => Sms::for('birthday'),
            'anniversary' => Sms::for('anniversary'),
        ];
    }

    protected function smsContext(int $userId): ?array
    {
        if ($userId === (int) auth()->id() || !in_array($this->smsType, ['birthday', 'anniversary'], true)) {
            return null;
        }

        $profile = User::visibleOnBoard()->with('profile')->find($userId)?->profile;
        if (!$profile || blank($profile->cellphone) || $profile->todaysOccasionType() !== $this->smsType) {
            return null;
        }

        $key = $this->smsType . '-' . $userId . '-' . Jalalian::now()->getYear();

        return $this->smsKey === $key ? ['source' => NotifyLog::SOURCE_CALENDAR, 'key' => $key] : null;
    }

    public MessageComposerForm $composer;
    public EditMessageForm $edit;
    #[Locked]
    public ?int $activeUserId = null;
    public string $search = '';
    public string $filter = 'all';
    #[Locked]
    public int $contactsLimit = 30;
    public bool $hasMoreContacts = false;
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
    #[Locked]
    public int $focusNewer = 5;
    public bool $hasNewer = false;
    #[Locked]
    public ?int $newMessagesAnchorId = null;
    #[Locked]
    public ?string $pollStamp = null;

    private const MESSAGES_STAMP_THRESHOLD = 200;
    private const LOAD_STEP = 25;

    #[Computed]
    public function activeContact(): ?User
    {
        return $this->activeUserId
            ? User::with(['profile.department', 'profile.details'])
                ->addSelect(Message::pinSelects((int) auth()->id(), (int) $this->activeUserId))
                ->find($this->activeUserId)
            : null;
    }

    #[Computed]
    public function activeContactArchived(): bool
    {
        $contact = $this->activeContact;
        return $contact !== null && !$contact->isVisibleOnBoard();
    }

    public function backToList(): void
    {
        $this->mobileShowChat = false;
        $this->activeUserId = null;
        $this->messageSearch = '';
        $this->focusAnchorId = null;
        $this->focusOlder = 5;
        $this->focusNewer = 5;
        $this->hasOlder = false;
        $this->messagesLimit = 10;
        $this->newMessagesAnchorId = null;
        $this->resetAllStates();
    }

    #[Computed]
    public function contacts(): array
    {
        $users = $this->filter === 'archived'
            ? app(FetchArchivedContactsAction::class)->execute(auth()->id(), $this->search, $this->contactsLimit + 1)
            : app(FetchContactsAction::class)->execute(auth()->id(), $this->search, $this->filter, $this->contactsLimit + 1);
        $this->hasMoreContacts = $users->count() > $this->contactsLimit;
        $users = $users->take($this->contactsLimit);

        $messageIds = $users->pluck('last_message_id')->filter()->unique();
        $lastMessages = Message::withoutTrashed()
            ->select(['id', 'sender_id', 'body', 'attachments', 'created_at', 'read_at'])
            ->whereIn('id', $messageIds)
            ->get()
            ->keyBy('id');

        return $users->map(fn(User $user) => [
            'id' => (int)$user->id,
            'name' => $user->name,
            'profile' => $user->profile ? [
                'image' => $user->profile->image,
                'position' => $user->profile->position,
                'cellphone' => $user->profile->cellphone,
            ] : null,
            'display_position' => $user->profile?->display_position,
            'occasion' => $user->profile?->todaysOccasionType(),
            'unit' => ($details = $user->profile?->detailsMap())?->get('unit'),
            'section' => $details?->get('section'),
            'last_message' => ($last = $lastMessages->get($user->last_message_id)) ? [
                'sender_id' => $last->sender_id,
                'body' => $last->body,
                'attachments' => $last->attachments,
                'created_at' => $last->created_at?->toISOString(),
                'read_at' => $last->read_at?->toISOString(),
            ] : null,
            'unread_count' => (int)($user->unread_count ?? 0),
            'is_online' => $user->isOnline() ?? false,
            'presence' => $user->presence,
            'focus' => $user->focusLabel(),
            'inactive' => !$user->isActive(),
        ])->values()->all();
    }

    #[Computed]
    public function totalUnread(): int
    {
        return Message::totalUnreadFor(auth()->id());
    }

    #[Computed]
    public function visibleContactsBase(): \Illuminate\Support\Collection
    {
        return User::visibleOnBoard()
            ->select('id', 'last_seen')
            ->with('profile:id,user_id,birthdate,start_date,employment_status')
            ->get();
    }

    #[Computed]
    public function allContactIds(): array
    {
        return $this->visibleContactsBase->pluck('id')->map(fn($id) => (int) $id)->values()->all();
    }

    #[Computed]
    public function onlineContactsCount(): int
    {
        return $this->visibleContactsBase->filter(fn(User $user) => $user->isOnline())->count();
    }

    #[Computed]
    public function totalOccasions(): int
    {
        return $this->visibleContactsBase->filter(fn(User $user) => $user->profile?->todaysOccasionType() !== null)->count();
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

    public function toggleReaction(ToggleMessageReactionAction $action, int $messageId, string $emoji): void
    {
        if (!$this->activeUserId || $this->activeContactArchived) {
            return;
        }

        if (!$action->execute($messageId, $emoji, (int) auth()->id())) {
            $this->dispatch('show-toast', message: 'این پیام دیگر در دسترس نیست.', type: 'error');
            return;
        }

        $this->invalidateMessageCache();
    }

    public function toggleGroupPin(ToggleMessagePinAction $action, int $messageId, bool $onlyUnpin = false): void
    {
        if (!$this->activeUserId || (!$onlyUnpin && $this->activeContactArchived)) {
            return;
        }

        if (!$action->execute((int) $this->activeUserId, $messageId, (int) auth()->id(), $onlyUnpin)) {
            $this->dispatch('show-toast', message: 'این پیام دیگر در دسترس نیست.', type: 'error');
        }

        unset($this->activeContact);
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

        if ($me === (int) $message->recipient_id && !isset($attachment['views'][(string) $me])) {
            $marked = DB::transaction(function () use ($message, $index, $me): bool {
                $files = Message::withoutTrashed()->lockForUpdate()->find($message->id)?->attachments;

                if (!isset($files[$index]) || isset($files[$index]['views'][(string) $me])) {
                    return false;
                }

                $files[$index]['views'][(string) $me] = now()->toISOString();
                DB::table('messages')->where('id', $message->id)->update(['attachments' => json_encode($files, JSON_UNESCAPED_UNICODE)]);

                return true;
            });

            if ($marked) {
                PollDigestService::bump(Message::pairVersionKey((int) $message->sender_id, $me));
            }
        }

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

    #[Renderless]
    public function days(): array
    {
        if (!$this->activeUserId) {
            return [];
        }

        return app(SearchMessagesAction::class)->days(
            (int) $this->activeUserId,
            (int) auth()->id(),
        );
    }

    #[Renderless]
    public function bookmarks(array $ids): array
    {
        if (!$this->activeUserId) {
            return [];
        }

        return app(SearchMessagesAction::class)->bookmarks(
            (int) $this->activeUserId,
            $ids,
            (int) auth()->id(),
        );
    }

    #[Renderless]
    public function files(): array
    {
        if (!$this->activeUserId) {
            return [];
        }

        return app(SearchMessagesAction::class)->files(
            (int) $this->activeUserId,
            (int) auth()->id(),
        );
    }

    public function focusMessage(int $id): bool
    {
        if (!$this->activeUserId || $id <= 0) {
            return false;
        }

        $overLimit = app(FocusMessageAction::class)->execute(
            (int) $this->activeUserId,
            $id,
            (int) auth()->id(),
            $this->messagesLimit
        );

        if ($overLimit === null) {
            return false;
        }

        $this->messageSearch = '';

        if (!$overLimit) {
            if ($this->focusAnchorId !== null) {
                $this->focusAnchorId = null;
                $this->focusOlder = 5;
                $this->focusNewer = 5;
                $this->invalidateMessageCache();
            }
            $this->dispatch('record-focus', type: 'message', id: $id);
            return true;
        }

        $this->focusAnchorId = $id;
        $this->focusOlder = 5;
        $this->focusNewer = 5;
        $this->invalidateMessageCache();
        $this->dispatch('record-focus', type: 'message', id: $id);
        return true;
    }

    public function focusRecord(int $userId): bool
    {
        if (!User::whereKey($userId)->exists()) {
            return false;
        }

        $this->selectContact($userId);

        $focusMsg = (int) ($this->focusQueryParam('focus_msg') ?? 0);
        if ($focusMsg > 0) {
            return $this->focusMessage($focusMsg);
        }

        return false;
    }

    public function clearFocus(): void
    {
        $this->baseClearFocus();

        if ($this->focusAnchorId === null && $this->messagesLimit === 10) {
            return;
        }

        $this->focusAnchorId = null;
        $this->focusOlder = 5;
        $this->focusNewer = 5;
        $this->messagesLimit = 10;
        $this->invalidateMessageCache();
    }

    public function loadMoreMessages(): void
    {
        if ($this->focusAnchorId !== null) {
            $this->focusOlder += self::LOAD_STEP;
        } else {
            $this->messagesLimit += self::LOAD_STEP;
        }
        $this->invalidateMessageCache();
    }

    public function loadNewerMessages(): void
    {
        if ($this->focusAnchorId === null) {
            return;
        }

        $this->focusNewer += self::LOAD_STEP;
        $this->invalidateMessageCache();
        $rows = $this->messages;

        if (!$this->hasNewer) {
            $this->focusAnchorId = null;
            $this->focusOlder = 5;
            $this->focusNewer = 5;
            $this->messagesLimit = max(10, count($rows));
            $this->markRead((int) $this->activeUserId);
        }
    }

    #[Renderless]
    public function pollDigest(): string
    {
        return PollDigestService::version("dm:sv:" . auth()->id());
    }

    public function refreshUnread(): void
    {
        unset($this->contacts);
    }

    public function refreshActive(): void
    {
        if ($this->messagesLimit > self::MESSAGES_STAMP_THRESHOLD && $this->focusAnchorId === null) {
            $stamp = $this->latestMessageStamp();
            if ($stamp === $this->pollStamp) {
                $this->skipRender();
                return;
            }
            $this->pollStamp = $stamp;
        }

        $this->invalidateMessageCache();
        unset($this->activeContact);
    }

    private function latestMessageStamp(): ?string
    {
        if (!$this->activeUserId) {
            return null;
        }

        $me = auth()->id();
        $peer = $this->activeUserId;

        $direction = fn(int $senderId, int $recipientId) => Message::query()
            ->from(DB::raw('`messages` FORCE INDEX (`idx_sent_covering`)'))
            ->where('sender_id', $senderId)
            ->where('recipient_id', $recipientId);

        $latest = collect([
            $direction($me, $peer)->latest('id')->first(['id', 'updated_at', 'deleted_at']),
            $direction($peer, $me)->latest('id')->first(['id', 'updated_at', 'deleted_at']),
        ])->filter()->sortByDesc('id')->first();

        $typingKey = "typing:dm:{$peer}:{$me}";
        $rvKey = Message::pairVersionKey($me, $peer);
        $cached = Cache::many([$typingKey, $rvKey]);

        $typing = $cached[$typingKey] ? '1' : '0';
        $rv = (string) ($cached[$rvKey] ?? '0');

        return ($latest ? "{$latest->id}:{$latest->updated_at}:{$latest->deleted_at}" : 'none') . ":{$typing}:{$rv}";
    }

    #[Computed]
    public function messages(): array
    {
        if (!$this->activeUserId) {
            return [];
        }

        $me = auth()->id();
        $peer = $this->activeUserId;

        $direction = fn(int $senderId, int $recipientId) => Message::query()
            ->from(DB::raw('`messages` FORCE INDEX (`idx_sent_covering`)'))
            ->where('sender_id', $senderId)
            ->where('recipient_id', $recipientId);

        if ($this->focusAnchorId !== null) {
            $anchor = (int) $this->focusAnchorId;
            $probe = $this->focusNewer + 2;
            $newer = $direction($me, $peer)->where('id', '>=', $anchor)->oldest('id')->take($probe)->get()
                ->merge($direction($peer, $me)->where('id', '>=', $anchor)->oldest('id')->take($probe)->get())
                ->sortBy('id')->take($probe)->values();
            $this->hasNewer = $newer->count() > $this->focusNewer + 1;
            $newer = $newer->take($this->focusNewer + 1);
            $older = $direction($me, $peer)->where('id', '<', $anchor)->latest('id')->take($this->focusOlder + 1)->get()
                ->merge($direction($peer, $me)->where('id', '<', $anchor)->latest('id')->take($this->focusOlder + 1)->get())
                ->sortByDesc('id')->take($this->focusOlder + 1)->values();
            $this->hasOlder = $older->count() > $this->focusOlder;
            $rows = $older->take($this->focusOlder)->merge($newer)->sortBy('id')->values();
        } else {
            $this->hasNewer = false;
            $rows = $direction($me, $peer)->latest('id')->take($this->messagesLimit + 1)->get()
                ->merge($direction($peer, $me)->latest('id')->take($this->messagesLimit + 1)->get())
                ->sortByDesc('id')->take($this->messagesLimit + 1)->values();
            $this->hasOlder = $rows->count() > $this->messagesLimit;
            $rows = $rows->take($this->messagesLimit)->sortBy('id')->values();
        }

        $rows->load(['replyTo:id,sender_id,body', 'replyTo.sender:id,name']);

        $shapeSender = fn($s) => ['name' => $s?->name ?? '', 'avatar' => $s?->getProfileImageUrl() ?? $s?->getInitialsAvatarUrl(), 'online' => $s?->isOnline() ?? false, 'presence' => $s?->presence, 'focus' => $s?->focusLabel(), 'inactive' => $s && !$s->isActive()];
        $senders = collect([auth()->user(), $this->activeContact])->filter()->unique('id')->mapWithKeys(fn($s) => [$s->id => $shapeSender($s)])->all();
        $noSender = $shapeSender(null);

        return collect($rows)->map(fn($m) => [
                'id' => (int)$m->id,
                'sender_id' => (int)$m->sender_id,
                'body' => $m->body,
                'attachments' => $m->attachments,
                'reactions' => $m->reactions ?? [],
                'is_edited' => (bool)$m->is_edited,
                'read_at' => $m->read_at?->toISOString(),
                'created_at' => $m->created_at->toISOString(),
                'deleted_at' => $m->deleted_at?->toISOString(),
                'reply_to' => $m->replyTo ? [
                    'id' => $m->replyTo->id,
                    'body' => $m->replyTo->body,
                    'sender' => ['name' => $m->replyTo->sender?->name ?? 'ناشناس'],
                ] : null,
                'sender' => $senders[$m->sender_id] ?? $noSender,
            ])->values()->all();
    }

    public function replyTo(int $messageId): void
    {
        $this->composer->replyToId = $messageId;
        $this->edit->reset();
    }

    #[Async]
    public function markRead(int $userId): void
    {
        app(MarkMessagesAsReadAction::class)->execute($userId, auth()->id());
    }

    #[Js]
    public function cancelEdit()
    {
        return <<<'JS'
            $wire.edit.editingBody = ''
        JS;
    }

    public function render()
    {
        return view('livewire.dashboard.contact')->layout('layouts.app');
    }

    public function placeholder(): View
    {
        return view('livewire.dashboard.contact.placeholder')->layout('layouts.app');
    }

    public function saveEdit(SaveEditAction $action, int $editingId): bool
    {
        if (!$action->execute($this->edit, $editingId, $this->editTimeLimit)) {
            $this->dispatch('show-toast', message: 'مهلت ویرایش این پیام به پایان رسیده است.', type: 'error');
            return false;
        }
        $this->edit->reset();
        $this->invalidateMessageCache();
        $this->dispatch('show-toast', message: 'پیام ویرایش شد', type: 'success');
        return true;
    }

    #[Renderless]
    #[Async]
    public function enhanceEdit(EnhanceMessageAction $action, int $editingId, string $text): array
    {
        return $action->execute($editingId, (int) auth()->id(), $text, $this->editTimeLimit);
    }

    public function selectContact(int $userId): void
    {
        if (!User::getCachedAllOptions()->has($userId)) return;

        $this->activeUserId = $userId;
        $this->mobileShowChat = true;
        $this->messagesLimit = 10;
        $this->messageSearch = '';
        $this->focusAnchorId = null;
        $this->focusOlder = 5;
        $this->focusNewer = 5;
        $this->hasOlder = false;
        $this->invalidateMessageCache();
        $this->resetAllStates();

        $this->newMessagesAnchorId = $this->presenter->firstUnreadId($this->messages, auth()->id());

        $this->markRead($userId);

        unset($this->contacts);
    }

    #[Renderless]
    public function pingTyping(): void
    {
        if (!$this->activeUserId) {
            return;
        }

        Cache::put("typing:dm:" . auth()->id() . ":{$this->activeUserId}", true, now()->addSeconds(8));
    }

    #[Computed]
    public function peerTyping(): bool
    {
        if (!$this->activeUserId) {
            return false;
        }

        return (bool) Cache::get("typing:dm:{$this->activeUserId}:" . auth()->id());
    }

    public function send(SendMessageAction $action, ?string $body = null, mixed $room = null, ?string $rev = null): ?int
    {
        if (!$this->activeUserId) {
            return null;
        }

        if ($room !== null && (!is_scalar($room) || (int) $room !== $this->activeUserId)) {
            return null;
        }

        if ($body !== null) {
            $this->composer->body = $body;
        }

        if ($rev !== null && !preg_match('/^[A-Za-z0-9-]{1,64}\z/', $rev)) {
            $rev = null;
        }

        $markerKey = $rev !== null ? 'chat-send:' . auth()->id() . ":contact:{$rev}" : null;

        $lock = null;
        $held = false;

        try {
            if ($markerKey) {
                try {
                    $lock = Cache::lock($markerKey . ':lock', 60);
                    $held = $lock->block(5);
                } catch (LockTimeoutException $e) {
                    throw $e;
                } catch (Throwable) {
                    $lock = null;
                }
            }

            try {
                $messageId = $markerKey
                    ? $this->sendOnce($action, $markerKey)
                    : $action->execute($this->composer, $this->activeUserId)->id;
            } finally {
                if ($held) {
                    rescue(fn() => $lock->release());
                }
            }

            Cache::forget("typing:dm:" . auth()->id() . ":{$this->activeUserId}");
            $this->composer->reset();
            $this->focusAnchorId = null;
            $this->focusOlder = 5;
            $this->focusNewer = 5;
            $this->invalidateMessageCache();
            unset($this->contacts);
            $this->dispatch('message-sent', id: $messageId, rev: $rev);
            rescue(fn() => $this->markRead($this->activeUserId));

            return $messageId;
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());
            $this->dispatch('message-error');
            $this->dispatch('show-toast', message: collect($e->errors())->first()[0] ?? 'پیام ارسال نشد. متن و پیوست‌ها را بررسی کنید.', type: 'error');
        } catch (LockTimeoutException) {
            $this->dispatch('message-error');
        } catch (\Exception $e) {
            report($e);
            $this->dispatch('message-error');
            $this->dispatch('show-toast', message: 'هنگام پردازش پیام مشکلی پیش آمد. کمی بعد دوباره تلاش کنید.', type: 'error');
        }

        return null;
    }

    private function sendOnce(SendMessageAction $action, string $markerKey): int
    {
        $fingerprint = sha1(
            trim($this->composer->body) . '|' . ($this->composer->replyToId ?? '') . '|'
            . collect($this->composer->attachments)->map(fn($file) => $file->getFilename())->implode(',')
        );

        $marker = rescue(fn() => Cache::get($markerKey));

        if (is_array($marker) && ($marker['fp'] ?? null) === $fingerprint) {
            $existing = Message::whereKey($marker['id'] ?? 0)
                ->where('sender_id', auth()->id())
                ->where('recipient_id', $this->activeUserId)
                ->value('id');

            if ($existing) {
                return $existing;
            }
        }

        $messageId = $action->execute($this->composer, $this->activeUserId)->id;

        rescue(fn() => Cache::put($markerKey, ['id' => $messageId, 'fp' => $fingerprint], now()->addDay()));

        return $messageId;
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->contactsLimit = 30;
    }

    public function updatedSearch(): void
    {
        $this->contactsLimit = 30;
    }

    public function loadMoreContacts(): void
    {
        $this->contactsLimit += 30;
    }

    #[Computed]
    public function totalStaff(): int
    {
        return count($this->visibleContactsBase);
    }

    #[Computed]
    public function presenter(): ContactPresenter
    {
        return new ContactPresenter();
    }

    public function undoDelete(UndoDeleteAction $action): void
    {
        if (!$this->lastDeleted) return;
        $restored = $action->execute($this->lastDeleted);
        $this->lastDeleted = null;
        $this->invalidateMessageCache();
        unset($this->contacts);
        $this->dispatch('show-toast', message: $restored ? 'پیام بازیابی شد' : 'بازیابی این پیام ممکن نیست', type: $restored ? 'success' : 'error');
    }

    protected function recordFocusType(): string { return 'people'; }

    private function invalidateMessageCache(): void
    {
        unset($this->messages, $this->groupedMessages);
    }

    private function resetAllStates(): void
    {
        $this->composer->reset();
        $this->edit->reset();
        $this->lastDeleted = null;
    }
}
