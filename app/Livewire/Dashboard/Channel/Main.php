<?php

namespace App\Livewire\Dashboard\Channel;

use App\Livewire\Dashboard\Channel\Actions\ChangeChannelTypeAction;
use App\Livewire\Dashboard\Channel\Actions\CreateChannelAction;
use App\Livewire\Dashboard\Channel\Actions\DeleteChannelAction;
use App\Livewire\Dashboard\Channel\Actions\DeleteChannelMessageAction;
use App\Livewire\Dashboard\Channel\Actions\DownloadChannelAttachmentAction;
use App\Livewire\Dashboard\Channel\Actions\EnhanceChannelMessageAction;
use App\Livewire\Dashboard\Channel\Actions\FetchChannelsAction;
use App\Livewire\Dashboard\Channel\Actions\FetchJoinableChannelsAction;
use App\Livewire\Dashboard\Channel\Actions\FindFirstUnreadChannelMessageAction;
use App\Livewire\Dashboard\Channel\Actions\FocusChannelMessageAction;
use App\Livewire\Dashboard\Channel\Actions\JoinChannelAction;
use App\Livewire\Dashboard\Channel\Actions\LeaveChannelAction;
use App\Livewire\Dashboard\Channel\Actions\MarkChannelReadAction;
use App\Livewire\Dashboard\Channel\Actions\SaveEditChannelMessageAction;
use App\Livewire\Dashboard\Channel\Actions\SearchChannelMessagesAction;
use App\Livewire\Dashboard\Channel\Actions\SendChannelMessageAction;
use App\Livewire\Dashboard\Channel\Actions\SetChannelKeyAction;
use App\Livewire\Dashboard\Channel\Actions\SummarizeChannelAction;
use App\Livewire\Dashboard\Channel\Actions\SyncChannelAdminsAction;
use App\Livewire\Dashboard\Channel\Actions\SyncChannelMembersAction;
use App\Livewire\Dashboard\Channel\Actions\ToggleChannelMessageReactionAction;
use App\Livewire\Dashboard\Channel\Actions\ToggleChannelPinAction;
use App\Livewire\Dashboard\Channel\Actions\UndoDeleteChannelMessageAction;
use App\Livewire\Dashboard\Channel\Forms\ChannelMessageComposerForm;
use App\Livewire\Dashboard\Channel\Forms\CreateChannelForm;
use App\Livewire\Dashboard\Channel\Forms\EditChannelMessageForm;
use App\Livewire\Dashboard\Channel\Presentation\ChannelPresenter;
use App\Enums\ChannelRole;
use App\Enums\ChannelType;
use App\Models\Channel;
use App\Models\ChannelMessage;
use App\Models\Department;
use App\Models\User;
use App\Services\Messaging\PollDigestService;
use App\Support\ChannelAccessPolicy;
use App\Traits\Livewire\BulkSelectsPeople;
use App\Traits\Livewire\ChatComposer;
use App\Traits\Livewire\FocusOnRecord;
use App\Traits\Livewire\ForwardsMessages;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

#[Isolate]
#[Lazy]
class Main extends Component
{
    use BulkSelectsPeople;
    use ChatComposer, ForwardsMessages, WithFileUploads;
    use FocusOnRecord {
        clearFocus as baseClearFocus;
    }

    protected function forwardSourceType(): string { return 'channel'; }

    public ChannelMessageComposerForm $composer;
    public EditChannelMessageForm $edit;
    public CreateChannelForm $create;

    #[Locked]
    public ?int $activeChannelId = null;
    #[Locked]
    public ?int $newMessagesAnchorId = null;
    #[Locked]
    public int $anchorCapturedAt = 0;

    public string $search = '';
    public string $messageSearch = '';
    public string $filter = 'all';
    #[Locked]
    public int $channelsLimit = 30;
    public bool $hasMoreChannels = false;
    public bool $browseMode = false;
    public bool $createMode = false;
    public bool $mobileShowChat = false;
    #[Locked]
    public int $editTimeLimit = 600;
    public ?array $editingMsg = null;
    #[Locked]
    public ?array $lastDeleted = null;
    #[Locked]
    public int $loadedLimit = 10;
    public bool $hasOlder = false;
    #[Locked]
    public ?string $pollStamp = null;

    private const MESSAGES_STAMP_THRESHOLD = 200;
    private const LOAD_STEP = 25;

    #[Locked]
    public ?int $focusAnchorId = null;
    #[Locked]
    public int $focusOlder = 5;
    #[Locked]
    public int $focusNewer = 5;
    public bool $hasNewer = false;

    public bool $isManageMembersOpen = false;
    public array $memberRecipientIds = [];
    public array $writerIds = [];
    public string $memberTab = 'members';
    public array $createRecipientIds = [];
    public bool $isManageAdminsOpen = false;
    public array $adminRecipientIds = [];

