<?php

namespace App\Livewire\Dashboard\Launchpad;

use App\Livewire\Dashboard\Launchpad\Actions\ActivateLaunchpadAction;
use App\Livewire\Dashboard\Launchpad\Actions\CreateLaunchpadAction;
use App\Livewire\Dashboard\Launchpad\Actions\DeactivateLaunchpadAction;
use App\Livewire\Dashboard\Launchpad\Actions\DeleteLaunchpadAction;
use App\Livewire\Dashboard\Launchpad\Actions\RefreshLaunchpadAction;
use App\Livewire\Dashboard\Launchpad\Actions\UpdateLaunchpadAction;
use App\Livewire\Dashboard\Launchpad\Forms\LaunchpadEditForm;
use App\Livewire\Dashboard\Launchpad\Forms\LaunchpadForm;
use App\Livewire\Dashboard\Launchpad\Presentation\LaunchpadPresenter;
use App\Models\Department;
use App\Models\Launchpad;
use App\Models\User;
use App\Support\LaunchpadAccessPolicy;
use App\Traits\Livewire\BulkSelectsPeople;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Morilog\Jalali\CalendarUtils;
use Morilog\Jalali\Jalalian;
use RuntimeException;

#[Lazy]
class Main extends Component
{
    use BulkSelectsPeople;
    use WithFileUploads {
        _finishUpload as private finishLivewireUpload;
    }

    public function _finishUpload($name, $tmpPath, $isMultiple, $append = true): void
    {
        $this->finishLivewireUpload($name, $tmpPath, $isMultiple, $name === 'form.files' ? false : $append);
    }

    private const TABS = ['list', 'create', 'compact'];

    public LaunchpadForm $form;

    #[Url(as: 'tab')]
    public string $activeTab = 'list';

    #[Locked]
    #[Url(as: 'a')]
    public ?int $openId = null;

    #[Locked]
    public ?string $viewToken = null;

    #[Locked]
    #[Url(as: 'embed')]
    public bool $embed = false;

    #[Locked]
    public ?string $unavailable = null;

    #[Locked]
    #[Url(as: 'f')]
    public string $filter = 'all';

    #[Url(as: 'q')]
    public string $search = '';

    #[Locked]
    public string $listStatus = 'active';

    #[Locked]
    public string $statusFilter = 'all';

    #[Locked]
    public string $typeFilter = 'all';

    #[Locked]
    public string $unseenFilter = 'all';

    #[Locked]
    public array $openFresh = [];

    #[Locked]
    public int $listLimit = 30;

    #[Locked]
    public string $sort = 'id';

    #[Locked]
    public string $sortDir = 'desc';

    #[Locked]
    public array $precheck = [];

    #[Locked]
    public array $details = [];

    public string $fromYear = '';
    public string $fromMonth = '';
    public string $fromDay = '';
    public string $toYear = '';
    public string $toMonth = '';
    public string $toDay = '';
    public string $expiryYear = '';
    public string $expiryMonth = '';
    public string $expiryDay = '';

    public LaunchpadEditForm $editForm;

    #[Locked]
    public ?int $editId = null;

    public bool $editOpen = false;

    #[Locked]
    public array $editContext = [];

    #[Locked]
    public array $editLoaded = [];

    public string $editFromYear = '';
    public string $editFromMonth = '';
    public string $editFromDay = '';
    public string $editToYear = '';
    public string $editToMonth = '';
    public string $editToDay = '';
    public string $editExpiryYear = '';
    public string $editExpiryMonth = '';
    public string $editExpiryDay = '';

