<?php

namespace App\Livewire\Dashboard\AiReport;

use App\Livewire\Dashboard\AiReport\Actions\DeleteReportAction;
use App\Livewire\Dashboard\AiReport\Actions\GenerateReportAction;
use App\Livewire\Dashboard\AiReport\Actions\ShareReportAction;
use App\Livewire\Dashboard\AiReport\Actions\UpdateReportAction;
use App\Livewire\Dashboard\AiReport\Forms\ComposerForm;
use App\Livewire\Dashboard\AiReport\Presentation\AiReportPresenter;
use App\Models\AiReport;
use App\Models\User;
use App\Services\AiReport\BlockCatalog;
use App\Support\AiReportAccessPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class Main extends Component
{
    use WithFileUploads;

    private const GUARDED_WHEN_READ_ONLY = ['form', 'shareRecipients', 'titleDraft', 'blockDraft'];

    private const TABS = ['compose', 'report', 'table'];

    private const SIDE_PER_PAGE = 5;

    private const TABLE_PER_PAGE = 30;

    private const PINNED_MAX = 50;

    private const FILTERS = ['all', 'mine', 'shared', 'draft', 'saved', 'inactive'];

    private const SORTS = ['id', 'updated', 'title'];

    public ComposerForm $form;

    #[Locked]
    #[Url(as: 'r')]
    public ?int $reportId = null;

    #[Locked]
    public int $listLimit = 0;

    #[Locked]
    public bool $readOnly = false;

    #[Locked]
    public ?string $lastSeenStatus = null;

    #[Locked]
    public string $listSignature = '';

    #[Locked]
    public bool $shareOpen = false;

    public array $shareRecipients = [];

    public ?string $titleDraft = null;

    #[Locked]
    public ?int $watchedId = null;

    #[Locked]
    public ?string $blockRef = null;

    #[Locked]
    public string $editorHash = '';

    public array $blockDraft = [];

    #[Url(as: 'tab')]
    public string $activeTab = 'compose';

    #[Locked]
    #[Url(as: 'f')]
    public string $filter = 'all';

    #[Url(as: 'q')]
    public string $search = '';

    #[Locked]
    public string $sort = 'id';

    #[Locked]
    public string $sortDir = 'desc';

    #[Locked]
    public string $listTab = 'active';

    #[Locked]
    public array $pinned = [];

    public bool $blockEditorOpen = false;

    public function updating(string $name, mixed $value): void
    {
        foreach (self::GUARDED_WHEN_READ_ONLY as $guarded) {
            abort_if($this->readOnly && str_starts_with($name, $guarded), 403);
        }
    }

    public function updatedBlockEditorOpen(bool $open): void
    {
        if (!$open || $this->blockRef === null) {
            $this->cancelBlockEditor();
        }
    }

    public function mount(?AiReport $report = null): void
    {
        $this->activeTab = in_array($this->activeTab, self::TABS, true) ? $this->activeTab : 'compose';
        $this->filter = in_array($this->filter, self::FILTERS, true) ? $this->filter : 'all';
        $this->search = mb_substr(trim($this->search), 0, 100);
        $this->listLimit = $this->pageSize();

        if (($notice = session()->pull('ai-report-notice')) !== null) {
            $this->toast($notice, 'warning');
        }

        if ($report?->exists) {
            if (!AiReportAccessPolicy::canView($report, auth()->user())) {
                $this->unavailable();
            } elseif (request()->routeIs('ai-report.shared')) {
                $this->redirect(route('ai-report', ['r' => $report->id]));
            } else {
                $this->select($report);
            }

            return;
        }

        if ($this->reportId === null) {
            return;
        }

        $linked = AiReport::query()->find($this->reportId);

        if ($linked !== null && AiReportAccessPolicy::canView($linked, auth()->user())) {
            $this->select($linked);

            return;
        }

        $this->unavailable();
    }

    public function updatedReportId(): void
    {
        if ($this->reportId === null) {
            $this->close();
        }
    }

    public function loadMoreReports(): void
    {
        $this->listLimit = min($this->listLimit + $this->pageSize(), (int) config('ai_report.list_max'));
        $this->listSignature = '';
        unset($this->reports);
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

        if (!in_array($tab, ['active', 'inactive'], true)) {
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

        $this->sortDir = $this->sort === $column
            ? ($this->sortDir === 'asc' ? 'desc' : 'asc')
            : ($column === 'title' ? 'asc' : 'desc');
        $this->sort = $column;
        $this->resetList();
    }

    public function syncPinned(array $ids): void
    {
        $ids = array_filter($ids, 'is_numeric');
        $ids = array_filter(array_map('intval', $ids), fn (int $id) => $id > 0);
        $this->pinned = array_slice(array_values(array_unique($ids)), 0, self::PINNED_MAX);
        $this->listSignature = '';
        $this->forgetReport();
    }

    public function resetSort(): void
    {
        $this->sort = 'id';
        $this->sortDir = 'desc';
        $this->resetList();
    }

    public function updatedSearch(string $value): void
    {
        $this->search = mb_substr(trim($value), 0, 100);
        $this->resetList();
    }

    public function open(int $id): void
    {
        $report = AiReport::query()->find($id);
        if ($report === null || !AiReportAccessPolicy::canView($report, auth()->user())) {
            $this->unavailable();

            return;
        }

        $this->select($report);
        $this->form->files = [];
        $this->listLimit = $this->pageSize();
        $this->listSignature = '';
    }

    public function close(): void
    {
        $this->reportId = null;
        $this->readOnly = false;
        $this->lastSeenStatus = null;
        $this->titleDraft = null;
        $this->shareRecipients = [];
        $this->shareOpen = false;
        $this->form->block = null;
        $this->cancelBlockEditor();
        $this->listSignature = '';
        $this->listLimit = $this->pageSize();
        $this->forgetReport();
    }

    public function reuse(): void
    {
        $report = $this->actionable();
        $versions = array_values($report?->versions ?? []);
        $prompt = $report?->forked_from === null && !($versions[0]['trimmed'] ?? false) ? $versions[0]['prompt'] ?? null : null;

        if (!filled($prompt)) {
            return;
        }

        $this->close();
        $this->form->reset();
        $this->resetValidation();
        $this->form->prompt = $prompt;
        $this->applyPresets($versions[0]);
        $this->dispatch('ai-report-open');
    }

    public function generate(GenerateReportAction $action): void
    {
        $report = $this->run(fn() => $action->execute($this->form, auth()->user()));

        if ($report === null) {
            return;
        }

        $this->form->reset();
        $this->select($report);
        $this->watchedId = $report->id;
        $this->toast('گزارش در حال تولید است؛ چند ثانیه صبر کنید.', 'success');
    }

    public function refine(GenerateReportAction $action): void
    {
        $parent = $this->actionable();

        if ($parent === null) {
            return;
        }

        $report = $this->run(fn() => $action->execute($this->form, auth()->user(), $parent));

        if ($report === null) {
            return;
        }

        $this->form->reset();
        $this->select($report);
        $this->watchedId = $report->id;
        $this->toast(
            $report->id === $parent->id
                ? 'اصلاح گزارش در حال انجام است؛ چند ثانیه صبر کنید.'
                : 'گزارش اشتراک‌گذاری‌شده ثابت می‌ماند؛ نسخهٔ جدید به‌صورت پیش‌نویس ساخته شد.',
            'success'
        );
    }

    public function retry(GenerateReportAction $action): void
    {
        $report = $this->actionable();
        $version = $report?->status === AiReport::STATUS_FAILED ? $report->lastVersion() : [];
        $prompt = $version['prompt'] ?? null;

        if (!filled($prompt) || in_array($report->fail_reason, config('ai_report.no_refund'), true)) {
            return;
        }

        $this->form->prompt = $prompt;
        $this->form->block = is_string($version['block'] ?? null) ? $version['block'] : null;

        if (!empty($version['files'])) {
            $this->dispatch('ai-report-open');
            $this->toast('فایل‌ها را دوباره پیوست کنید و روی اصلاح بزنید.', 'warning');

            return;
        }

        try {
            $this->refine($action);
        } catch (ValidationException $e) {
            $this->form->reset();
            $this->toast(collect($e->errors())->flatten()->first() ?? 'امکان تلاش مجدد نیست.', 'warning');
        }
    }

    public function editBlock(string $ref, bool $flagged = false): void
    {
        $report = $this->actionable();
        $block = $report === null ? null : $this->blockAt($report, $ref);

        if ($block === null) {
            return;
        }

        $this->form->block = $ref;

        if ($flagged && ($block['check'] ?? null) === 'mismatch' && trim($this->form->prompt) === '') {
            $this->form->prompt = $this->mismatchPrompt($block);
        }

        $this->dispatch('ai-report-open');
    }

    public function clearBlock(): void
    {
        $this->form->block = null;
    }

    public function openBlockEditor(string $ref): void
    {
        $report = $this->actionable();
        $block = $report !== null && AiReportAccessPolicy::canRevert($report, auth()->user()) ? $this->blockAt($report, $ref) : null;

        if ($block === null) {
            return;
        }

        $this->blockRef = $ref;
        $this->editorHash = md5(json_encode($block));
        $this->blockDraft = $block;
        $this->resetValidation();
        $this->blockEditorOpen = true;
    }

    public function saveBlock(UpdateReportAction $action): void
    {
        $ref = $this->blockRef;
        if ($ref === null || !$this->update($action, ['block' => $ref, 'data' => $this->blockDraft, 'editor_hash' => $this->editorHash], 'بخش ویرایش شد و به‌عنوان نسخهٔ تازه ذخیره شد.')) {
            return;
        }

        $this->cancelBlockEditor();
    }

    public function cancelBlockEditor(): void
    {
        $this->blockEditorOpen = false;
        $this->blockRef = null;
        $this->editorHash = '';
        $this->blockDraft = [];
        $this->resetValidation();
    }

    public function save(UpdateReportAction $action): void
    {
        $this->update($action, ['save' => true], 'گزارش ذخیره شد.');
    }

    public function rename(UpdateReportAction $action): void
    {
        $this->update($action, ['title' => (string) ($this->titleDraft ?? $this->report?->title)], 'عنوان گزارش ذخیره شد.');
    }

    public function revert(UpdateReportAction $action, int $index): void
    {
        $this->update($action, ['revert_to' => $index], 'گزارش به نسخهٔ انتخابی برگشت.');
    }

    public function toggleActive(UpdateReportAction $action): void
    {
        $active = !($this->actionable()?->active ?? true);
        $this->update(
            $action,
            ['active' => $active],
            $active ? 'گزارش دوباره فعال شد.' : 'گزارش غیرفعال شد؛ گیرندگان دیگر به آن دسترسی ندارند.'
        );
    }

    public function share(ShareReportAction $action): void
    {
        $report = $this->actionable();

        if ($report === null) {
            return;
        }

        $result = $this->run(
            fn() => $action->execute($report, auth()->user(), array_map('intval', $this->shareRecipients)),
            'اشتراک‌گذاری این گزارش مجاز نیست.'
        );

        if ($result === null) {
            return;
        }

        $this->forgetReport();
        $this->shareRecipients = array_map('strval', $this->report?->recipients ?? []);
        [$message, $type] = $this->shareToast($result);
        $this->toast($message, $type);
        $this->dispatch('close-modal', name: 'ai-report-share');
    }

    public function delete(DeleteReportAction $action): void
    {
        $report = $this->actionable();

        if ($report === null || $this->run(fn() => $action->execute($report, auth()->user()), 'حذف این گزارش مجاز نیست.') === null) {
            return;
        }

        $this->close();
        $this->toast('گزارش حذف شد.', 'success');
    }

    public function openShare(): void
    {
        $report = $this->actionable();

        if ($report !== null) {
            $this->shareRecipients = array_map('strval', $report->recipients ?? []);
            $this->shareOpen = true;
        }
    }

    public function pollList(): void
    {
        $previous = $this->listSignature;
        unset($this->reports, $this->hasGenerating);
        $this->reports;
        $this->announceFinished();

        if ($previous === $this->listSignature) {
            $this->skipRender();
        }
    }

    public function pollStatus(): void
    {
        if ($this->lastSeenStatus === AiReport::STATUS_GENERATING && $this->reportId !== null && $this->stillGenerating()) {
            $this->skipRender();

            return;
        }

        $this->forgetReport();
        $report = $this->target();

        if ($report === null) {
            return;
        }

        if ($report->status === $this->lastSeenStatus) {
            $report->status === AiReport::STATUS_GENERATING && $this->skipRender();

            return;
        }

        $previous = $this->lastSeenStatus;
        $this->lastSeenStatus = $report->status;

        if ($previous !== AiReport::STATUS_GENERATING) {
            return;
        }

        $this->watchedId = null;

        if ($report->status === AiReport::STATUS_FAILED) {
            $this->toast((new AiReportPresenter())->failToast($report->fail_reason), 'warning');

            return;
        }

        $this->titleDraft = $report->title;
        $this->toast('گزارش آماده شد.', 'success');
    }

    #[Computed]
    public function report(): ?AiReport
    {
        if ($this->reportId === null) {
            return null;
        }

        $report = AiReport::query()->with('user:id,name')->find($this->reportId);

        if ($report === null || !AiReportAccessPolicy::canView($report, auth()->user())) {
            return null;
        }

        if ($report->isStale() && $report->markFailed('stale')) {
            return $report->fresh();
        }

        return $report;
    }

    #[Computed]
    public function reports(): Collection
    {
        $rows = $this->activeTab === 'report' ? new Collection() : $this->listQuery()
            ->select([...AiReport::LIST_COLUMNS, 'seen_by', 'active'])
            ->when($this->activeTab === 'table', fn (Builder $q) => $q->addSelect(['preset_type', 'preset_length', 'preset_audience'])->with('user:id,name'))
            ->limit($this->listLimit)
            ->get()
            ->each(function (AiReport $row) {
                if ($row->isStale() && $row->markFailed('stale')) {
                    $row->status = AiReport::STATUS_FAILED;
                    $row->fail_reason = 'stale';
                }
            });

        $this->listSignature = "{$this->activeTab}|{$this->listTab}|{$this->filter}|{$this->search}|{$this->sort}:{$this->sortDir}|" . (int) $this->hasGenerating . '|'
            . $rows->map(fn (AiReport $row) => "{$row->id}:{$row->status}:{$row->fail_reason}:" . count($row->seen_by ?? []))->implode('|');

        return $rows;
    }

    #[Computed]
    public function hasGenerating(): bool
    {
        return AiReport::query()->freshGenerating((int) auth()->id())->exists();
    }

    #[Computed]
    public function total(): int
    {
        return $this->listQuery()->reorder()->count();
    }

    #[Computed]
    public function recipientCandidates(): array
    {
        return User::getCachedActiveOptions()
            ->except(auth()->id())
            ->map(fn($name, $id) => ['id' => (int) $id, 'name' => $name])
            ->values()
            ->all();
    }

    #[Computed]
    public function quota(): array
    {
        return ['used' => AiReport::quotaUsed((int) auth()->id()), 'cap' => AiReport::quotaCap()];
    }

    public function render(): View
    {
        return view('livewire.dashboard.ai-report', ['presenter' => new AiReportPresenter()])
            ->extends('layouts.app')
            ->section('content');
    }

    private function select(AiReport $report): void
    {
        $this->activeTab = $this->activeTab === 'compose' ? 'compose' : 'report';
        $this->reportId = $report->id;
        $this->readOnly = !$report->isOwnedBy(auth()->user());
        $this->lastSeenStatus = $report->status;
        $this->titleDraft = $report->title;
        $this->shareRecipients = $this->readOnly ? [] : array_map('strval', $report->recipients ?? []);
        $this->shareOpen = false;
        $this->form->block = null;
        $this->cancelBlockEditor();
        if ($this->readOnly && $report->isRecipient(auth()->user())) {
            $report->markSeenBy((int) auth()->id());
        }
        if (!$this->readOnly) {
            $this->applyPresets($report->lastVersion());
        }
        $this->forgetReport();
    }

    private function applyPresets(array $version): void
    {
        foreach (['type' => 'types', 'length' => 'lengths', 'audience' => 'audiences'] as $key => $group) {
            if (is_string($version[$key] ?? null) && is_array(config("ai_report.{$group}.{$version[$key]}"))) {
                $this->form->{$key} = $version[$key];
            }
        }
    }

    private function forgetReport(): void
    {
        unset($this->report, $this->reports, $this->quota, $this->hasGenerating, $this->total);
    }

    private function blockAt(AiReport $report, string $ref): ?array
    {
        return BlockCatalog::resolveBlock($report->spec ?? [], $ref)['block'] ?? null;
    }

    private function mismatchPrompt(array $block): string
    {
        $columns = collect(BlockCatalog::mismatchColumns($block))
            ->map(fn(int $j) => $block['columns'][$j] ?? null)
            ->filter(fn($name) => filled($name))
            ->implode('»، «');
        $what = $columns !== '' ? "مجموع ستون «{$columns}»" : 'ارقام';
        $where = filled($block['heading'] ?? null) ? " در جدول «{$block['heading']}»" : ' در این جدول';

        return mb_substr("{$what}{$where} با ردیف‌ها نمی‌خواند؛ لطفاً ارقام را با فایل پیوست یا درخواست اصلی تطبیق بده و جدول را اصلاح کن.", 0, (int) config('ai_report.prompt.max'));
    }

    private function listQuery(): Builder
    {
        $user = auth()->user();
        $filter = $this->activeTab === 'table' ? $this->filter : ($this->listTab === 'inactive' ? 'inactive' : 'all');
        $search = $this->activeTab === 'table' ? $this->search : '';

        $query = $filter === 'inactive'
            ? AiReport::query()->ownedBy($user->id)->inactive()
            : AiReport::query()->visibleTo($user);

        match ($filter) {
            'mine' => $query->ownedBy($user->id),
            'shared' => $query->where('user_id', '!=', $user->id),
            'draft' => $query->whereNull('saved_at'),
            'saved' => $query->whereNotNull('saved_at'),
            default => $query,
        };

        if ($search !== '') {
            $query->whereRaw('INSTR(title, ?) > 0', [$search]);
        }

        if ($this->activeTab === 'table' && $this->pinned !== []) {
            $query->orderByRaw('CASE WHEN id IN (' . implode(',', array_fill(0, count($this->pinned), '?')) . ') THEN 0 ELSE 1 END', $this->pinned);
        }

        return match ($this->activeTab === 'table' ? $this->sort : 'id') {
            'title' => $query->orderBy('title', $this->sortDir)->orderByDesc('id'),
            'updated' => $query->orderBy('updated_at', $this->sortDir)->orderByDesc('id'),
            default => $query->orderByDesc('id'),
        };
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
        $this->listSignature = '';
        $this->forgetReport();
    }

    private function announceFinished(): void
    {
        $userId = (int) auth()->id();
        $current = AiReport::query()->freshGenerating($userId)->value('id');
        $watched = $this->watchedId;
        $this->watchedId = $current;

        $viewing = $watched === $this->reportId && $this->activeTab !== 'table';

        if ($watched === null || $watched === $current || $viewing) {
            return;
        }

        $row = AiReport::query()->ownedBy($userId)->find($watched, ['id', 'status', 'fail_reason']);

        match ($row?->status) {
            AiReport::STATUS_READY => $this->toast('گزارش آماده شد.', 'success'),
            AiReport::STATUS_FAILED => $this->toast((new AiReportPresenter())->failToast($row->fail_reason), 'warning'),
            default => null,
        };
    }

    private function stillGenerating(): bool
    {
        return AiReport::query()
            ->whereKey($this->reportId)
            ->active()
            ->freshGenerating((int) auth()->id())
            ->exists();
    }

    private function target(): ?AiReport
    {
        $report = $this->report;

        if ($report === null && $this->reportId !== null) {
            $this->unavailable();
        }

        return $report;
    }

    private function actionable(): ?AiReport
    {
        return $this->readOnly ? null : $this->target();
    }

    private function unavailable(): void
    {
        $this->toast('این گزارش دیگر در دسترس نیست.', 'warning');
        $this->close();
    }

    private function shareToast(array $result): array
    {
        [$message, $type] = match (true) {
            $result['added'] > 0 && $result['removed'] > 0 => [convertToPersian($result['added']) . ' نفر افزوده و ' . convertToPersian($result['removed']) . ' نفر از گیرندگان حذف شد.', 'success'],
            $result['added'] > 0 => [convertToPersian($result['added']) . ' نفر تازه به گیرندگان افزوده شد.', 'success'],
            $result['removed'] > 0 => [convertToPersian($result['removed']) . ' نفر از گیرندگان حذف شد.', 'success'],
            default => ['گیرندگان تغییری نکرد.', 'warning'],
        };

        if ($result['truncated']) {
            return [$message . ' تعداد گیرندگان به سقف ' . convertToPersian(config('ai_report.max_recipients')) . ' نفر محدود شد.', 'warning'];
        }

        return [$message, $type];
    }

    private function run(callable $callback, ?string $denied = null): mixed
    {
        try {
            return $callback() ?? true;
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            if ($denied !== null && $e instanceof \RuntimeException && !$e instanceof \PDOException) {
                $stale = $e->getCode() === UpdateReportAction::STALE_EDITOR;
                $this->toast($stale ? $e->getMessage() : $denied, 'warning');
                $stale && $this->cancelBlockEditor();

                return null;
            }

            report($e);
            $this->toast((new AiReportPresenter())->failToast('exception'), 'warning');

            return null;
        }
    }

    private function update(UpdateReportAction $action, array $changes, string $message): bool
    {
        $report = $this->actionable();

        if ($report === null || $this->run(fn() => $action->execute($report, auth()->user(), $changes), 'این عملیات برای این گزارش مجاز نیست.') === null) {
            return false;
        }

        $this->forgetReport();
        $this->titleDraft = $this->report?->title;
        $this->toast($message, 'success');

        return true;
    }

    private function toast(string $message, string $type): void
    {
        $this->dispatch('toast', message: $message, type: $type);
    }
}
