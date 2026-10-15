<?php

namespace App\Livewire\Dashboard\Project;

use App\Livewire\Dashboard\Channel\Actions\DownloadChannelAttachmentAction;
use App\Livewire\Dashboard\Channel\Actions\MarkChannelReadAction;
use App\Livewire\Dashboard\Channel\Actions\SendChannelMessageAction;
use App\Livewire\Dashboard\Channel\Forms\ChannelMessageComposerForm;
use App\Livewire\Dashboard\Project\Presentation\ProjectPresenter;
use App\Models\Channel;
use App\Models\ChannelMessage;
use App\Models\Project;
use App\Services\ProjectTask\ChannelProvisioner;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\Response;

class TeamChat extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?int $activeProjectId = null;

    public ChannelMessageComposerForm $chatComposer;
    #[Locked]
    public int $teamChatLimit = 30;
    public array $mentionMemberNames = [];
    public bool $activated = false;

    public function mount(?int $activeProjectId = null, bool $initialActive = false): void
    {
        $this->activeProjectId = $activeProjectId;
        $this->activated = $initialActive;
        $this->chatComposer->fill(['body' => '', 'attachments' => [], 'replyToId' => null]);

        if ($initialActive) {
            $this->markRead();
        }
    }

    public function activate(): void
    {
        $this->activated = true;
        $this->markRead();
    }

    private function markRead(): void
    {
        if ($this->activeChannel) {
            app(MarkChannelReadAction::class)->execute($this->activeChannel->id, (int) auth()->id());
        }
    }

    #[Computed]
    protected function activeProject(): ?Project
    {
        return $this->activeProjectId
            ? Project::visibleTo(auth()->user())->find($this->activeProjectId)
            : null;
    }

    #[Computed]
    public function activeChannel(): ?Channel
    {
        $project = $this->activeProject;
        return $project ? app(ChannelProvisioner::class)->resolve($project) : null;
    }

    #[Computed]
    protected function isMember(): bool
    {
        return $this->channelMembersForMentions->contains('id', (int) auth()->id());
    }

    #[Computed]
    public function teamChatMessages(): array
    {
        $channel = $this->activeChannel;
        if (!$channel || !$this->isMember) {
            return ['rows' => [], 'hasMore' => false];
        }

        $messages = ChannelMessage::withoutTrashed()
            ->where('channel_id', $channel->id)
            ->with(['sender:id,name,last_seen,status,presence,focus_until', 'sender.profile:id,user_id,image', 'replyTo:id,body,sender_id', 'replyTo.sender:id,name'])
            ->latest('id')
            ->take($this->teamChatLimit + 1)
            ->get();

        $hasMore = $messages->count() > $this->teamChatLimit;

        return [
            'rows' => $messages->take($this->teamChatLimit)
                ->reverse()
                ->values()
                ->map(fn(ChannelMessage $m) => [
                    'id' => $m->id,
                    'sender_id' => (int) $m->sender_id,
                    'body' => $m->body,
                    'is_mine' => (int) $m->sender_id === (int) auth()->id(),
                    'attachments' => $m->attachments ?? [],
                    'sender' => [
                        'name' => $m->sender?->name ?? 'ناشناس',
                        'avatar' => $m->sender?->getProfileImageUrl() ?? $m->sender?->getInitialsAvatarUrl(),
                        'online' => $m->sender?->isOnline() ?? false,
                        'presence' => $m->sender?->presence,
                        'focus' => $m->sender?->focusLabel(),
                        'inactive' => $m->sender && !$m->sender->isActive(),
                    ],
                    'reply_to_id' => $m->reply_to_id,
                    'is_edited' => (bool) $m->is_edited,
                    'deleted_at' => $m->deleted_at?->toIso8601String(),
                    'reply_to' => $m->replyTo ? [
                        'id' => $m->replyTo->id,
                        'body' => $m->replyTo->body,
                        'sender' => ['name' => $m->replyTo->sender?->name ?? 'ناشناس'],
                    ] : null,
                    'created_at' => $m->created_at->toIso8601String(),
                ])
                ->all(),
            'hasMore' => $hasMore,
        ];
    }

    public function loadOlderTeamChat(): void
    {
        $this->teamChatLimit += 30;
        unset($this->teamChatMessages, $this->groupedTeamChatMessages);
    }

    #[Computed]
    public function chatPresenter(): ProjectPresenter
    {
        return new ProjectPresenter();
    }

    #[Computed]
    protected function channelMembersForMentions(): Collection
    {
        if (!$this->activeChannel) {
            return collect();
        }

        return $this->activeChannel->memberUsers()
            ->select('users.id', 'users.name', 'users.presence', 'users.status')
            ->orderBy('users.name')
            ->get();
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

    #[Computed]
    public function groupedTeamChatMessages(): array
    {
        $rows = $this->teamChatMessages['rows'] ?? [];

        return collect($rows)
            ->groupBy(fn(array $m) => Carbon::parse($m['created_at'])->setTimezone(config('app.timezone'))->toDateString())
            ->map(fn($group, $date) => $this->chatPresenter->messageGroup(
                $date,
                $group->values()->all(),
                (int) auth()->id(),
                0,
                [],
                $this->mentionMemberMap,
            ))
            ->values()
            ->all();
    }

    public function loadMentionMemberNames(): void
    {
        $this->mentionMemberNames = array_keys($this->mentionMemberPresence);
    }

    public function removeAttachment(int $index): void
    {
        $attachments = $this->chatComposer->attachments;
        unset($attachments[$index]);
        $this->chatComposer->attachments = array_values($attachments);
    }

    public function downloadAttachment(int $messageId, int $index): ?Response
    {
        return app(DownloadChannelAttachmentAction::class)->execute(
            $messageId,
            $index,
            (int) auth()->id()
        );
    }

    public function sendChatMessage(SendChannelMessageAction $action): void
    {
        $channel = $this->activeChannel;
        if (!$channel) {
            return;
        }

        $action->execute($this->chatComposer, $channel->id);
        $this->chatComposer->reset();
        unset($this->teamChatMessages, $this->groupedTeamChatMessages);
    }

    public function refreshTeamChat(): void
    {
        unset($this->teamChatMessages, $this->groupedTeamChatMessages);
        $this->markRead();
    }

    public function render(): View
    {
        return view('livewire.dashboard.project.chat');
    }
}