    public function mount(): void
    {
        $this->activeTab = in_array($this->activeTab, self::TABS, true) ? $this->activeTab : 'list';
        $this->filter = in_array($this->filter, ['all', 'mine', 'shared', 'department'], true) ? $this->filter : 'all';
        $this->search = mb_substr(trim($this->search), 0, 100);
        if (!$this->embed) {
            $this->form->department_id = (string) auth()->user()->profile?->department_id;
        }
        if ($this->openId !== null) {
            $this->open($this->openId);
        }
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, self::TABS, true)) {
            $this->activeTab = $tab;
            $this->close();
        } elseif (in_array($tab, ['active', 'inactive'], true)) {
            $this->listStatus = $tab;
            $this->listLimit = 30;
        }
    }

    public function toggleView(string $view): void
    {
        $this->switchTab($view);
    }

    public function updatedActiveTab(): void
    {
        $this->activeTab = in_array($this->activeTab, self::TABS, true) ? $this->activeTab : 'list';
    }

    public function setFilter(string $filter): void
    {
        if (in_array($filter, ['all', 'mine', 'shared', 'department'], true)) {
            $this->filter = $filter;
            $this->listLimit = 30;
        }
    }

    public function setStatusFilter(string $status): void
    {
        if (in_array($status, ['all', 'active', 'inactive'], true)) {
            $this->statusFilter = $status;
            $this->listLimit = 30;
        }
    }

    public function setTypeFilter(string $type): void
    {
        if (in_array($type, ['all', 'static', 'link'], true)) {
            $this->typeFilter = $type;
            $this->listLimit = 30;
        }
    }

    public function setUnseenFilter(string $unseen): void
    {
        if (in_array($unseen, ['all', 'unseen'], true)) {
            $this->unseenFilter = $unseen;
            $this->listLimit = 30;
        }
    }

    public function updatedSearch(): void
    {
        $this->search = mb_substr(trim($this->search), 0, 100);
        $this->listLimit = 30;
    }

    public function sortBy(string $column): void
    {
        if (!in_array($column, ['id', 'title'], true)) {
            return;
        }

        $this->sortDir = $this->sort === $column
            ? ($this->sortDir === 'asc' ? 'desc' : 'asc')
            : ($column === 'title' ? 'asc' : 'desc');
        $this->sort = $column;
        $this->listLimit = 30;
    }

    public function resetSort(): void
    {
        $this->sort = 'id';
        $this->sortDir = 'desc';
        $this->listLimit = 30;
    }

    public function loadMore(): void
    {
        $this->listLimit += 30;
    }

    public function open(int $id): void
    {
        $this->resetEdit();
        $row = Launchpad::query()->find($id, ['id', 'user_id', 'kind', 'active', 'audience', 'seen_by', 'accessible_from', 'accessible_to', 'expire_at', 'settings']);
        $user = auth()->user();
        $link = $row !== null && $this->embed && $this->ssoLink($row);
        if ($row === null || ($row->kind !== 'static' && !$link) || !LaunchpadAccessPolicy::canView($row, $user) || ($link && LaunchpadAccessPolicy::workspaceBlock($row, $user) !== null)) {
            $this->openId = null;
            $this->viewToken = null;
            $this->openFresh = [];
            $this->unavailable = 'این برنامه در دسترس نیست.';
            if (!$this->embed) {
                $this->dispatch('toast', message: $this->unavailable, type: 'warning');
                $this->unavailable = null;
            }
            return;
        }
        $this->openId = (int) $row->id;
        $this->viewToken = $link ? null : LaunchpadAccessPolicy::token($row, $user);
        $this->openFresh = $link ? [] : app(LaunchpadPresenter::class)->freshness($row, LaunchpadAccessPolicy::canManage($row, $user));
        $this->unavailable = null;
        $row->markSeenBy((int) $user->id);
    }

    public function showDetails(int $id): void
    {
        $columns = ['id', 'user_id', 'department_id', 'kind', 'title', 'description', 'active', 'audience', 'accessible_from', 'accessible_to', 'expire_at', 'settings', 'created_at', 'updated_at'];
        $user = auth()->user();
        $row = Launchpad::query()->with('user:id,name')->find($id, $columns);
        if ($row === null || !LaunchpadAccessPolicy::canView($row, $user)) {
            $this->details = [];
            $this->dispatch('toast', message: 'این برنامه در دسترس نیست.', type: 'warning');
            return;
        }
        $manager = LaunchpadAccessPolicy::canManage($row, $user);
        if ($manager && $row->kind === 'static') {
            $row = Launchpad::query()->with('user:id,name')->find($id, [...$columns, 'source']) ?? $row;
        }
        $this->details = app(LaunchpadPresenter::class)->details($row, $manager);
        $this->dispatch('open-modal', name: 'launchpad-details');
    }

    public function openEdit(int $id): void
    {
        $row = Launchpad::query()->find($id, ['id', 'user_id', 'department_id', 'kind', 'title', 'description', 'audience', 'accessible_from', 'accessible_to', 'expire_at', 'source', 'settings']);
        if ($this->embed || $row === null || !LaunchpadAccessPolicy::canManage($row, auth()->user())) {
            $this->dispatch('toast', message: 'این برنامه در دسترس نیست.', type: 'warning');
            return;
        }
        $this->resetEdit();
        $this->editForm->load($row);
        $this->editContext = ['title' => (string) $row->title, 'department' => (string) (Department::getCachedOptions()[$row->department_id] ?? $row->department_id), 'owner' => (int) $row->user_id];
        $this->fillEditDate('editFrom', $row->accessible_from);
        $this->fillEditDate('editTo', $row->accessible_to);
        $this->fillEditDate('editExpiry', $row->expire_at);
        $this->editId = (int) $row->id;
        $this->editOpen = true;
        $this->dispatch('close-modal');
    }

    public function saveEdit(UpdateLaunchpadAction $action): void
    {
        if ($this->editId === null) {
            return;
        }
        $this->editForm->accessible_from = $this->editDate('editFrom');
        $this->editForm->accessible_to = $this->editDate('editTo');
        $this->editForm->expire_at = $this->editDate('editExpiry');
        try {
            $action->execute($this->editId, $this->editForm, auth()->user());
        } catch (RuntimeException $e) {
            $this->resetEdit();
            $this->dispatch('toast', message: $e->getMessage(), type: 'warning');
            return;
        } catch (QueryException) {
            $this->dispatch('toast', message: 'ذخیرهٔ برنامه انجام نشد. دوباره تلاش کنید؛ اگر مشکل ادامه داشت، به پشتیبانی اطلاع دهید.', type: 'warning');
            return;
        }
        $this->resetEdit();
        $this->details = [];
        unset($this->rows, $this->counts);
        $this->dispatch('toast', message: 'برنامه ویرایش شد.', type: 'success');
    }

    public function closeEdit(): void
    {
        $this->resetEdit();
    }

    public function updatedEditOpen(bool $open): void
    {
        if (!$open) {
            $this->resetEdit();
        }
    }

    public function close(): void
    {
        $this->resetEdit();
        $this->openId = null;
        $this->viewToken = null;
        $this->openFresh = [];
        $this->unavailable = null;
    }

    public function updatedFormFiles(): void
    {
        $this->precheck = app(LaunchpadPresenter::class)->precheck($this->form->precheck());
    }

    protected function bulkSelectTargets(): array
    {
        return ['form.audience', 'editForm.audience'];
    }

    protected function bulkPeoplePool(string $property, ?string $dept = null): array
    {
        return $this->bulkDefaultPeoplePool($dept, $property === 'editForm.audience' ? (int) ($this->editContext['owner'] ?? 0) : null);
    }

    protected function bulkSelectLimit(string $property): ?int
    {
        return (int) config('launchpad.max_audience');
    }

    #[Computed]
    public function deptOptions(): array
    {
        return Department::getCachedOptions()->toArray();
    }

    public function save(): void
    {
        $this->form->accessible_from = $this->date('from');
        $this->form->accessible_to = $this->date('to');
        $this->form->expire_at = $this->date('expiry');
        $this->form->title = $this->form->normalizedTitle();
        $this->form->normalizeSource();
        $this->form->validate();
        if ($this->form->kind === 'static' && ($this->precheck['ok'] ?? false) !== true) {
            throw ValidationException::withMessages(['form.files' => 'فایل‌ها آمادهٔ ذخیره نیستند. مشکل‌های نوشته‌شده زیر بخش انتخاب فایل را برطرف کنید و دوباره فایل‌ها را انتخاب کنید.']);
        }
        $title = $this->form->title;
        $existing = Launchpad::query()->where('department_id', $this->form->department_id)->where('title', $title)->with('user:id,name')->first(['id', 'user_id']);
        if ($existing !== null && LaunchpadAccessPolicy::canManage($existing, auth()->user())) {
            $this->dispatch('open-confirmation',
                title: 'جایگزینی برنامهٔ قبلی',
                message: implode("\n", [
                    "نام برنامه: {$title}",
                    'دپارتمان: ' . (Department::getCachedOptions()[$this->form->department_id] ?? $this->form->department_id),
                    'مالک: ' . ($existing->user?->name ?? '—'),
                    'در این دپارتمان برنامه‌ای با همین نام وجود دارد. فایل‌ها یا میان‌بر و توضیح قبلی با اطلاعات تازه عوض می‌شوند. فایل‌های قبلی برای همیشه حذف می‌شوند. مخاطبان و تاریخ‌های برنامهٔ قبلی حفظ می‌شوند؛ انتخاب‌های تازهٔ شما برای مخاطبان و تاریخ‌ها اعمال نمی‌شوند. اگر «نوع منبع داده» را روی «بدون منبع داده» قرار دهید، اتصال قبلی حفظ می‌شود؛ در غیر این صورت، اتصال قبلی حذف و اتصال جدید جایگزین آن می‌شود.',
                ]),
                method: 'confirmReplace',
                type: 'dispatch',
                tone: 'danger',
            );
            return;
        }
        $this->persist(false);
    }

    public function confirmReplace(): void
    {
        $this->persist(true);
    }

    public function delete(int $id): void
    {
        $row = Launchpad::query()->find($id, ['id', 'user_id']);
        if ($row === null || !LaunchpadAccessPolicy::canManage($row, auth()->user())) {
            $this->dispatch('toast', message: 'شما اجازهٔ حذف این برنامه را ندارید یا برنامه دیگر در دسترس نیست.', type: 'warning');
            return;
        }
        try {
            app(DeleteLaunchpadAction::class)->execute($row, auth()->user());
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: 'شما اجازهٔ حذف این برنامه را ندارید یا برنامه دیگر در دسترس نیست.', type: 'warning');
            return;
        }
        $this->dispatch('toast', message: 'برنامه حذف شد.', type: 'success');
    }

    public function activate(int $id): void
    {
        [$message, $type] = match (app(ActivateLaunchpadAction::class)->execute($id, auth()->user())) {
            'ok' => ['برنامه فعال شد.', 'success'],
            'expired' => ['مهلت دسترسی به برنامه تمام شده است.', 'warning'],
            default => ['این برنامه در دسترس نیست.', 'warning'],
        };
        $this->dispatch('toast', message: $message, type: $type);
    }

    public function deactivate(int $id): void
    {
        app(DeactivateLaunchpadAction::class)->execute($id, auth()->user())
            ? $this->dispatch('toast', message: 'برنامه غیرفعال شد.', type: 'success')
            : $this->dispatch('toast', message: 'این برنامه در دسترس نیست.', type: 'warning');
    }

    public function addOption(int $id): void
    {
        $user = auth()->user();
        $row = Launchpad::query()->find($id, ['id', 'user_id', 'kind', 'active', 'audience', 'accessible_from', 'accessible_to', 'expire_at', 'settings']);
        $link = $row !== null && $this->ssoLink($row);
        if ($row !== null && $row->kind === 'link' && !$link && LaunchpadAccessPolicy::canView($row, $user)) {
            $this->dispatch('toast', message: 'این سامانه در فضای کاری باز نمی‌شود؛ آن را از فهرست برنامه‌ها در زبانهٔ جدید باز کنید.', type: 'info');
            return;
        }
        if ($row === null || ($row->kind !== 'static' && !$link) || LaunchpadAccessPolicy::workspaceBlock($row, $user) !== null) {
            $this->dispatch('toast', message: 'این برنامه در دسترس نیست.', type: 'warning');
            return;
        }
        $this->changeOptions($id, true)
            ? $this->dispatch('toast', message: 'برنامه به فضای کاری افزوده شد.', type: 'success')
            : $this->dispatch('toast', message: 'این برنامه در فضای کاری وجود دارد.', type: 'warning');
    }

    public function removeOption(int $id): void
    {
        $this->changeOptions($id, false)
            ? $this->dispatch('toast', message: 'از فضای کاری حذف شد.', type: 'success')
            : $this->dispatch('toast', message: 'این برنامه در فضای کاری نیست.', type: 'warning');
    }

    public function refreshNow(int $id): void
    {
        try {
            $result = app(RefreshLaunchpadAction::class)->refreshNow($id, auth()->user());
        } catch (AuthorizationException) {
            $this->dispatch('toast', message: 'شما اجازهٔ به‌روزرسانی اطلاعات این برنامه را ندارید.', type: 'warning');
            return;
        }
        [$message, $type] = match ($result) {
            'queued' => ['درخواست ثبت شد. اگر نوبت دریافت شبانهٔ این برنامه از قبل در صف باشد، همان اجرا می‌شود و نتیجه کمی دیرتر می‌رسد.', 'success'],
            'dead' => ['برنامه غیرفعال است یا خارج از بازهٔ دسترسی قرار دارد؛ ابتدا آن را فعال کنید.', 'warning'],
            'cooldown' => ['برای این برنامه به‌تازگی درخواست دریافت اطلاعات ثبت شده است. چند دقیقه صبر کنید و دوباره تلاش کنید.', 'warning'],
            default => ['برای این برنامه اتصال دریافت اطلاعات تنظیم نشده یا دریافت اطلاعات در وضعیت فعلی آن ممکن نیست.', 'warning'],
        };
        $this->dispatch('toast', message: $message, type: $type);
    }

    #[Computed]
    public function rows(): Collection
    {
        [$sql, $bindings] = $this->liveCondition();
        $status = $this->activeTab === 'compact' ? $this->listStatus : $this->statusFilter;

        return $this->scoped()->select(['id', 'title', 'department_id', 'user_id', 'kind', 'active', 'audience', 'accessible_from', 'accessible_to', 'expire_at', 'description', 'settings', 'refresh_due_at'])->with('user:id,name')
            ->when($status !== 'all', fn (Builder $q) => $q->whereRaw($status === 'inactive' ? "NOT ({$sql})" : "({$sql})", $bindings))
            ->when($this->activeTab !== 'compact' && $this->typeFilter !== 'all', fn (Builder $q) => $q->where('kind', $this->typeFilter))
            ->when($this->activeTab !== 'compact' && $this->unseenFilter === 'unseen', fn (Builder $q) => $q->unseenBy((int) auth()->id()))
            ->when($this->sort === 'title', fn (Builder $q) => $q->orderBy('title', $this->sortDir)->orderByDesc('id'), fn (Builder $q) => $q->orderBy('id', $this->sortDir))
            ->limit($this->listLimit + 1)->get();
    }

    #[Computed]
    public function counts(): array
    {
        [$sql, $bindings] = $this->liveCondition();
        $row = $this->scoped()->selectRaw("COUNT(*) AS total, COALESCE(SUM(CASE WHEN {$sql} THEN 1 ELSE 0 END), 0) AS live", $bindings)->first();
        $live = (int) $row->live;

        return ['active' => $live, 'inactive' => (int) $row->total - $live];
    }

    #[Computed]
    public function candidates(): array
    {
        return $this->peopleExcept((int) auth()->id());
    }

    #[Computed]
    public function editCandidates(): array
    {
        return $this->peopleExcept((int) ($this->editContext['owner'] ?? 0));
    }

    private function peopleExcept(int $exceptId): array
    {
        $depts = User::getCachedActiveDeptMap();

        return User::getCachedActiveOptions()->except($exceptId)
            ->map(fn ($name, $id) => ['id' => (int) $id, 'name' => $name, 'dept' => $depts[(int) $id] ?? ''])
            ->values()->all();
    }

    #[Computed]
    public function limits(): array
    {
        return [
            'max_files' => LaunchpadForm::maxFiles(),
            'per_file_mb' => (int) ((int) config('file_upload_limits.launchpad') / 1024),
            'total_mb' => (int) ((int) config('file_upload_limits.launchpad_total') / 1024),
        ];
    }

    public function placeholder(): View
    {
        return view('livewire.dashboard.launchpad.placeholder');
    }

    public function render(): View
    {
        return view('livewire.dashboard.launchpad', [
            'presenter' => app(LaunchpadPresenter::class),
            'options' => !$this->embed && $this->openId === null && $this->activeTab === 'list' ? Launchpad::optionIds(auth()->user()) : [],
            'departments' => !$this->embed && $this->openId === null && in_array($this->activeTab, ['create', 'compact'], true)
                ? (auth()->user()->hasElevatedRole() ? Department::getCachedOptions() : Department::getCachedOptions()->only([(string) auth()->user()->profile?->department_id]))
                : [],
            'sites' => !$this->embed ? Launchpad::ssoSites() : [],
            'meter' => !$this->embed && $this->openId === null && $this->activeTab === 'list' ? app(LaunchpadPresenter::class)->capMeter((string) auth()->user()->profile?->department_id) : [],
            'capRules' => !$this->embed && $this->openId === null && in_array($this->activeTab, ['create', 'compact'], true) ? app(LaunchpadPresenter::class)->capRules() : [],
            'yearFloors' => $this->editOpen ? ['editFrom' => $this->yearFloor('editFrom'), 'editTo' => $this->yearFloor('editTo'), 'editExpiry' => $this->yearFloor('editExpiry')] : [],
            'filters' => ['all' => 'همه', 'mine' => 'برنامه‌های من', 'shared' => 'به‌اشتراک‌گذاشته‌شده با من', 'department' => 'دپارتمان من'],
            'statusFilters' => ['all' => 'همه', 'active' => 'فعال', 'inactive' => 'غیرفعال'],
            'typeFilters' => ['all' => 'همه', 'static' => 'برنامه‌های آماده', 'link' => 'سامانه‌های متصل'],
            'unseenFilters' => ['all' => 'همه', 'unseen' => 'هنوز باز نشده'],
        ]);
    }

    private function changeOptions(int $id, bool $add): bool
    {
        $user = auth()->user();

        return DB::transaction(function () use ($user, $id, $add): bool {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail(['id', 'extra']);
            $ids = Launchpad::optionIds($locked);
            if (in_array($id, $ids, true) === $add) {
                return false;
            }
            $ids = $add ? [...$ids, $id] : array_diff($ids, [$id]);
            $existing = Launchpad::query()->whereIn('id', $ids)->pluck('id')->map(fn ($key) => (int) $key)->all();
            $locked->setExtraValue('preferences.' . Launchpad::OPTIONS_PREFERENCE, array_slice(array_values(array_intersect($ids, $existing)), -max(1, (int) config('launchpad.workspace.options_max'))));
            $locked->save();
            $user->extra = $locked->extra;

            return true;
        });
    }

    private function ssoLink(Launchpad $row): bool
    {
        return $row->kind === 'link' && isset(Launchpad::ssoSites()[(string) ($row->settings['site'] ?? '')]);
    }

    private function scoped(): Builder
    {
        $user = auth()->user();
        $dept = (string) $user->profile?->department_id;

        return Launchpad::query()->visibleTo($user)
            ->when($this->filter === 'mine', fn (Builder $q) => $q->where('user_id', $user->id))
            ->when($this->filter === 'shared', fn (Builder $q) => $q->where('user_id', '!=', $user->id)->where(fn (Builder $a) => $a->whereJsonContains('audience', (int) $user->id)->orWhere('settings->public', true)))
            ->when($this->filter === 'department', fn (Builder $q) => $q->where('department_id', $dept))
            ->when($this->search !== '', fn (Builder $q) => $q->whereRaw('INSTR(title, ?) > 0', [$this->search]));
    }

    private function liveCondition(): array
    {
        $today = today()->toDateString();

        return ['active = 1 AND (accessible_from IS NULL OR accessible_from <= ?) AND (accessible_to IS NULL OR accessible_to >= ?) AND (expire_at IS NULL OR expire_at >= ?)', [$today, $today, $today]];
    }

    private function persist(bool $replace): void
    {
        try {
            $saved = app(CreateLaunchpadAction::class)->execute($this->form, auth()->user(), $replace)['launchpad'];
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'warning');
            return;
        } catch (QueryException) {
            $this->dispatch('toast', message: 'ذخیرهٔ برنامه انجام نشد. دوباره تلاش کنید؛ اگر مشکل ادامه داشت، به پشتیبانی اطلاع دهید.', type: 'warning');
            return;
        }
        $this->form->reset();
        $this->form->department_id = (string) auth()->user()->profile?->department_id;
        $this->precheck = [];
        $this->reset('fromYear', 'fromMonth', 'fromDay', 'toYear', 'toMonth', 'toDay', 'expiryYear', 'expiryMonth', 'expiryDay');
        if ($this->activeTab === 'compact') {
            if ($saved->kind === 'static') {
                $this->open((int) $saved->id);
            }
        } else {
            $this->activeTab = 'list';
        }
        $this->dispatch('toast', message: 'برنامه ذخیره شد.', type: 'success');
    }

    private function resetEdit(): void
    {
        if ($this->editId === null && !$this->editOpen) {
            return;
        }
        $this->editForm->reset();
        $this->resetErrorBag();
        $this->reset('editId', 'editOpen', 'editContext', 'editLoaded', 'editFromYear', 'editFromMonth', 'editFromDay', 'editToYear', 'editToMonth', 'editToDay', 'editExpiryYear', 'editExpiryMonth', 'editExpiryDay');
    }

    private function fillEditDate(string $prefix, ?Carbon $date): void
    {
        $jalali = $date === null ? null : Jalalian::fromCarbon($date);
        $this->{$prefix . 'Year'} = $jalali === null ? '' : (string) $jalali->getYear();
        $this->{$prefix . 'Month'} = $jalali === null ? '' : (string) $jalali->getMonth();
        $this->{$prefix . 'Day'} = $jalali === null ? '' : (string) $jalali->getDay();
        $this->editLoaded[$prefix] = [$this->{$prefix . 'Year'} . '-' . $this->{$prefix . 'Month'} . '-' . $this->{$prefix . 'Day'}, $date?->toDateString()];
    }

    private function editDate(string $prefix): ?string
    {
        [$loaded, $stored] = $this->editLoaded[$prefix] ?? ['', null];

        return $this->{$prefix . 'Year'} . '-' . $this->{$prefix . 'Month'} . '-' . $this->{$prefix . 'Day'} === $loaded ? $stored : $this->date($prefix);
    }

    private function yearFloor(string $prefix): int
    {
        $loaded = (int) explode('-', (string) ($this->editLoaded[$prefix][0] ?? ''))[0];

        return $loaded > 0 ? min($loaded, jNow()) : jNow();
    }

    private function date(string $prefix): ?string
    {
        $year = $this->{$prefix . 'Year'};
        $month = $this->{$prefix . 'Month'};
        $day = $this->{$prefix . 'Day'};
        if ($year === '' && $month === '' && $day === '') {
            return null;
        }
        if (!ctype_digit($year) || !ctype_digit($month) || !ctype_digit($day)
            || (int) $year < $this->yearFloor($prefix) || (int) $year > jNow() + 5
            || !CalendarUtils::checkDate((int) $year, (int) $month, (int) $day, true)) {
            throw ValidationException::withMessages([$prefix . 'Year' => 'روز، ماه و سال را کامل و به شمسی وارد کنید. تاریخ باید معتبر و سال آن بین ' . convertToPersian((string) jNow()) . ' تا ' . convertToPersian((string) (jNow() + 5)) . ' باشد.']);
        }

        return (new Jalalian((int) $year, (int) $month, (int) $day))->toCarbon()->format('Y-m-d');
    }
}
