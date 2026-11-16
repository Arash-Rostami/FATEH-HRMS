<?php

namespace App\Livewire\Dashboard\Launchpad;

use App\Livewire\Dashboard\Launchpad\Actions\CreateLaunchpadAction;
use App\Livewire\Dashboard\Launchpad\Actions\DeleteLaunchpadAction;
use App\Livewire\Dashboard\Launchpad\Forms\LaunchpadForm;
use App\Livewire\Dashboard\Launchpad\Presentation\LaunchpadPresenter;
use App\Models\Department;
use App\Models\Launchpad;
use App\Models\User;
use App\Support\LaunchpadAccessPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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
    use WithFileUploads {
        _finishUpload as private finishLivewireUpload;
    }

    public function _finishUpload($name, $tmpPath, $isMultiple, $append = true): void
    {
        $this->finishLivewireUpload($name, $tmpPath, $isMultiple, $name === 'form.files' ? false : $append);
    }

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
    public int $listLimit = 30;

    #[Locked]
    public array $precheck = [];

    public bool $replaceOpen = false;

    public string $fromYear = '';
    public string $fromMonth = '';
    public string $fromDay = '';
    public string $toYear = '';
    public string $toMonth = '';
    public string $toDay = '';
    public string $expiryYear = '';
    public string $expiryMonth = '';
    public string $expiryDay = '';

    public function mount(): void
    {
        $this->activeTab = in_array($this->activeTab, ['list', 'create'], true) ? $this->activeTab : 'list';
        $this->filter = in_array($this->filter, ['all', 'mine', 'shared', 'department'], true) ? $this->filter : 'all';
        $this->search = mb_substr(trim($this->search), 0, 100);
        $this->form->department_id = (string) auth()->user()->profile?->department_id;
        if ($this->openId !== null) {
            $this->open($this->openId);
        }
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, ['list', 'create'], true)) {
            $this->activeTab = $tab;
            $this->close();
        }
    }

    public function setFilter(string $filter): void
    {
        if (in_array($filter, ['all', 'mine', 'shared', 'department'], true)) {
            $this->filter = $filter;
            $this->listLimit = 30;
        }
    }

    public function updatedSearch(): void
    {
        $this->search = mb_substr(trim($this->search), 0, 100);
        $this->listLimit = 30;
    }

    public function loadMore(): void
    {
        $this->listLimit += 30;
    }

    public function open(int $id): void
    {
        $row = Launchpad::query()->find($id);
        $user = auth()->user();
        if ($row === null || !LaunchpadAccessPolicy::canView($row, $user)) {
            $this->openId = null;
            $this->viewToken = null;
            $this->unavailable = 'این برنامه در دسترس نیست.';
            if (!$this->embed) {
                $this->dispatch('toast', message: $this->unavailable, type: 'warning');
                $this->unavailable = null;
            }
            return;
        }
        $this->openId = (int) $row->id;
        $this->viewToken = LaunchpadAccessPolicy::token($row, $user);
        $this->unavailable = null;
        $row->markSeenBy((int) $user->id);
    }

    public function close(): void
    {
        $this->openId = null;
        $this->viewToken = null;
        $this->unavailable = null;
    }

    public function updatedFormFiles(): void
    {
        $this->precheck = app(LaunchpadPresenter::class)->precheck($this->form->precheck());
    }

    public function addDepartment(): void
    {
        $ids = User::query()->visibleOnBoard()->whereHas('profile', fn (Builder $q) => $q->where('department_id', $this->form->department_id))->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->form->audience = array_values(array_unique(array_merge($this->form->audience, $ids)));
    }

    public function save(): void
    {
        $this->form->accessible_from = $this->date('from');
        $this->form->accessible_to = $this->date('to');
        $this->form->expire_at = $this->date('expiry');
        $this->form->title = $this->form->normalizedTitle();
        $this->form->validate();
        if (($this->precheck['ok'] ?? false) !== true) {
            throw ValidationException::withMessages(['form.files' => 'فایل‌های انتخاب‌شده معتبر نیستند.']);
        }
        $title = $this->form->title;
        $existing = Launchpad::query()->where('department_id', $this->form->department_id)->where('title', $title)->first();
        if ($existing !== null && LaunchpadAccessPolicy::canManage($existing, auth()->user())) {
            $this->replaceOpen = true;
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
        $row = Launchpad::query()->find($id);
        if ($row === null || !LaunchpadAccessPolicy::canManage($row, auth()->user())) {
            $this->dispatch('toast', message: 'حذف این برنامه مجاز نیست.', type: 'warning');
            return;
        }
        try {
            app(DeleteLaunchpadAction::class)->execute($row, auth()->user());
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: 'حذف این برنامه مجاز نیست.', type: 'warning');
            return;
        }
        $this->dispatch('toast', message: 'برنامه حذف شد.', type: 'success');
    }

    #[Computed]
    public function rows(): Collection
    {
        $user = auth()->user();
        $dept = (string) $user->profile?->department_id;
        return Launchpad::query()->visibleTo($user)->select(['id', 'title', 'department_id', 'user_id', 'active', 'audience', 'accessible_from', 'accessible_to', 'expire_at'])->with('user:id,name')
            ->when($this->filter === 'mine', fn (Builder $q) => $q->where('user_id', $user->id))
            ->when($this->filter === 'shared', fn (Builder $q) => $q->where('user_id', '!=', $user->id)->whereJsonContains('audience', (int) $user->id))
            ->when($this->filter === 'department', fn (Builder $q) => $q->where('department_id', $dept))
            ->when($this->search !== '', fn (Builder $q) => $q->whereRaw('INSTR(title, ?) > 0', [$this->search]))
            ->latest('id')->limit($this->listLimit + 1)->get();
    }

    #[Computed]
    public function candidates(): array
    {
        return User::getCachedActiveOptions()->except(auth()->id())->map(fn ($name, $id) => ['id' => (int) $id, 'name' => $name])->values()->all();
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
            'departments' => !$this->embed && $this->openId === null && $this->activeTab === 'create'
                ? (auth()->user()->hasElevatedRole() ? Department::getCachedOptions() : Department::getCachedOptions()->only([(string) auth()->user()->profile?->department_id]))
                : [],
            'meter' => !$this->embed && $this->openId === null && $this->activeTab === 'list' ? app(LaunchpadPresenter::class)->capMeter((string) auth()->user()->profile?->department_id) : [],
            'filters' => ['all' => 'همه', 'mine' => 'برنامه‌های من', 'shared' => 'اشتراک با من', 'department' => 'بخش من'],
        ]);
    }

    private function persist(bool $replace): void
    {
        try {
            app(CreateLaunchpadAction::class)->execute($this->form, auth()->user(), $replace);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'warning');
            return;
        } finally {
            $this->replaceOpen = false;
        }
        $this->form->reset();
        $this->form->department_id = (string) auth()->user()->profile?->department_id;
        $this->precheck = [];
        $this->reset('fromYear', 'fromMonth', 'fromDay', 'toYear', 'toMonth', 'toDay', 'expiryYear', 'expiryMonth', 'expiryDay');
        $this->activeTab = 'list';
        $this->dispatch('toast', message: 'برنامه ذخیره شد.', type: 'success');
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
            || (int) $year < 1300 || (int) $year > 1410
            || !CalendarUtils::checkDate((int) $year, (int) $month, (int) $day, true)) {
            throw ValidationException::withMessages([$prefix . 'Year' => 'تاریخ را به‌درستی وارد کنید.']);
        }

        return (new Jalalian((int) $year, (int) $month, (int) $day))->toCarbon()->format('Y-m-d');
    }
}
