<?php

namespace App\Livewire\Dashboard\Correspondence;

use App\Livewire\Dashboard\Correspondence\Actions\LetterRules;
use App\Livewire\Dashboard\Correspondence\Actions\ShareLetterAction;
use App\Livewire\Dashboard\Correspondence\Actions\TemplateAction;
use App\Livewire\Dashboard\Correspondence\Forms\LetterForm;
use App\Livewire\Dashboard\Correspondence\Presentation\CorrespondencePresenter;
use App\Models\Correspondence;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use App\Support\CorrespondenceAccessPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Morilog\Jalali\CalendarUtils;
use PDOException;
use RuntimeException;
use Throwable;

class Main extends Component
{
    private const TABS = ['compose', 'report', 'table'];

    private const LIST_TABS = ['waiting', 'mine', 'incoming', 'all', 'registry', 'templates'];

    private const FILTERS = ['all', 'draft', 'shared', 'approved', 'overdue'];

    private const SORTS = ['updated', 'subject', 'number'];

    private const SIDE_PER_PAGE = 5;

    private const TABLE_PER_PAGE = 30;

    private const PINNED_MAX = 50;

    private const SEARCH_MAX = 64;

    private const SEARCH_TOKENS = 5;

    private const BATCH_CHARS = 20000;

    private const WAITING_MONTHS = 6;

    private const WAITING_CHUNKS = 4;

    #[Locked]
    #[Url(as: 'r')]
    public ?int $letterId = null;

    #[Locked]
    public int $listLimit = 0;

    #[Locked]
    public string $listSignature = '';

    #[Locked]
    public ?int $replyToId = null;

    #[Locked]
    public int $epoch = 0;

    #[Locked]
    public ?int $watchedId = null;

    #[Locked]
    public array $pinned = [];

    #[Locked]
    #[Url(as: 'f')]
    public string $filter = 'all';

    #[Locked]
    public string $sort = 'updated';

    #[Locked]
    public string $sortDir = 'desc';

    #[Locked]
    public string $listTab = 'waiting';

    #[Locked]
    public array $batchIds = [];

    #[Url(as: 'tab')]
    public string $activeTab = 'compose';

    #[Url(as: 'q')]
    public string $search = '';

    public string $batchLines = '';

    public string $templateSearch = '';

    private ?CorrespondencePresenter $presenterCache = null;

    private bool $more = false;

    public function mount(): void
    {
        $this->activeTab = in_array($this->activeTab, self::TABS, true) ? $this->activeTab : 'compose';
        $this->filter = in_array($this->filter, self::FILTERS, true) ? $this->filter : 'all';
        $this->listTab = $this->allowedListTab($this->listTab);
        $this->search = mb_substr(trim($this->search), 0, self::SEARCH_MAX);
        $this->listLimit = $this->pageSize();
        $open = request()->integer('open');
        if ($open > 0) {
            $this->letterId = $open;
        }
        if ($this->letterId) {
            $this->open($this->letterId);
        }
        if (is_string($error = session('correspondence_error'))) {
            $this->toast($error, 'error');
        }
    }

    public function updatedSearch(string $value): void
    {
        $this->search = mb_substr(trim($value), 0, self::SEARCH_MAX);
        $this->resetList();
    }

    public function clearFilters(): void
    {
        $this->filter = 'all';
        $this->search = '';
        $this->listTab = 'waiting';
        $this->resetList();
    }

    public function loadMore(): void
    {
        $this->listLimit = min($this->listLimit + $this->pageSize(), LetterForm::limits()['list_max']);
        $this->forgetList();
    }

