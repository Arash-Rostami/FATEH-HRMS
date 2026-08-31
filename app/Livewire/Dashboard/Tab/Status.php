<?php

namespace App\Livewire\Dashboard\Tab;

use App\Enums\PresenceStatus;
use App\Enums\SkillRequestStatus;
use App\Jobs\LogMissingSkillJob;
use App\Livewire\Dashboard\Profile\Actions\EndorseSkillAction;
use App\Livewire\Dashboard\Profile\Actions\RevokeEndorsementAction;
use App\Livewire\Dashboard\Profile\Presentation\SkillPresenter;
use App\Livewire\Dashboard\Profile\Presentation\SkillUserPresenter;
use App\Livewire\Dashboard\Tab\Actions\SendSmsAction;
use App\Livewire\Dashboard\Tab\Presentation\StatusPresenter;
use App\Models\Profile;
use App\Models\Skill;
use App\Models\SkillUser;
use App\Models\User;
use App\Services\SmsService;
use App\Services\User\UserKeyGrouper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Isolate]
#[Lazy]
class Status extends Component
{
    public string $activeFilter = 'all';
    public string $activeClassifier = 'all';
    public string $search = '';
    public string $view = 'grid';
    public $showAboutModal = false;

    public ?int $skillId = null;
    public string $skillSearch = '';
    public bool $mentorOnly = false;
    public array $loggedSkillSearches = [];
    public ?int $aboutMeUserId = null;

    private ?array $classifierGroupsRaw = null;

    public function mount(): void
    {
        $this->view = session('status_view_mode', 'grid');

        $skillId = (int) request()->query('skill', 0);

        if ($skillId > 0 && Skill::activeCatalog()->whereKey($skillId)->exists()) {
            $this->skillId = $skillId;
        }
    }

    public function render()
    {
        $skillUserPresenter = new SkillUserPresenter();
        $skillUserPresenter->preloadEndorsers($this->aboutMeSkills);

        return view('livewire.dashboard.tab.status', [
            'skillUserPresenter' => $skillUserPresenter,
            'skillPresenter' => new SkillPresenter(),
            'statusPresenter' => new StatusPresenter(),
        ]);
    }

    public function placeholder(): \Illuminate\View\View
    {
        return view('livewire.dashboard.tab.status.placeholder');
    }

    public function sendSms(string $userId, SendSmsAction $action, SmsService $smsService)
    {
        $result = $action->execute($userId, $smsService);
        $this->dispatch('toast', message: $result['message'], type: $result['type']);
    }

    public function setFilter(string $filter)
    {
        $this->activeFilter = $this->activeFilter === $filter ? 'all' : $filter;
    }

    public function toggleView(string $view): void
    {
        if (!in_array($view, ['grid', 'chart'], true)) {
            return;
        }

        $this->view = $view;
        session(['status_view_mode' => $view]);
    }

    public function selectSkill(int $skillId): void
    {
        $this->skillId = $skillId;
        $this->skillSearch = '';
        $this->resetSkillComputeds();
    }

    public function clearSkillFilter(): void
    {
        $this->skillId = null;
        $this->skillSearch = '';
        $this->mentorOnly = false;
        $this->resetSkillComputeds();
    }

    public function searchSkill(): void
    {
        $term = $this->normalizedSkillSearch();

        $this->skillId = null;
        $this->resetSkillComputeds();

        if ($term === '' || in_array($term, $this->loggedSkillSearches, true)) {
            return;
        }

        if ($this->skillCandidates->isNotEmpty()) {
            return;
        }

        $this->loggedSkillSearches[] = $term;
        LogMissingSkillJob::dispatch($term);
    }

    public function openAboutMe(int $userId): void
    {
        if (!User::query()->visibleOnBoard()->whereKey($userId)->exists()) {
            return;
        }

        $this->aboutMeUserId = $userId;
        $this->showAboutModal = true;

        unset($this->aboutMeSkills, $this->aboutMeDirectReports);
    }

    public function endorse(int $skillUserId, EndorseSkillAction $action): void
    {
        $this->runSkillAction(
            $skillUserId,
            fn (SkillUser $su, $user) => $action->execute($su, $user),
            'مهارت با موفقیت تأیید شد.'
        );
    }

    public function revokeEndorsement(int $skillUserId, RevokeEndorsementAction $action): void
    {
        $this->runSkillAction(
            $skillUserId,
            fn (SkillUser $su, $user) => $action->execute($su, $user),
            'تأیید شما لغو شد.'
        );
    }

