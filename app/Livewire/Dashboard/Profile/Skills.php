<?php

namespace App\Livewire\Dashboard\Profile;

use App\Enums\SkillRequestStatus;
use App\Livewire\Dashboard\Profile\Actions\EndorseSkillAction;
use App\Livewire\Dashboard\Profile\Actions\MarkSkillUsedAction;
use App\Livewire\Dashboard\Profile\Actions\RequestSkillAction;
use App\Livewire\Dashboard\Profile\Actions\RevokeEndorsementAction;
use App\Livewire\Dashboard\Profile\Actions\ToggleMentoringAction;
use App\Livewire\Dashboard\Profile\Actions\ToggleSkillPrivacyAction;
use App\Livewire\Dashboard\Profile\Forms\SkillsForm;
use App\Livewire\Dashboard\Profile\Presentation\SkillPresenter;
use App\Livewire\Dashboard\Profile\Presentation\SkillUserPresenter;
use App\Models\Skill;
use App\Models\SkillUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Skills extends Component
{
    public SkillsForm $form;
    #[Locked]
    public ?int $markUsedId = null;
    public string $markUsedContext = '';

    public function mount(): void
    {
        $userId = (int) Auth::id();
        $notices = Cache::get("skill_notices:{$userId}", []);

        foreach ($notices as $key => $notice) {
            $this->dispatch('toast', message: $notice['body'], type: $notice['type'] ?? 'success');
            self::dismiss((int) Auth::id(), $key);
        }
    }


    public function render()
    {
        $skillUserPresenter = new SkillUserPresenter();
        $skillUserPresenter->preloadEndorsers($this->ownSkills);

        return view('livewire.dashboard.profile.skills', [
            'skillUserPresenter' => $skillUserPresenter,
            'skillPresenter' => new SkillPresenter(),
        ]);
    }

    #[Computed]
    public function ownSkills()
    {
        return SkillUser::with('skill')
            ->where('user_id', Auth::id())
            ->get()
            ->sortBy(fn (SkillUser $row) => $row->status !== SkillRequestStatus::Approved
                ? [1, match ($row->status) {
                    SkillRequestStatus::Pending => 0,
                    default => 1,
                }, -($row->created_at?->timestamp ?? 0), 0]
                : [0, match ($row->stateTier()->value) {
                    'endorsed' => 0,
                    'active' => 1,
                    default => 2,
                }, -min($row->endorsements_count, SkillUser::ENDORSEMENT_SATURATION_CAP), -($row->last_used_at?->timestamp ?? 0)])
            ->values();
    }

    #[Computed]
    public function catalog()
    {
        $presenter = new SkillPresenter();

        return Skill::activeCatalog()
            ->orderBy('category')
            ->orderBy('name')
            ->get(['id', 'name', 'name_en', 'category'])
            ->map(fn (Skill $skill) => [
                'value' => $skill->id,
                'label' => $skill->name_en ? "({$skill->name_en}) {$skill->name}" : $skill->name,
                'category' => $presenter->categoryLabel($skill),
            ]);
    }

    public function requestSkill(RequestSkillAction $action): void
    {
        try {
            $action->execute($this->form);
            $this->form->reset();
            unset($this->ownSkills);
            $this->dispatch('toast', message: 'درخواست مهارت ثبت شد و در انتظار تأیید است.', type: 'success');
        } catch (ModelNotFoundException|QueryException $e) {
            report($e);
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        } catch (\RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        } catch (\Exception $e) {
            report($e);
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function reRequest(int $skillUserId, RequestSkillAction $action): void
    {
        $row = SkillUser::findOrFail($skillUserId);

        if ($row->user_id !== Auth::id()) {
            abort(403);
        }

        if ($row->requested_name !== null) {
            $this->form->proposedName = $row->requested_name;
            $this->form->skillId = null;
        } else {
            $this->form->skillId = $row->skill_id;
            $this->form->proposedName = null;
        }

        $this->requestSkill($action);
    }

    public function openMarkUsed(int $skillUserId): void
    {
        $this->markUsedId = $skillUserId;
        $this->markUsedContext = '';
        $this->dispatch('open-modal', name: 'mark-skill-used-modal');
    }

    public function markUsed(MarkSkillUsedAction $action): void
    {
        $this->validate([
            'markUsedContext' => 'nullable|string|max:255',
        ], [
            'markUsedContext.max' => 'توضیحات نباید بیشتر از ۲۵۵ کاراکتر باشد.',
        ]);

        try {
            $action->execute(SkillUser::findOrFail($this->markUsedId), $this->markUsedContext ?: null);
            $this->markUsedId = null;
            $this->markUsedContext = '';
            unset($this->ownSkills);
            $this->dispatch('toast', message: 'زمان استفاده از مهارت ثبت شد.', type: 'success');
        } catch (HttpException $e) {
            throw $e;
        } catch (ModelNotFoundException $e) {
            report($e);
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        } catch (\RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        } catch (\Exception $e) {
            report($e);
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function togglePrivacy(int $skillUserId, ToggleSkillPrivacyAction $action): void
    {
        try {
            $action->execute(SkillUser::findOrFail($skillUserId));
            unset($this->ownSkills);
        } catch (HttpException $e) {
            throw $e;
        } catch (ModelNotFoundException $e) {
            report($e);
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        } catch (\RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        } catch (\Exception $e) {
            report($e);
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function toggleMentoring(int $skillUserId, ToggleMentoringAction $action): void
    {
        try {
            $action->execute(SkillUser::findOrFail($skillUserId));
            unset($this->ownSkills);
        } catch (HttpException $e) {
            throw $e;
        } catch (ModelNotFoundException $e) {
            report($e);
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        } catch (\RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        } catch (\Exception $e) {
            report($e);
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function endorse(int $skillUserId, EndorseSkillAction $action): void
    {
        try {
            $action->execute(SkillUser::findOrFail($skillUserId), Auth::user());
            unset($this->ownSkills);
            $this->dispatch('toast', message: 'مهارت با موفقیت تأیید شد.', type: 'success');
        } catch (HttpException $e) {
            throw $e;
        } catch (ModelNotFoundException $e) {
            report($e);
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        } catch (\RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        } catch (\Exception $e) {
            report($e);
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function revokeEndorsement(int $skillUserId, RevokeEndorsementAction $action): void
    {
        try {
            $action->execute(SkillUser::findOrFail($skillUserId), Auth::user());
            unset($this->ownSkills);
            $this->dispatch('toast', message: 'تأیید شما لغو شد.', type: 'success');
        } catch (HttpException $e) {
            throw $e;
        } catch (ModelNotFoundException $e) {
            report($e);
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        } catch (\RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        } catch (\Exception $e) {
            report($e);
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    private static function dismiss(int $userId, string $dedupeKey): void
    {
        Cache::lock("skill_notices_lock:{$userId}", 5)->block(2, function () use ($userId, $dedupeKey) {
            $key = "skill_notices:{$userId}";
            $list = Cache::get($key, []);
            unset($list[$dedupeKey]);
            Cache::put($key, $list, now()->addDays(7));
        });
    }
}
