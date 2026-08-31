<?php

namespace App\Livewire\Dashboard\Energy;

use App\Filament\Resources\UserResource\Enums\UserType;
use App\Livewire\Dashboard\Energy\Presentation\ChartPresenter;
use App\Models\EnergyTest;
use App\Models\User;
use App\Services\EnergyQuestionService;
use App\Services\PersolTeamService as TeamData;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Attributes\On;
use Livewire\Component;

#[Defer]
class Chart extends Component
{
    #[Computed]
    public function companyAverages(): array
    {
        $cutoff = now()->subMonths(18);

        return EnergyTest::query()
            ->join('users', 'users.id', '=', 'energy_tests.user_id')
            ->where('users.type', '!=', UserType::Guest->value)
            ->where('energy_tests.user_id', '!=', $this->user->id)
            ->where(fn($q) => $q->where('energy_tests.completed_at', '>=', $cutoff)->orWhere('energy_tests.created_at', '>=', $cutoff))
            ->selectRaw('
                COALESCE(AVG(mind_score), 0) as mind,
                COALESCE(AVG(emotion_score), 0) as emotion,
                COALESCE(AVG(physique_score), 0) as physique,
                COALESCE(AVG(soul_score), 0) as soul,
                COALESCE(AVG(overall_score), 0) as overall
            ')
            ->first()
            ->toArray();
    }

    #[Computed]
    public function history(): array
    {
        $cutoff = now()->subMonths(18);

        return EnergyTest::query()
            ->where('user_id', $this->user->id)
            ->where(fn($q) => $q->where('completed_at', '>=', $cutoff)->orWhere('created_at', '>=', $cutoff))
            ->orderBy('completed_at')
            ->selectRaw("
                DATE_FORMAT(completed_at, '%Y-%m-%d') as date,
                mind_score as mind,
                emotion_score as emotion,
                physique_score as physique,
                soul_score as soul,
                overall_score as overall
            ")
            ->get()
            ->toArray();
    }

    #[Computed]
    public function isManager(): bool
    {
        return $this->user->isDeptHead();
    }

    #[Computed]
    public function latestTest(): ?array
    {
        $test = EnergyTest::where('user_id', $this->user->id)
            ->latest('completed_at')
            ->first();

        return $test?->only(['mind_score', 'emotion_score', 'physique_score', 'soul_score', 'overall_score', 'completed_at']);
    }

    #[Computed]
    public function sections(): array
    {
        return app(EnergyQuestionService::class)->getQuestions()['sections'];
    }

    #[Computed]
    public function personalHistory(): array
    {
        $service = app(EnergyQuestionService::class);

        return EnergyTest::query()
            ->where('user_id', $this->user->id)
            ->orderByDesc('completed_at')
            ->limit(12)
            ->get()
            ->map(function (EnergyTest $test) use ($service) {
                $bank = $service->getQuestionsForMonth($test->month_index)['questions'];

                return [
                    'completed_at' => $test->completed_at,
                    'overall' => $test->overall_score,
                    'choices' => collect($test->answers ?? [])
                        ->mapWithKeys(function (array $flags, string $cat) use ($bank) {
                            $options = $bank[$cat] ?? [];
                            $chosen = collect($flags)->search(true, true);

                            return [$cat => is_int($chosen) ? ($options[$chosen] ?? null) : null];
                        })
                        ->all(),
                ];
            })
            ->all();
    }

    #[Computed]
    public function teamMembersData(): array
    {
        if (!$this->isManager || !$this->user->profile?->department_id) {
            return [];
        }

        $query = User::query()
            ->with(['profile:user_id,department_id,position', 'latestEnergyTest'])
            ->where('id', '!=', $this->user->id)
            ->where('status', 'active')
            ->whereHas('latestEnergyTest');

        resolve(TeamData::class)->applyRules($query, $this->user);

        return $query->get()
            ->map(fn(User $member) => [
                'name' => $member->name,
                'scores' => [
                    'physique' => $member->latestEnergyTest->physique_score,
                    'emotion' => $member->latestEnergyTest->emotion_score,
                    'mind' => $member->latestEnergyTest->mind_score,
                    'soul' => $member->latestEnergyTest->soul_score,
                    'overall' => $member->latestEnergyTest->overall_score,
                ],
            ])
            ->values()
            ->toArray();
    }

    #[Computed]
    public function user(): User
    {
        return auth()->user();
    }

    #[On('energy-test-submitted')]
    public function refreshAfterSubmit(): void
    {
        unset($this->history, $this->latestTest, $this->companyAverages, $this->teamMembersData, $this->personalHistory);
    }

    public function placeholder(): View
    {
        return view('livewire.dashboard.energy.chart-placeholder');
    }

    public function render()
    {
        return view('livewire.dashboard.energy.chart', [
            'isManager' => $this->isManager,
            'history' => $this->history,
            'latestTest' => $this->latestTest,
            'companyAverages' => $this->companyAverages,
            'teamMembersData' => $this->teamMembersData,
            'sections' => $this->sections,
            'personalHistory' => $this->personalHistory,
            'presenter' => new ChartPresenter(),
        ]);
    }
}
