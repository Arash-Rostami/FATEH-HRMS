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
use App\Models\Skill;
use App\Models\SkillUser;
use App\Models\User;
use App\Services\SmsService;
use App\Services\User\UserKeyGrouper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Status extends Component
{
    public string $activeFilter = 'all';
    public string $activeClassifier = 'all';
    public string $search = '';
    public $showAboutModal = false;

    public ?int $skillId = null;
    public string $skillSearch = '';
    public bool $mentorOnly = false;
    public array $loggedSkillSearches = [];
    public ?int $aboutMeUserId = null;

    public function mount(): void
    {
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
        ]);
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

    public function selectSkill(int $skillId): void
    {
        $this->skillId = $skillId;
        $this->skillSearch = '';
        unset($this->skillCandidates, $this->selectedSkill);
    }

    public function clearSkillFilter(): void
    {
        $this->skillId = null;
        $this->skillSearch = '';
        $this->mentorOnly = false;
        unset($this->skillCandidates, $this->selectedSkill);
    }

    public function searchSkill(): void
    {
        $term = $this->normalizedSkillSearch();
        $this->skillId = null;
        unset($this->skillCandidates, $this->selectedSkill);

        if ($term === '' || $this->skillCandidates->isNotEmpty() || in_array($term, $this->loggedSkillSearches, true)) {
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
        unset($this->aboutMeSkills);
    }

    public function endorse(int $skillUserId, EndorseSkillAction $action): void
    {
        try {
            $action->execute(SkillUser::findOrFail($skillUserId), Auth::user());
            unset($this->aboutMeSkills);
            $this->dispatch('toast', message: 'مهارت با موفقیت تأیید شد.', type: 'success');
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            report($e);
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function revokeEndorsement(int $skillUserId, RevokeEndorsementAction $action): void
    {
        try {
            $action->execute(SkillUser::findOrFail($skillUserId), Auth::user());
            unset($this->aboutMeSkills);
            $this->dispatch('toast', message: 'تأیید شما لغو شد.', type: 'success');
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            report($e);
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
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
        return collect(UserKeyGrouper::map())
            ->reject(fn(array $g) => UserKeyGrouper::isHidden($g['norm']))
            ->mapWithKeys(fn(array $g) => [
                $g['norm'] => [
                    'label' => $g['label'],
                    'values' => UserKeyGrouper::distinctValues($g['variants']),
                ],
            ])
            ->all();
    }

    #[Computed]
    public function stats()
    {
        $counts = User::query()
            ->visibleOnBoard()
            ->when($this->search !== '', fn(Builder $query) => $query->search($this->search))
            ->when($this->activeClassifier !== 'all', fn(Builder $query) => $this->applyClassifier($query))
            ->selectRaw('presence, count(*) as count')
            ->toBase()
            ->groupBy('presence')
            ->get()
            ->mapWithKeys(fn($row) => [
                $row->presence => $row->count
            ])
            ->all();

        return collect(PresenceStatus::cases())
            ->mapWithKeys(fn(PresenceStatus $s) => [$s->value => $counts[$s->value] ?? 0])
            ->all();
    }

    #[Computed]
    public function users()
    {
        $rankOrder = implode("','", array_reverse(array_keys(User::RANKS)));

        User::primeTodaysDeskCache();

        $skillId = $this->skillId;
        $mentorOnly = $this->mentorOnly;

        return User::query()
            ->with(['profile.department', 'profile.details'])
            ->visibleOnBoard()
            ->when($this->activeFilter !== 'all', fn(Builder $query) => $query->where('presence', $this->activeFilter))
            ->when($this->activeClassifier !== 'all', fn(Builder $query) => $this->applyClassifier($query))
            ->when($this->search !== '', fn(Builder $query) => $query->search($this->search))
            ->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')
            ->select('users.*')
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
                        'su.endorsements_count as skill_tier_endorsements_count',
                        'su.last_used_at as skill_tier_last_used_at',
                    ])
                    ->orderByRaw('(su.endorsements_count >= ?) DESC', [SkillUser::ENDORSEMENT_SATURATION_CAP])
                    ->orderByRaw('(su.last_used_at IS NOT NULL AND su.last_used_at >= ?) DESC', [now()->subDays(SkillUser::ACTIVE_WINDOW_DAYS)])
                    ->orderByRaw('LEAST(su.endorsements_count, ?) DESC', [SkillUser::ENDORSEMENT_SATURATION_CAP])
                    ->orderByDesc('su.last_used_at'),
                fn (Builder $query) => $query
                    ->orderByRaw("FIELD(profiles.position, '{$rankOrder}') DESC")
                    ->orderByDesc('users.last_seen'),
            )
            ->get();
    }

    private function applyClassifier(Builder $query): Builder
    {
        [$norm, $value] = array_pad(explode('|', $this->activeClassifier, 2), 2, '');

        if (UserKeyGrouper::isHidden($norm)) {
            return $query;
        }

        $group = collect(UserKeyGrouper::map())->firstWhere('norm', $norm);

        return $group ? UserKeyGrouper::applyFilter($query, $group['variants'], $value) : $query;
    }
}
