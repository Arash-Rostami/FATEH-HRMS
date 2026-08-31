<?php

namespace App\Livewire\Dashboard\Project;

use App\Livewire\Dashboard\Project\Presentation\ProjectPresenter;
use App\Livewire\Dashboard\TaskBoard\Forms\ReplyForm;
use App\Models\Project;
use App\Models\Task;
use App\Services\ProjectTask\ActivityLogger;
use App\Services\ProjectTask\DeleteReplyAction;
use App\Services\ProjectTask\MentionResolver;
use App\Services\ProjectTask\SaveEditReplyAction;
use App\Services\ProjectTask\ToggleReplyReactionAction;
use App\Traits\HasProjectMembers;
use Carbon\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Defer]
class Activity extends Component
{
    use HasProjectMembers, WithFileUploads;

    #[Locked]
    public ?int $activeProjectId = null;

    public ReplyForm $activityComposer;
    #[Locked]
    public int $activityLimit = 50;
    public ?int $editingReplyId = null;
    public string $editingReplyBody = '';

    public function placeholder(): View
    {
        return view('livewire.dashboard.project.activity-placeholder');
    }

    public function mount(?int $activeProjectId = null): void
    {
        $this->activeProjectId = $activeProjectId;

        $focusEntry = (int) request()->query('focus_entry', 0);
        if ($focusEntry > 0) {
            $this->focusActivityEntry($focusEntry);
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
    protected function chatPresenter(): ProjectPresenter
    {
        return new ProjectPresenter();
    }

    #[Computed]
    public function activityFeed(): array
    {
        $project = $this->activeProject;
        if (!$project) {
            return ['rows' => [], 'hasMore' => false];
        }

        $logger = app(ActivityLogger::class);
        $participants = $this->mentionCandidates;
        $mentionContext = app(MentionResolver::class)->context($participants);
        $participantsById = $participants->keyBy('id');
        $viewerId = (int) auth()->id();

        $entries = $logger->feedFor($project)
            ->with([
                'user.profile:id,user_id,image',
                'repliable' => fn($morphTo) => $morphTo->morphWith([Task::class => ['detail:id,task_id,checklist']]),
            ])
            ->reorder()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take($this->activityLimit + 1)
            ->get();

        $hasMore = $entries->count() > $this->activityLimit;

        return [
            'rows' => $entries->take($this->activityLimit)
                ->reverse()
                ->values()
                ->map(function ($reply) use ($logger, $mentionContext, $participantsById, $viewerId) {
                    $rendered = $logger->render($reply);
                    $isComment = ($reply->type?->value ?? 'comment') === 'comment';
                    $mentioned = $isComment ? $mentionContext->mentionedUsers($rendered['body']) : collect();

                    $canModify = $isComment
                        && (int) ($reply->user_id ?? 0) === $viewerId
                        && $reply->created_at->diffInSeconds(now()) <= SaveEditReplyAction::EDIT_TIME_LIMIT;

                    $task = $reply->repliable instanceof Task ? $reply->repliable : null;

                    return [
                        'id' => $reply->id,
                        'type' => $reply->type?->value ?? 'comment',
                        'user_id' => (int) ($reply->user_id ?? 0),
                        'user_name' => $reply->user?->name ?? 'سیستم',
                        'avatar_url' => $reply->user?->getProfileImageUrl(),
                        'files' => $this->chatPresenter->attachments($reply->files ?? []),
                        'reactions' => collect($reply->reactions ?? [])->map(fn(array $r) => [
                            ...$r,
                            'user_name' => $participantsById->get((int) ($r['user_id'] ?? null))?->name ?? 'کاربر',
                        ])->all(),
                        'created_at' => $reply->created_at->toIso8601String(),
                        ...$rendered,
                        'body_html' => $isComment ? $mentionContext->render($rendered['body'], $mentioned) : null,
                        'mentions_you' => $isComment && $mentioned->contains('id', $viewerId),
                        'can_modify' => $canModify,
                        'is_edited' => !empty(($reply->payload ?? [])['edited_at'] ?? null),
                        'task_progress' => $task && !empty($task->detail?->checklist) ? $task->progress_percent : null,
                    ];
                })
                ->all(),
            'hasMore' => $hasMore,
        ];
    }

    #[Computed]
    public function groupedActivityFeed(): array
    {
        $rows = $this->activityFeed['rows'] ?? [];

        return collect($rows)
            ->groupBy(fn(array $e) => Carbon::parse($e['created_at'])->setTimezone(config('app.timezone'))->toDateString())
            ->map(fn($group, $date) => [
                'date' => $date,
                'label' => Carbon::parse($date)->isToday()
                    ? 'امروز'
                    : (Carbon::parse($date)->isYesterday() ? 'دیروز' : toJalali($date, 'j F Y')),
                'entries' => $group->values()->all(),
            ])
            ->values()
            ->all();
    }

    public function loadOlderActivity(): void
    {
        $this->activityLimit += 50;
        unset($this->activityFeed, $this->groupedActivityFeed);
    }

    public function removeActivityAttachment(int $index): void
    {
        $files = $this->activityComposer->files;
        unset($files[$index]);
        $this->activityComposer->files = array_values($files);
    }

    public function startEditComment(int $replyId, string $currentBody): void
    {
        $this->editingReplyId = $replyId;
        $this->editingReplyBody = $currentBody;
        $this->resetErrorBag('editingReplyBody');
    }

    public function cancelEditComment(): void
    {
        $this->editingReplyId = null;
        $this->editingReplyBody = '';
        $this->resetErrorBag('editingReplyBody');
    }

    public function saveEditedComment(SaveEditReplyAction $action): void
    {
        if (!$this->editingReplyId) {
            return;
        }

        $this->validate([
            'editingReplyBody' => 'required|string|max:4000',
        ], [
            'editingReplyBody.required' => 'متن نظر نمی‌تواند خالی باشد.',
            'editingReplyBody.max' => 'متن نظر نباید بیشتر از ۴۰۰۰ کاراکتر باشد.',
        ]);

        $ok = $action->execute($this->editingReplyId, (int) auth()->id(), $this->editingReplyBody);

        if (!$ok) {
            $this->addError('editingReplyBody', 'امکان ویرایش این نظر وجود ندارد.');
            return;
        }

        $this->editingReplyId = null;
        $this->editingReplyBody = '';
        unset($this->activityFeed, $this->groupedActivityFeed);
    }

    public function deleteComment(DeleteReplyAction $action, int $replyId): void
    {
        $action->execute($replyId, (int) auth()->id());
        unset($this->activityFeed, $this->groupedActivityFeed);
    }

    public function toggleReaction(ToggleReplyReactionAction $action, int $replyId, string $emoji): void
    {
        $action->execute($replyId, $emoji, (int) auth()->id());
        unset($this->activityFeed, $this->groupedActivityFeed);
    }

    public function focusActivityEntry(int $id): bool
    {
        $project = $this->activeProject;
        if (!$project || $id <= 0) {
            return false;
        }

        $logger = app(ActivityLogger::class);
        $reply = $logger->feedFor($project)->whereKey($id)->first();
        if (!$reply) {
            return false;
        }

        $newerCount = $logger->feedFor($project)
            ->reorder()
            ->where(function ($q) use ($reply) {
                $q->where('created_at', '>', $reply->created_at)
                    ->orWhere(function ($q2) use ($reply) {
                        $q2->where('created_at', $reply->created_at)->where('id', '>', $reply->id);
                    });
            })
            ->count();

        if ($newerCount >= $this->activityLimit) {
            $this->activityLimit = $newerCount + 1;
            unset($this->activityFeed, $this->groupedActivityFeed);
        }

        $this->dispatch('record-focus', type: 'activity', id: $id);

        return true;
    }

    public function postComment(): void
    {
        $project = $this->activeProject;
        if (!$project) {
            return;
        }

        $this->activityComposer->validate();
        app(ActivityLogger::class)->comment($project, auth()->user(), $this->activityComposer->body, $this->activityComposer->files);
        $this->activityComposer->reset();
        unset($this->activityFeed, $this->groupedActivityFeed);
    }

    public function refreshActivity(): void
    {
        unset($this->activityFeed);
    }

    public function render(): View
    {
        return view('livewire.dashboard.project.activity', ['presenter' => new ProjectPresenter()]);
    }
}