    public function toggleView(string $view): void
    {
        if (!in_array($view, self::TABS, true)) {
            return;
        }
        $this->activeTab = $view;
        $this->resetList();
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, self::TABS, true)) {
            $this->toggleView($tab);

            return;
        }
        if (!in_array($tab, self::LIST_TABS, true) || $this->allowedListTab($tab) !== $tab) {
            return;
        }
        $this->listTab = $tab;
        $this->resetList();
    }

    public function setFilter(string $filter): void
    {
        if (!in_array($filter, self::FILTERS, true) || $this->filter === $filter) {
            return;
        }
        $this->filter = $filter;
        $this->resetList();
    }

    public function sortBy(string $column): void
    {
        if (!in_array($column, self::SORTS, true)) {
            return;
        }
        $this->sortDir = $this->sort === $column ? ($this->sortDir === 'asc' ? 'desc' : 'asc') : ($column === 'subject' ? 'asc' : 'desc');
        $this->sort = $column;
        $this->resetList();
    }

    public function resetSort(): void
    {
        $this->sort = 'updated';
        $this->sortDir = 'desc';
        $this->resetList();
    }

    public function syncPinned(array $ids): void
    {
        $ids = array_slice(array_values($ids), 0, self::PINNED_MAX);
        $this->pinned = array_values(array_unique(array_filter(array_map('intval', array_filter($ids, 'is_numeric')), fn (int $id) => $id > 0)));
        $this->forgetList();
    }

    public function open(int $id): void
    {
        $access = $this->access($id);
        if ($access === null) {
            $this->unavailable();

            return;
        }
        $this->select($id);
        $access === 'view' && $this->dispatch('correspondence-open');
    }

    public function close(): void
    {
        $this->letterId = null;
        $this->replyToId = null;
        $this->epoch++;
        $this->forgetList();
    }

    public function blank(): void
    {
        $this->close();
        $this->dispatch('correspondence-open');
    }

    #[On('correspondence-reply')]
    public function startReply(int $id): void
    {
        $this->close();
        $this->replyToId = $id;
        $this->dispatch('correspondence-open');
    }

    #[On('correspondence-letter-updated')]
    public function letterUpdated(?int $id = null, ?bool $watch = null): void
    {
        $this->forgetList();
        if ($id === null) {
            $this->close();

            return;
        }
        if ($id !== $this->letterId) {
            $this->access($id) === null ? $this->unavailable() : $this->select($id, $watch === null && $this->letterId === null);
        }
        if ($watch !== null && $id === $this->letterId) {
            $this->watchedId = $watch ? $id : null;
        }
    }

    public function flipTemplate(int $id, TemplateAction $action): void
    {
        $user = $this->user();
        if (!$user || $this->run(fn () => $action->toggleTemplate($user, $id)) === null) {
            return;
        }
        unset($this->letters, $this->recentTemplates);
    }

    public function useTemplate(int $id, TemplateAction $action): void
    {
        $user = $this->user();
        if (!$user) {
            return;
        }
        $copy = $this->run(fn () => $action->useTemplate($user, $id, $this->blankDraftId()));
        if (!$copy instanceof Correspondence) {
            return;
        }
        $this->select((int) $copy->id);
        $this->dispatch('correspondence-open');
        $this->toast(__('resources/correspondence/strings.toasts.copied'), 'success');
    }

    public function createBatch(int $templateId, TemplateAction $action): void
    {
        $user = $this->user();
        if (!$user) {
            return;
        }
        if (mb_strlen($this->batchLines) > self::BATCH_CHARS) {
            $this->toast(__('resources/correspondence/strings.errors.too_many_recipients'), 'warning');

            return;
        }
        $lines = preg_split('/\R/u', $this->batchLines) ?: [];
        $ids = $this->run(fn () => $action->createFromTemplate($user, $templateId, $lines));
        if (!is_array($ids)) {
            return;
        }
        $this->batchLines = '';
        $this->batchIds = $ids;
        $this->forgetList();
        $this->toast(__('resources/correspondence/strings.toasts.batch_created', ['count' => convertToPersian(count($ids))]), 'success');
        if ($ids !== []) {
            $this->open($ids[0]);
        }
    }

    public function shareBatch(ShareLetterAction $flow): void
    {
        $user = $this->user();
        if (!$user) {
            return;
        }
        $ids = array_slice(array_values(array_unique(array_map('intval', $this->batchIds))), 0, LetterForm::limits()['batch']);
        $rows = Correspondence::query()
            ->whereIn('id', $ids)
            ->ownedBy($user->id)
            ->whereIn('status', [Correspondence::STATUS_DRAFT, Correspondence::STATUS_SAVED])
            ->get(['id', 'subject', 'spec', 'content_hash']);
        $this->batchIds = array_values(array_intersect($ids, $rows->pluck('id')->all()));
        if ($rows->contains(fn (Correspondence $row) => $this->presenter()->hasUnfilled($row))) {
            $this->toast(__('resources/correspondence/strings.errors.placeholders'), 'warning');

            return;
        }

        $failed = 0;
        foreach ($rows as $row) {
            if ($this->run(fn () => $flow->execute((int) $row->id, ['expected_hash' => $row->content_hash]), true) === null) {
                $failed++;

                continue;
            }
            $this->batchIds = array_values(array_diff($this->batchIds, [(int) $row->id]));
        }
        $this->forgetList();
        if ($rows->count() > $failed) {
            $this->toast(__('resources/correspondence/strings.toasts.shared'), 'success');
        }
        if ($failed > 0) {
            $this->toast(__('resources/correspondence/strings.toasts.batch_failed', ['count' => convertToPersian($failed)]), 'warning');
        }
    }

    #[Computed]
    public function templates(): array
    {
        $user = $this->user();
        if (!$user) {
            return [];
        }
        $search = mb_substr(trim($this->templateSearch), 0, 80);
        $query = TemplateAction::templatesFor($user)
            ->select(['id', 'user_id', 'subject', 'type', 'classification', 'updated_at', 'meta'])
            ->addSelect(DB::raw('LEFT(search_text, 160) as search_text'))
            ->orderByDesc('updated_at')
            ->limit(30);
        if ($search !== '') {
            $query->where(fn (Builder $q) => $q
                ->whereRaw('INSTR(subject, ?) > 0', [$search])
                ->orWhereRaw("INSTR(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.template_name')), ?) > 0", [$search]));
        }

        return $query->get()->map(fn (Correspondence $row) => $this->presenter()->templateRow($row))->all();
    }

    public function pollList(): void
    {
        $previous = $this->listSignature;
        $this->forgetList();
        $this->letters;
        $this->announceFinished();
        if ($this->generatingId === null) {
            $this->dispatch('correspondence-generation-ended')->to(Workspace::class);
        }
        if ($previous === $this->listSignature) {
            $this->skipRender();
        }
    }

    #[Computed]
    public function letters(): Collection
    {
        $user = $this->user();
        $limit = min($this->listLimit, LetterForm::limits()['list_max']);
        $query = $user && $this->activeTab !== 'report'
            ? $this->listQuery($user)->select(match ($this->listTab) {
                'templates' => [...Correspondence::ROW_COLUMNS, 'meta'],
                'waiting' => [...Correspondence::ROW_COLUMNS, 'route_mode', 'parties'],
                default => Correspondence::ROW_COLUMNS,
            })
            : null;
        $this->more = false;
        $rows = match (true) {
            $query === null => new Collection(),
            $this->listTab === 'waiting' => $this->waitingRows($query, (int) $user->id, $limit),
            default => $query->limit($limit > 0 ? $limit + 1 : 0)->get(),
        };
        $this->more = $this->more || $rows->count() > $limit;
        $rows = $rows->take($limit)->values();
        $this->failStale($rows);
        $this->listSignature = $this->signature($rows);

        return $rows->map(fn (Correspondence $row) => $this->presenter()->listRow($row, (int) $user->id, $this->hideSubject($row, (int) $user->id)));
    }

    #[Computed]
    public function generatingId(): ?int
    {
        $id = Correspondence::query()->freshGenerating((int) auth()->id())->value('id');

        return $id === null ? null : (int) $id;
    }

    #[Computed]
    public function hasGenerating(): bool
    {
        return $this->generatingId !== null;
    }

    #[Computed]
    public function hasMore(): bool
    {
        $this->letters;

        return $this->listLimit > 0 && $this->listLimit < LetterForm::limits()['list_max'] && $this->more;
    }

    #[Computed]
    public function recentTemplates(): array
    {
        $user = $this->user();
        if (!$user) {
            return [];
        }

        return ModelCacheVersion::remember(Correspondence::class, "template-chips:{$user->id}", now()->addSeconds(60), fn () => TemplateAction::templatesFor($user)
            ->select(['id', 'subject', 'type', 'meta'])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Correspondence $row) => $this->presenter()->templateChip($row))
            ->all());
    }

    public function render(): View
    {
        return view('livewire.dashboard.correspondence', ['presenter' => $this->presenter()])
            ->extends('layouts.app')
            ->section('content');
    }

    private function waitingRows(Builder $query, int $userId, int $limit): Collection
    {
        $rows = new Collection();
        if ($limit < 1) {
            return $rows;
        }

        for ($chunk = 0; $chunk < self::WAITING_CHUNKS && $rows->count() <= $limit; $chunk++) {
            $page = $query->offset($chunk * $limit)->limit($limit)->get();
            $rows = $rows->concat($page->filter(fn (Correspondence $row) => CorrespondencePresenter::waitingForMe($row, $userId)));
            if ($page->count() < $limit) {
                return $rows->values();
            }
        }
        $this->more = $rows->count() <= $limit;

        return $rows->values();
    }

    private function listQuery(User $user): Builder
    {
        $query = match ($this->listTab) {
            'waiting' => Correspondence::query()->where('status', Correspondence::STATUS_SHARED)->where(
                fn (Builder $q) => $q->where('awaiting_user_id', $user->id)->orWhere(
                    fn (Builder $window) => $window
                        ->where('route_mode', Correspondence::ROUTE_PARALLEL)
                        ->where('shared_at', '>=', now()->subMonths(self::WAITING_MONTHS))
                        ->whereJsonContains('parties', ['user_id' => $user->id, 'role' => LetterForm::ROLE_APPROVER])
                )
            ),
            'mine' => Correspondence::query()->ownedBy($user->id),
            'incoming' => Correspondence::query()->where('direction', Correspondence::DIRECTION_IN)->visibleTo($user),
            'registry' => $this->registry()
                ? Correspondence::query()->whereIn('status', CorrespondencePresenter::REGISTRY_STATUSES)
                : Correspondence::query()->whereRaw('1 = 0'),
            'templates' => TemplateAction::templatesFor($user),
            default => Correspondence::query()->visibleTo($user),
        };
        if ($this->activeTab !== 'table') {
            return $query->orderByDesc('updated_at')->orderByDesc('id');
        }
        $this->applyTable($query, (int) $user->id);

        return $query;
    }

    private function applyTable(Builder $query, int $userId): void
    {
        match ($this->filter) {
            'draft' => $query->whereIn('status', Correspondence::DRAFT_LIKE),
            'shared' => $query->where('status', Correspondence::STATUS_SHARED),
            'approved' => $query->where('status', Correspondence::STATUS_APPROVED),
            'overdue' => $query->where('status', Correspondence::STATUS_SHARED)->where('deadline_at', '<', now()),
            default => null,
        };
        $this->applySearch($query, $userId);
        if ($this->pinned !== []) {
            $query->orderByRaw('CASE WHEN id IN (' . implode(',', array_fill(0, count($this->pinned), '?')) . ') THEN 0 ELSE 1 END', $this->pinned);
        }
        $column = match ($this->sort) {
            'subject' => 'subject',
            'number' => 'number',
            default => 'updated_at',
        };
        $query->orderBy($column, $this->sortDir === 'asc' ? 'asc' : 'desc')->orderByDesc('id');
    }

    private function applySearch(Builder $query, int $userId): void
    {
        $tokens = preg_split('/\s+/u', str_replace(['ي', 'ك'], ['ی', 'ک'], $this->search), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach (array_slice($tokens, 0, self::SEARCH_TOKENS) as $token) {
            $number = CalendarUtils::convertNumbers($token, true);
            $query->where(fn (Builder $q) => $q
                ->whereRaw('INSTR(number, ?) > 0', [$number])
                ->orWhere(fn (Builder $incoming) => $incoming
                    ->where('direction', Correspondence::DIRECTION_IN)
                    ->whereRaw('INSTR(their_number, ?) > 0', [$number]))
                ->orWhere(fn (Builder $subject) => $subject
                    ->where(fn (Builder $visible) => $visible->where('classification', Correspondence::CLASSIFICATION_NORMAL)->orWhere('user_id', $userId))
                    ->whereRaw('INSTR(subject, ?) > 0', [$token])));
        }
    }

    private function access(int $id): ?string
    {
        $user = $this->user();
        $letter = Correspondence::query()->find($id);
        if (!$letter || !$user) {
            return null;
        }

        return match (true) {
            CorrespondenceAccessPolicy::canView($user, $letter) => 'view',
            CorrespondenceAccessPolicy::canRegistry($user, $letter) => 'registry',
            default => null,
        };
    }

    private function select(int $id, bool $keepChild = false): void
    {
        $same = $id === $this->letterId;
        $same || $keepChild || $this->epoch++;
        $this->activeTab = $this->activeTab === 'compose' ? 'compose' : 'report';
        $this->letterId = $id;
        $this->listSignature = '';
        $same && $this->dispatch('correspondence-reload')->to(Workspace::class);
    }

    private function allowedListTab(string $tab): string
    {
        return in_array($tab, self::LIST_TABS, true) && ($tab !== 'registry' || $this->registry()) ? $tab : 'waiting';
    }

    private function registry(): bool
    {
        $user = $this->user();

        return $user !== null && CorrespondenceAccessPolicy::isRegistry($user);
    }

    private function presenter(): CorrespondencePresenter
    {
        return $this->presenterCache ??= new CorrespondencePresenter();
    }

    private function forgetList(): void
    {
        $this->listSignature = '';
        unset($this->letters, $this->generatingId, $this->hasGenerating, $this->hasMore);
    }

    private function announceFinished(): void
    {
        $watched = $this->watchedId;
        $current = $this->generatingId;
        $this->watchedId = $current;

        if ($watched === null || $watched === $current || ($watched === $this->letterId && $this->activeTab !== 'table')) {
            return;
        }

        $row = Correspondence::query()->ownedBy((int) auth()->id())->find($watched, ['id', 'status', 'fail_reason']);
        if ($row === null || $row->status === Correspondence::STATUS_GENERATING) {
            return;
        }

        if ($row->status === Correspondence::STATUS_FAILED) {
            $this->toast($this->presenter()->failToast($row->fail_reason), 'warning');

            return;
        }
        $this->toast(__('resources/correspondence/strings.toasts.generated'), 'success');
    }

    private function blankDraftId(): ?int
    {
        $user = $this->user();
        $letter = $user && $this->letterId !== null ? Correspondence::query()->ownedBy((int) $user->id)->find($this->letterId) : null;

        return $letter && TemplateAction::isBlank($letter, $user) ? (int) $letter->id : null;
    }

    private function hideSubject(Correspondence $letter, int $viewerId): bool
    {
        return $letter->classification !== Correspondence::CLASSIFICATION_NORMAL && (int) $letter->user_id !== $viewerId;
    }

    private function failStale(Collection $rows): void
    {
        foreach ($rows as $row) {
            if ($row->isStale() && $row->markFailed('stale')) {
                $row->status = Correspondence::STATUS_FAILED;
            }
        }
    }

    private function signature(Collection $rows): string
    {
        return implode('|', [
            $this->activeTab,
            $this->listTab,
            $this->filter,
            $this->search,
            $this->sort . ':' . $this->sortDir,
            (int) $this->hasGenerating,
            $rows->map(fn (Correspondence $row) => $row->id . ':' . $row->status)->implode(','),
        ]);
    }

    private function pageSize(): int
    {
        return match ($this->activeTab) {
            'table' => self::TABLE_PER_PAGE,
            'report' => 0,
            default => self::SIDE_PER_PAGE,
        };
    }

    private function resetList(): void
    {
        $this->listLimit = $this->pageSize();
        $this->forgetList();
    }

    private function run(callable $callback, bool $silent = false): mixed
    {
        try {
            return $callback() ?? true;
        } catch (ValidationException $e) {
            if (!$silent) {
                $this->setErrorBag($e->errors());
                $this->toast((string) collect($e->errors())->flatten()->first(), 'warning');
            }
        } catch (RuntimeException $e) {
            if ($e instanceof PDOException) {
                report($e);
                $silent || $this->toast($this->presenter()->failToast('exception'), 'warning');

                return null;
            }
            $stale = $e->getCode() === LetterRules::STALE;
            $silent || $this->toast(__('resources/correspondence/strings.errors.' . ($stale ? 'changed_elsewhere' : 'denied')), 'warning');
        } catch (Throwable $e) {
            report($e);
            $silent || $this->toast($this->presenter()->failToast('exception'), 'warning');
        }

        return null;
    }

    private function unavailable(): void
    {
        $this->close();
        $this->toast(__('resources/correspondence/strings.errors.not_found'), 'warning');
    }

    private function toast(string $message, string $type): void
    {
        $this->dispatch('toast', message: $message, type: $type);
    }

    private function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