    public array $mentionMemberNames = [];

    public function mount(): void
    {
        $this->composer->fill(['body' => '', 'attachments' => [], 'replyToId' => null]);
        $this->edit->fill(['editingBody' => '']);
        $this->create->fill(['name' => '', 'slug' => '', 'description' => null, 'type' => 'open']);
    }

    #[Computed]
    public function activeChannel(): ?Channel
    {
        return $this->activeChannelId
            ? Channel::with('owner')->withCount('members')->withPinnedPreview()->withLastMessageAt()->withViewerRole((int) auth()->id())
                ->whereHas('memberUsers', fn($q) => $q->where('users.id', auth()->id()))
                ->find($this->activeChannelId)
            : null;
    }

    #[Computed]
    public function channels(): array
    {
        $rows = app(FetchChannelsAction::class)->execute(auth()->id(), $this->search, $this->filter, $this->channelsLimit + 1, $this->allChannelIds);
        $this->hasMoreChannels = $rows->count() > $this->channelsLimit;
        $rows = $rows->take($this->channelsLimit);

        $messageIds = $rows->pluck('last_message_id')->filter()->unique();
        $lastMessages = ChannelMessage::withoutTrashed()
            ->select(['id', 'sender_id', 'body', 'attachments', 'created_at'])
            ->with('sender:id,name')
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
                'is_entered' => !empty($ch->entered_at),
                'members_count' => (int)($ch->members_count ?? 0),
                'unread_count' => (int)($ch->unread_count ?? 0),
                'last_message' => $last ? [
                    'body' => $last->body,
                    'sender_id' => $last->sender_id,
                    'sender_name' => $last->sender?->name,
                    'attachments' => $last->attachments,
                    'created_at' => $last->created_at->toISOString(),
                ] : null,
            ];
        })->values()->all();
    }

    #[Computed]
    public function totalUnread(): int
    {
        return ChannelMessage::totalUnreadFor((int) auth()->id());
    }

    #[Computed]
    public function myChannelMemberships(): Collection
    {
        return DB::table('channel_members')
            ->join('channels', 'channels.id', '=', 'channel_members.channel_id')
            ->whereNull('channels.deleted_at')
            ->where('channel_members.user_id', auth()->id())
            ->select('channel_members.channel_id', 'channels.type')
            ->get();
    }

    #[Computed]
    public function allChannelIds(): array
    {
        return $this->myChannelMemberships->pluck('channel_id')->map(fn($id) => (int) $id)->values()->all();
    }

    #[Computed]
    public function myChannelsSummary(): array
    {
        $types = $this->myChannelMemberships->pluck('type');
        $total = $types->count();
        $open = $types->filter(fn($type) => $type === 'open')->count();

        return ['total' => $total, 'open' => $open, 'private' => $total - $open];
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
    public function abilities(): array
    {
        return ChannelAccessPolicy::abilities($this->activeChannel, auth()->user());
    }

    #[Computed]
    public function pendingInvitees(): array
    {
        $channel = $this->activeChannel;
        if (!$channel || !ChannelAccessPolicy::canManageMembers($channel, auth()->user())) {
            return [];
        }

        $ids = $this->activeChannelMemberUsers->filter(fn(User $user) => $user->pivot->entered_at === null)->pluck('id')->all();
        if (!$ids) {
            return [];
        }

        return User::query()
            ->whereIn('users.id', $ids)
            ->leftJoin('edges', fn($j) => $j->on('edges.user_id', '=', 'users.id')
                ->where('edges.edge_key', 'channels-controller:nudge')
                ->where('edges.subject_type', Channel::class)
                ->where('edges.subject_id', (int) $channel->id)
                ->whereNotNull('edges.dismissed_at'))
            ->select('users.id', 'users.name')
            ->selectRaw('(edges.user_id IS NULL) AS cancellable')
            ->orderBy('users.name')
            ->get()
            ->map(fn(User $u) => [
                'id' => (int) $u->id,
                'name' => $u->name,
                'cancellable' => (bool) $u->cancellable,
            ])
            ->all();
    }

    public function cancelInvite(LeaveChannelAction $action, int $channelId, int $userId): void
    {
        if ($channelId !== $this->activeChannelId
            || !in_array($userId, array_column($this->pendingInvitees, 'id'))) {
            return;
        }

        if (!$action->execute($channelId, $userId)) {
            return;
        }

        unset($this->pendingInvitees, $this->activeChannel, $this->activeChannelMemberUsers, $this->channelMembers, $this->readers, $this->channelMembersForMentions, $this->mentionMemberMap, $this->mentionMemberPresence);
        $this->dispatch('show-toast', message: 'دعوت لغو شد', type: 'info');
    }

    #[Computed]
    public function deptOptions(): array
    {
        return Department::getCachedOptions()->toArray();
    }

    #[Computed]
    public function memberCandidates(): array
    {
        $depts = User::getCachedActiveDeptMap();

        return User::getCachedActiveOptions()
            ->except(array_merge([auth()->id()], $this->lockedMemberIds($this->activeChannel)))
            ->map(fn($name, $id) => ['id' => (int)$id, 'name' => $name, 'dept' => $depts[(int)$id] ?? ''])
            ->values()
            ->all();
    }

    #[Computed]
    public function writerCandidates(): array
    {
        if (!$this->isManageMembersOpen || $this->activeChannel?->isProjectLinked()) {
            return [];
        }

        $skip = array_merge([(int) auth()->id()], $this->lockedMemberIds($this->activeChannel));
        $depts = User::getCachedActiveDeptMap();

        return $this->activeChannelMemberUsers
            ->filter(fn(User $user) => !in_array((int) $user->id, $skip, true) && $user->isActive() && $user->pivot->role !== ChannelRole::Admin->value)
            ->sortBy('name')
            ->map(fn(User $user) => ['id' => (int) $user->id, 'name' => $user->name, 'dept' => $depts[(int) $user->id] ?? ''])
            ->values()
            ->all();
    }

    protected function bulkSelectTargets(): array
    {
        return ['memberRecipientIds', 'adminRecipientIds', 'createRecipientIds'];
    }

    protected function canBulkSelect(string $property): bool
    {
        $channel = $this->activeChannel;
        $user = auth()->user();

        return match ($property) {
            'createRecipientIds' => $this->createMode,
            'memberRecipientIds' => $this->isManageMembersOpen && $channel && ChannelAccessPolicy::canManageMembers($channel, $user),
            'adminRecipientIds' => $this->isManageAdminsOpen && $channel && ChannelAccessPolicy::canManageAdmins($channel, $user),
            default => false,
        };
    }

    protected function bulkPeoplePool(string $property, ?string $dept = null): array
    {
        if ($property === 'adminRecipientIds') {
            $ownerId = (int) $this->activeChannel?->owner_id;
            $depts = User::getCachedActiveDeptMap();

            return $this->activeChannelMemberUsers
                ->filter(fn (User $user) => (int) $user->id !== $ownerId && $user->isActive() && $user->pivot->entered_at !== null)
                ->when($dept !== null, fn ($users) => $users->filter(fn (User $user) => ($depts[(int) $user->id] ?? '') === $dept))
                ->map(fn (User $user) => (int) $user->id)
                ->values()->all();
        }

        return array_values(array_diff($this->bulkDefaultPeoplePool($dept), $this->lockedMemberIds($this->activeChannel)));
    }

    #[Computed]
    public function messages(): array
    {
        if (!$this->activeChannelId) {
            $this->hasOlder = false;
            $this->hasNewer = false;
            return [];
        }

        if ($this->focusAnchorId !== null) {
            $anchor = (int) $this->focusAnchorId;
            $newer = ChannelMessage::withoutTrashed()
                ->where('channel_id', $this->activeChannelId)
                ->where('id', '>=', $anchor)
                ->oldest('id')
                ->take($this->focusNewer + 2)
                ->get();
            $this->hasNewer = $newer->count() > $this->focusNewer + 1;
            $newer = $newer->take($this->focusNewer + 1);
            $older = ChannelMessage::withoutTrashed()
                ->where('channel_id', $this->activeChannelId)
                ->where('id', '<', $anchor)
                ->latest('id')
                ->take($this->focusOlder + 1)
                ->get();
            $this->hasOlder = $older->count() > $this->focusOlder;
            $rows = $older->take($this->focusOlder)->merge($newer)->sortBy('id')->values();
        } else {
            $this->hasNewer = false;
            $rows = ChannelMessage::withoutTrashed()
                ->where('channel_id', $this->activeChannelId)
                ->latest('id')
                ->take($this->loadedLimit + 1)
                ->get();
            $this->hasOlder = $rows->count() > $this->loadedLimit;
            $rows = $rows->take($this->loadedLimit)->sortBy('id')->values();
        }

        $rows->load(['sender.profile', 'replyTo:id,sender_id,body', 'replyTo.sender:id,name']);

        $shapeSender = fn($s) => ['name' => $s?->name ?? 'ناشناس', 'avatar' => $s?->getProfileImageUrl() ?? $s?->getInitialsAvatarUrl(), 'online' => $s?->isOnline() ?? false, 'presence' => $s?->presence, 'focus' => $s?->focusLabel(), 'inactive' => $s && !$s->isActive()];
        $senders = $rows->pluck('sender')->filter()->unique('id')->mapWithKeys(fn($s) => [$s->id => $shapeSender($s)])->all();
        $noSender = $shapeSender(null);

        return $rows->map(fn($m) => [
            'id' => (int)$m->id,
            'sender_id' => (int)$m->sender_id,
            'body' => $m->body,
            'attachments' => $m->attachments,
            'reactions' => $m->reactions ?? [],
            'reply_to_id' => $m->reply_to_id,
            'is_edited' => (bool)$m->is_edited,
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

    #[Computed]
    protected function activeChannelMemberUsers(): Collection
    {
        if (!$this->activeChannel) {
            return collect();
        }

        return $this->activeChannel->memberUsers()
            ->with('profile')
            ->get();
    }

    #[Computed]
    public function readers(): array
    {
        if (!$this->activeChannelId) {
            return [];
        }

        $map = [];
        foreach ($this->activeChannelMemberUsers as $user) {
            $map[(int) $user->id] = [
                'cursor'  => (int) ($user->pivot->last_read_message_id ?? 0),
                'name'    => $user->name ?? '—',
                'avatar'  => $user->getProfileImageUrl() ?? $user->getInitialsAvatarUrl(),
                'read_at' => $user->pivot->updated_at?->toISOString(),
                'inactive' => !$user->isActive(),
            ];
        }

        return $map;
    }

    #[Computed]
    protected function channelMembersForMentions(): Collection
    {
        return $this->activeChannelMemberUsers->sortBy('name')->values();
    }

    #[Computed]
    public function channelMembers(): array
    {
        if (!$this->activeChannel) {
            return [];
        }

        $ownerId = $this->activeChannel->owner_id;

        return $this->activeChannelMemberUsers
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar_url' => $user->getProfileImageUrl() ?? $user->getInitialsAvatarUrl(),
                'is_owner' => $ownerId !== null && $user->id === $ownerId,
                'is_admin' => $user->pivot->role === ChannelRole::Admin->value,
                'presence_label' => $user->presence?->label(),
                'presence_class' => $user->presence?->activeClass(),
                'presence' => $user->presence,
                'focus' => $user->focusLabel(),
                'is_online' => $user->isOnline(),
                'last_seen_label' => $user->isActive() && !$user->isOnline() ? $user->lastSeenLabel() : null,
                'inactive' => !$user->isActive(),
            ])
            ->sortByDesc(fn(array $m) => $m['is_owner'] ? 2 : (int) $m['is_admin'])
            ->values()
            ->all();
    }

    #[Computed]
    public function mentionMemberMap(): array
    {
        $map = [];
        foreach ($this->channelMembersForMentions as $user) {
            $map[$user->name][] = (int) $user->id;
        }

        return $map;
    }

    #[Computed]
    public function mentionMemberPresence(): array
    {
        $out = [];
        foreach ($this->channelMembersForMentions as $user) {
            if (!$user->isActive()) {
                continue;
            }
            $out[$user->name] = [
                'presence_label' => $user->presence?->label(),
                'presence_class' => $user->presence?->activeClass(),
                'reachable' => $user->presence?->isReachable() ?? true,
            ];
        }

        return $out;
    }

    public function loadMentionMemberNames(): void
    {
        $this->mentionMemberNames = array_keys($this->mentionMemberPresence);
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

    #[Renderless]
    public function days(): array
    {
        if (!$this->activeChannelId) {
            return [];
        }

        return app(SearchChannelMessagesAction::class)->days(
            (int) $this->activeChannelId,
            (int) auth()->id(),
        );
    }

    #[Renderless]
    public function bookmarks(array $ids): array
    {
        if (!$this->activeChannelId) {
            return [];
        }

        return app(SearchChannelMessagesAction::class)->bookmarks(
            (int) $this->activeChannelId,
            $ids,
            (int) auth()->id(),
        );
    }

    #[Renderless]
    public function files(): array
    {
        if (!$this->activeChannelId) {
            return [];
        }

        return app(SearchChannelMessagesAction::class)->files(
            (int) $this->activeChannelId,
            (int) auth()->id(),
        );
    }

    public function selectChannel(int $channelId): void
    {
        if (!Channel::withoutTrashed()
            ->whereKey($channelId)
            ->whereHas('memberUsers', fn($memberQ) => $memberQ->where('users.id', auth()->id()))
            ->exists()) {
            return;
        }

        $now = now()->timestamp;
        if ($this->activeChannelId !== $channelId || $now - $this->anchorCapturedAt >= 3) {
            $this->anchorCapturedAt = $now;
            $this->newMessagesAnchorId = app(FindFirstUnreadChannelMessageAction::class)->execute($channelId, (int) auth()->id());
        }

        $this->activeChannelId = $channelId;
        $this->mobileShowChat = true;
        $this->browseMode = false;
        $this->createMode = false;
        $this->loadedLimit = 10;
        $this->hasOlder = false;
        $this->focusAnchorId = null;
        $this->focusOlder = 5;
        $this->focusNewer = 5;
        $this->editingMsg = null;
        $this->lastDeleted = null;
        $this->messageSearch = '';
        $this->composer->reset();
        $this->edit->reset();

        $this->markRead($channelId);

        unset($this->channels, $this->messages, $this->activeChannel, $this->activeChannelMemberUsers, $this->readers, $this->channelMembersForMentions, $this->channelMembers, $this->mentionMemberMap, $this->mentionMemberPresence, $this->pendingInvitees);
    }

    public function focusRecord(int $channelId): bool
    {
        $this->selectChannel($channelId);

        if ($this->activeChannelId !== $channelId) {
            return false;
        }

        $focusMsg = (int) ($this->focusQueryParam('focus_msg') ?? 0);
        if ($focusMsg > 0) {
            return $this->focusMessage($focusMsg);
        }

        return false;
    }

    public function focusMessage(int $id): bool
    {
        if (!$this->activeChannelId || $id <= 0) {
            return false;
        }

        $overLimit = app(FocusChannelMessageAction::class)->execute(
            (int) $this->activeChannelId,
            $id,
            (int) auth()->id(),
            $this->loadedLimit
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
                unset($this->messages, $this->groupedMessages);
            }
            $this->dispatch('record-focus', type: 'channel-message', id: $id);
            return true;
        }

        $this->focusAnchorId = $id;
        $this->focusOlder = 5;
        $this->focusNewer = 5;
        unset($this->messages, $this->groupedMessages);
        $this->dispatch('record-focus', type: 'channel-message', id: $id);
        return true;
    }

    public function focusSender(string $name): bool
    {
        if (!$this->activeChannelId) {
            return false;
        }

        $ids = $this->mentionMemberMap[$name] ?? [];
        if (!$ids) {
            return false;
        }

        $id = ChannelMessage::withoutTrashed()
            ->where('channel_id', $this->activeChannelId)
            ->whereIn('sender_id', $ids)
            ->latest('id')
            ->value('id');

        return $id ? $this->focusMessage((int) $id) : false;
    }

    public function clearFocus(): void
    {
        $this->baseClearFocus();

        if ($this->focusAnchorId === null && $this->loadedLimit === 10) {
            return;
        }
        $this->focusAnchorId = null;
        $this->focusOlder = 5;
        $this->focusNewer = 5;
        $this->loadedLimit = 10;
        unset($this->messages, $this->groupedMessages);
    }

    #[Renderless]
    public function pollDigest(): string
    {
        $ids = $this->allChannelIds;
        sort($ids);
        $versions = implode(',', PollDigestService::versions(array_map(fn($id) => "ch:tv:{$id}", $ids)));

        return crc32(implode(',', $ids)) . ':' . crc32($versions) . ':' . PollDigestService::version('ch:me:' . auth()->id());
    }

    public function refreshUnread(): void
    {
        unset($this->channels);
    }

    public function refreshActive(): void
    {
        if ($this->loadedLimit > self::MESSAGES_STAMP_THRESHOLD && $this->focusAnchorId === null) {
            $stamp = $this->latestMessageStamp();
            if ($stamp === $this->pollStamp) {
                $this->skipRender();
                return;
            }
            $this->pollStamp = $stamp;
        }

        unset($this->messages, $this->groupedMessages);
    }

    private function latestMessageStamp(): ?string
    {
        if (!$this->activeChannelId) {
            return null;
        }

        $latest = ChannelMessage::where('channel_id', $this->activeChannelId)
            ->latest('id')
            ->first(['id', 'updated_at', 'deleted_at']);

        $msg = $latest ? "{$latest->id}:{$latest->updated_at}:{$latest->deleted_at}" : 'none';
        $typing = implode(',', $this->typingMembers);
        $readVersion = PollDigestService::version("ch:rv:{$this->activeChannelId}");

        return "{$msg}:{$typing}:{$readVersion}";
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->channelsLimit = 30;
        unset($this->channels);
    }

    public function updatedSearch(): void
    {
        $this->channelsLimit = 30;
    }

    public function loadMoreChannels(): void
    {
        $this->channelsLimit += 30;
    }

    public function toggleBrowse(): void
    {
        $this->browseMode = !$this->browseMode;
        $this->createMode = false;
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
        $this->createRecipientIds = [];
        unset($this->memberCandidates);
    }

    public function closeCreate(): void
    {
        $this->createMode = false;
        $this->mobileShowChat = false;
    }

    public function createChannel(SyncChannelMembersAction $sync): void
    {
        try {
            $channel = DB::transaction(function () use ($sync) {
                $channel = app(CreateChannelAction::class)->execute($this->create);

                if (!empty($this->createRecipientIds)) {
                    $sync->execute((int) $channel->id, (int) auth()->id(), $this->createRecipientIds);
                }

                return $channel;
            });

            $this->createMode = false;
            $this->createRecipientIds = [];
            unset($this->channels, $this->joinableChannels, $this->activeChannel, $this->memberCandidates);
            $this->selectChannel($channel->id);
            $this->dispatch('show-toast', message: 'گروه ایجاد شد', type: 'success');
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());
            $this->dispatch('show-toast', message: collect($e->errors())->first()[0] ?? 'خطا در ایجاد گروه', type: 'error');
        } catch (\Exception $e) {
            report($e);
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
        $this->focusNewer = 5;
        $this->editingMsg = null;
        $this->messageSearch = '';
        $this->composer->reset();
        $this->edit->reset();
        unset($this->channels, $this->messages, $this->activeChannel, $this->joinableChannels, $this->activeChannelMemberUsers, $this->readers, $this->channelMembers, $this->pendingInvitees);
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

        $this->resetErrorBag('edit.editingBody');
        $this->editingMsg = ['id' => $messageId, 'body' => $msg->body];
        $this->edit->editingBody = $msg->body;
        $this->composer->replyToId = null;
    }

    public function loadOlder(): void
    {
        if ($this->focusAnchorId !== null) {
            $this->focusOlder += self::LOAD_STEP;
        } else {
            $this->loadedLimit += self::LOAD_STEP;
        }
        unset($this->messages);
    }

    public function loadNewer(): void
    {
        if ($this->focusAnchorId === null) {
            return;
        }

        $this->focusNewer += self::LOAD_STEP;
        unset($this->messages);
        $rows = $this->messages;

        if (!$this->hasNewer) {
            $this->focusAnchorId = null;
            $this->focusOlder = 5;
            $this->focusNewer = 5;
            $this->loadedLimit = max(10, count($rows));
            $this->markRead((int) $this->activeChannelId);
        }
    }

    #[Renderless]
    public function pingTyping(): void
    {
        if (!$this->activeChannelId) {
            return;
        }

        $key = "typing:ch:{$this->activeChannelId}";
        $now = now()->timestamp;
        $map = collect(Cache::get($key, []))->filter(fn($entry) => $entry[1] > $now)->all();
        $map[auth()->id()] = [auth()->user()->name, $now + 8];
        Cache::put($key, $map, now()->addSeconds(60));
    }

    #[Computed]
    public function typingMembers(): array
    {
        if (!$this->activeChannelId) {
            return [];
        }

        $now = now()->timestamp;
        $myId = auth()->id();

        return collect(Cache::get("typing:ch:{$this->activeChannelId}", []))
            ->filter(fn($entry, $userId) => $entry[1] > $now && (int) $userId !== $myId)
            ->map(fn($entry) => $entry[0])
            ->values()
            ->all();
    }

    private function clearOwnTyping(): void
    {
        if (!$this->activeChannelId) {
            return;
        }

        $key = "typing:ch:{$this->activeChannelId}";
        $map = Cache::get($key, []);
        unset($map[auth()->id()]);
        Cache::put($key, $map, now()->addSeconds(60));
    }

    public function send(SendChannelMessageAction $action): void
    {
        if (!$this->activeChannelId) {
            return;
        }

        try {
            $action->execute($this->composer, $this->activeChannelId);
            $this->clearOwnTyping();
            $this->composer->reset();
            $this->editingMsg = null;
            $this->focusAnchorId = null;
            $this->focusOlder = 5;
            $this->focusNewer = 5;
            unset($this->messages, $this->channels);
            $this->dispatch('message-sent');
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());
            $this->dispatch('show-toast', message: collect($e->errors())->first()[0] ?? 'خطا در ارسال پیام', type: 'error');
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            report($e);
            $this->dispatch('show-toast', message: 'خطای سیستمی رخ داده است', type: 'error');
        }
    }

    #[Renderless]
    public function saveChannelKey(SetChannelKeyAction $action, int $channelId, ?string $code = null): void
    {
        try {
            $isSet = $action->execute($channelId, auth()->id(), $code);
            $this->dispatch('show-toast', message: $isSet ? 'کلید گروه ثبت شد' : 'کلید گروه حذف شد', type: 'success');
        } catch (ValidationException $e) {
            $this->dispatch('show-toast', message: collect($e->errors())->first()[0] ?? 'کلید نامعتبر است', type: 'error');
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
        $snapshot = $action->execute($deletingId, $this->editTimeLimit);
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
        $restored = $action->execute($this->lastDeleted);
        $this->lastDeleted = null;
        unset($this->messages);
        $this->dispatch('show-toast', message: $restored ? 'پیام بازیابی شد' : 'بازیابی این پیام ممکن نیست', type: $restored ? 'success' : 'error');
    }

    public function toggleReaction(ToggleChannelMessageReactionAction $action, int $messageId, string $emoji): void
    {
        if (!$action->execute($messageId, $emoji, (int) auth()->id())) {
            $this->dispatch('show-toast', message: 'این پیام دیگر در دسترس نیست.', type: 'error');
        }

        unset($this->messages);
    }

    public function toggleGroupPin(ToggleChannelPinAction $action, int $messageId, bool $onlyUnpin = false): void
    {
        if (!$this->activeChannelId) {
            return;
        }

        if (!$action->execute($this->activeChannelId, $messageId, (int) auth()->id(), $onlyUnpin)) {
            $this->dispatch('show-toast', message: 'این پیام دیگر در دسترس نیست.', type: 'error');
        }

        unset($this->activeChannel);
    }

    public function joinChannel(JoinChannelAction $action, int $channelId): void
    {
        if (!$action->execute($channelId, auth()->id())) {
            unset($this->channels, $this->joinableChannels);
            $this->dispatch('show-toast', message: 'این گروه دیگر در دسترس نیست.', type: 'error');
            return;
        }
        $this->browseMode = false;
        unset($this->channels, $this->joinableChannels);
        $this->selectChannel($channelId);
    }

    public function leaveChannel(LeaveChannelAction $action, int $channelId): void
    {
        if (!$action->execute($channelId, auth()->id())) {
            return;
        }
        if ($this->activeChannelId === $channelId) {
            $this->activeChannelId = null;
            $this->mobileShowChat = false;
            $this->loadedLimit = 10;
            $this->hasOlder = false;
            $this->focusAnchorId = null;
            $this->focusOlder = 5;
            $this->focusNewer = 5;
            $this->messageSearch = '';
        }
        unset($this->channels, $this->joinableChannels, $this->messages, $this->activeChannel, $this->activeChannelMemberUsers, $this->readers, $this->channelMembersForMentions, $this->channelMembers, $this->mentionMemberMap, $this->mentionMemberPresence, $this->pendingInvitees);
        $this->dispatch('show-toast', message: 'از گروه خارج شدید', type: 'info');
    }

    public function deleteChannel(DeleteChannelAction $action, int $channelId): void
    {
        if (!$action->execute($channelId, auth()->id())) {
            return;
        }
        if ($this->activeChannelId === $channelId) {
            $this->activeChannelId = null;
            $this->mobileShowChat = false;
            $this->loadedLimit = 10;
            $this->hasOlder = false;
            $this->focusAnchorId = null;
            $this->focusOlder = 5;
            $this->focusNewer = 5;
            $this->messageSearch = '';
        }
        unset($this->channels, $this->joinableChannels, $this->messages, $this->activeChannel, $this->activeChannelMemberUsers, $this->readers, $this->channelMembersForMentions, $this->channelMembers, $this->mentionMemberMap, $this->mentionMemberPresence, $this->pendingInvitees);
        $this->dispatch('show-toast', message: 'گروه حذف شد', type: 'info');
    }

    public function openManageMembers(int $channelId): void
    {
        $channel = $this->activeChannel;
        if (!$channel || $channelId !== (int) $channel->id || !ChannelAccessPolicy::canManageMembers($channel, auth()->user())) {
            return;
        }

        $locked = $this->lockedMemberIds($channel);
        $members = $this->activeChannelMemberUsers;

        $this->memberRecipientIds = $members
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->reject(fn(int $id) => $id === (int) auth()->id() || in_array($id, $locked, true))
            ->values()
            ->all();

        $this->memberTab = 'members';
        $this->writerIds = $members
            ->filter(fn(User $user) => $user->pivot->role !== ChannelRole::Viewer->value)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        unset($this->memberCandidates, $this->writerCandidates);
        $this->isManageMembersOpen = true;
    }

    public function saveManageMembers(SyncChannelMembersAction $action): void
    {
        $channel = $this->activeChannel;

        if (!$channel || !ChannelAccessPolicy::canManageMembers($channel, auth()->user())) {
            $this->isManageMembersOpen = false;
            $this->memberRecipientIds = [];
            return;
        }

        $writers = collect($this->writerIds)->map(fn($id) => (int) $id);
        $candidates = collect($this->writerCandidates)->pluck('id');
        $viewers = $this->activeChannelMemberUsers->filter(fn(User $user) => $user->pivot->role === ChannelRole::Viewer->value)->pluck('id')->map(fn($id) => (int) $id);

        $result = $action->execute(
            (int) $this->activeChannelId,
            (int) auth()->id(),
            $this->memberRecipientIds,
            $candidates->diff($writers)->diff($viewers)->values()->all(),
            $candidates->intersect($writers)->intersect($viewers)->values()->all(),
        );

        $this->isManageMembersOpen = false;
        $this->memberRecipientIds = [];
        $this->writerIds = [];
        unset($this->channels, $this->activeChannel, $this->memberCandidates, $this->writerCandidates, $this->activeChannelMemberUsers, $this->readers, $this->channelMembersForMentions, $this->channelMembers, $this->mentionMemberMap, $this->mentionMemberPresence, $this->pendingInvitees);

        if (($result['added'] ?? 0) || ($result['removed'] ?? 0) || ($result['silenced'] ?? 0)) {
            $this->dispatch('channel-members-updated')->self();
            $this->dispatch('show-toast', message: 'اعضای گروه به‌روزرسانی شد', type: 'success');
        }
    }

    private function lockedMemberIds(?Channel $channel): array
    {
        if (!$channel || ChannelAccessPolicy::isOwner($channel, auth()->user()) || !ChannelAccessPolicy::canManageMembers($channel, auth()->user())) {
            return [];
        }

        return $this->activeChannelMemberUsers
            ->filter(fn(User $user) => $user->pivot->role === ChannelRole::Admin->value)
            ->pluck('id')
            ->push($channel->owner_id)
            ->map(fn($id) => (int) $id)
            ->all();
    }

    public function changeChannelType(ChangeChannelTypeAction $action, int $channelId, string $type): void
    {
        if ($channelId !== $this->activeChannelId || $this->activeChannel?->type?->value === $type) {
            return;
        }

        if (!$action->execute($channelId, (int) auth()->id(), $type)) {
            $this->dispatch('show-toast', message: 'تغییر نوع گروه برای شما مجاز نیست.', type: 'error');
            return;
        }

        unset($this->activeChannel, $this->channels, $this->joinableChannels, $this->myChannelMemberships);
        $this->dispatch('show-toast', message: $type === ChannelType::Private->value ? 'گروه خصوصی شد' : 'گروه عمومی شد', type: 'success');
    }

    #[Computed]
    public function adminCandidates(): array
    {
        if (!$this->isManageAdminsOpen) {
            return [];
        }

        $ownerId = (int) $this->activeChannel?->owner_id;

        $depts = User::getCachedActiveDeptMap();

        return $this->activeChannelMemberUsers
            ->filter(fn(User $user) => (int) $user->id !== $ownerId && $user->isActive() && $user->pivot->entered_at !== null && $user->pivot->role !== ChannelRole::Viewer->value)
            ->sortBy('name')
            ->map(fn(User $user) => ['id' => (int) $user->id, 'name' => $user->name, 'dept' => $depts[(int) $user->id] ?? ''])
            ->values()
            ->all();
    }

    public function openManageAdmins(int $channelId): void
    {
        $channel = $this->activeChannel;
        if (!$channel || $channelId !== (int) $channel->id || !ChannelAccessPolicy::canManageAdmins($channel, auth()->user())) {
            return;
        }

        $this->adminRecipientIds = $this->activeChannelMemberUsers
            ->filter(fn(User $user) => $user->pivot->role === ChannelRole::Admin->value)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        $this->isManageAdminsOpen = true;
        unset($this->adminCandidates);
    }

    public function saveManageAdmins(SyncChannelAdminsAction $action): void
    {
        $channelId = (int) $this->activeChannelId;
        $result = $channelId ? $action->execute($channelId, (int) auth()->id(), $this->adminRecipientIds) : [];

        $this->isManageAdminsOpen = false;
        $this->adminRecipientIds = [];
        unset($this->adminCandidates, $this->activeChannelMemberUsers, $this->channelMembers);

        if (($result['promoted'] ?? 0) || ($result['demoted'] ?? 0)) {
            $this->dispatch('show-toast', message: 'مدیران گروه به‌روزرسانی شد', type: 'success');
        }
    }

    #[Renderless]
    #[Async]
    public function aiQuota(SummarizeChannelAction $action): string
    {
        abort_unless(channelAiOn(), 404);

        return $action->meter((int) auth()->id());
    }

    #[Renderless]
    #[Async]
    public function askAi(SummarizeChannelAction $action, string $mode, string $question = ''): array
    {
        abort_unless($this->activeChannelId, 404);

        return $action->execute((int) $this->activeChannelId, (int) auth()->id(), $mode, mb_substr(trim($question), 0, (int) config('ai.channel.question_max')));
    }

    #[Renderless]
    #[Async]
    public function enhanceEdit(EnhanceChannelMessageAction $action, int $editingId, string $text): array
    {
        return $action->execute($editingId, (int) auth()->id(), $text, $this->editTimeLimit);
    }

    public function downloadAttachment(int $messageId, int $index): ?Response
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

    public function placeholder(): View
    {
        return view('livewire.dashboard.channel.placeholder')->layout('layouts.app');
    }

    protected function recordFocusType(): string
    {
        return 'channel';
    }
}