    private function runSkillAction(int $skillUserId, callable $action, string $successMessage): void
    {
        try {
            $action(SkillUser::findOrFail($skillUserId), Auth::user());

            unset($this->aboutMeSkills);

            $this->dispatch('toast', message: $successMessage, type: 'success');
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            report($e);

            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    private function resetSkillComputeds(): void
    {
        unset($this->skillCandidates, $this->selectedSkill);
    }

    private function normalizedSkillSearch(): string
    {
        return trim(preg_replace('/\s+/', ' ', $this->skillSearch));
    }

    #[Computed]
    public function skillCandidates()
    {
        $term = $this->normalizedSkillSearch();

        if ($term === '' || $this->skillId !== null) {
            return collect();
        }

        return Skill::activeCatalog()
            ->where(fn (Builder $q) => $q->where('name', 'like', "%{$term}%")->orWhere('name_en', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function selectedSkill(): ?Skill
    {
        return $this->skillId !== null ? Skill::activeCatalog()->find($this->skillId) : null;
    }

    #[Computed]
    public function aboutMeSkills()
    {
        if ($this->aboutMeUserId === null) {
            return collect();
        }

        return SkillUser::with(['skill', 'user'])
            ->where('user_id', $this->aboutMeUserId)
            ->where('status', SkillRequestStatus::Approved)
            ->where('is_private', false)
            ->get();
    }

    #[Computed]
    public function classifierGroups(): array
    {
        return collect($this->rawClassifierGroups())
            ->reject(fn (array $g) => UserKeyGrouper::isHidden($g['norm']))
            ->mapWithKeys(fn (array $g) => [
                $g['norm'] => [
                    'label' => $g['label'],
                    'values' => UserKeyGrouper::distinctValues($g['variants']),
                ],
            ])
            ->all();
    }

    #[Computed]
    public function todaysOccasions(): array
    {
        return $this->users
            ->map(fn (User $user) => ['user' => $user, 'type' => $user->profile?->todaysOccasionType()])
            ->filter(fn (array $o) => $o['type'] !== null)
            ->map(fn (array $o) => ['user' => $o['user'], 'type' => $o['type'], 'tone' => Profile::occasionTone($o['type'])])
            ->values()
            ->all();
    }

    #[Computed]
    public function stats()
    {
        $counts = User::query()
            ->visibleOnBoard()
            ->when($this->search !== '', fn (Builder $query) => $query->search($this->search))
            ->when($this->activeClassifier !== 'all', fn (Builder $query) => $this->applyClassifier($query))
            ->selectRaw('presence, count(*) as count')
            ->toBase()
            ->groupBy('presence')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->presence => $row->count])
            ->all();

        return collect(PresenceStatus::cases())
            ->mapWithKeys(fn (PresenceStatus $s) => [$s->value => $counts[$s->value] ?? 0])
            ->all();
    }

    #[Computed]
    public function users()
    {
        User::primeTodaysDeskCache();

        $skillId = $this->skillId;
        $mentorOnly = $this->mentorOnly;

        return User::query()
            ->with(['profile.department', 'profile.details' => fn ($q) => $q->whereIn('key', ['unit', 'section', 'display_title'])])
            ->visibleOnBoard()
            ->when($this->activeFilter !== 'all', fn (Builder $query) => $query->where('presence', $this->activeFilter))
            ->when($this->activeClassifier !== 'all', fn (Builder $query) => $this->applyClassifier($query))
            ->when($this->search !== '', fn (Builder $query) => $query->search($this->search))
            ->when(
                $skillId !== null,
                fn (Builder $query) => $query
                    ->join('skill_user as su', function ($join) use ($skillId, $mentorOnly) {
                        $join->on('su.user_id', '=', 'users.id')
                            ->where('su.skill_id', '=', $skillId)
                            ->where('su.status', '=', SkillRequestStatus::Approved->value)
                            ->where('su.is_private', '=', false)
                            ->when($mentorOnly, fn ($j) => $j->where('su.is_mentoring', '=', true));
                    })
                    ->addSelect([
                        'users.*',
                        'su.endorsements_count as skill_tier_endorsements_count',
                        'su.last_used_at as skill_tier_last_used_at',
                    ])
                    ->orderByRaw('(su.endorsements_count >= ?) DESC', [SkillUser::ENDORSEMENT_SATURATION_CAP])
                    ->orderByRaw('(su.last_used_at IS NOT NULL AND su.last_used_at >= ?) DESC', [now()->subDays(SkillUser::ACTIVE_WINDOW_DAYS)])
                    ->orderByRaw('LEAST(su.endorsements_count, ?) DESC', [SkillUser::ENDORSEMENT_SATURATION_CAP])
                    ->orderByDesc('su.last_used_at'),
                function (Builder $query) {
                    $rankOrder = implode("','", array_reverse(array_keys(User::RANKS)));

                    $query->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')
                        ->select('users.*')
                        ->orderByRaw("FIELD(profiles.position, '{$rankOrder}') DESC")
                        ->orderByDesc('users.last_seen');
                },
            )
            ->get();
    }

    #[Computed]
    public function orgTree(): array
    {
        return (new StatusPresenter())->orgTree($this->users);
    }

    #[Computed]
    public function orgViewData(): array
    {
        return (new StatusPresenter())->chartData($this->orgTree, $this->users);
    }

    #[Computed]
    public function aboutMeDirectReports()
    {
        if ($this->aboutMeUserId === null) {
            return collect();
        }

        $me = $this->users->firstWhere('id', $this->aboutMeUserId);

        if ($me === null) {
            return collect();
        }

        return (new StatusPresenter())->directReports($this->orgTree, $me);
    }

    private function applyClassifier(Builder $query): Builder
    {
        [$norm, $value] = array_pad(explode('|', $this->activeClassifier, 2), 2, '');

        if (UserKeyGrouper::isHidden($norm)) {
            return $query;
        }

        $group = collect($this->rawClassifierGroups())->firstWhere('norm', $norm);

        return $group ? UserKeyGrouper::applyFilter($query, $group['variants'], $value) : $query;
    }

    private function rawClassifierGroups(): array
    {
        if ($this->classifierGroupsRaw === null) {
            $this->classifierGroupsRaw = collect(UserKeyGrouper::map())->all();
        }

        return $this->classifierGroupsRaw;
    }
}
