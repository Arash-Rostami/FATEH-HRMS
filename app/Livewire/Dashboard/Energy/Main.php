<?php

namespace App\Livewire\Dashboard\Energy;

use App\Livewire\Dashboard\Energy\Actions\SubmitTestAction;
use App\Livewire\Dashboard\Energy\Presentation\EnergyPresenter;
use App\Models\EnergyTest as EnergyModel;
use App\Services\EnergyQuestionService;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Main extends Component
{
    public int $step = 1;
    public int $totalSteps = 0;
    public int $monthIndex = 0;
    public bool $canProceed = false;
    public bool $canSubmit = false;
    public bool $showSurvey = true;
    public float $progress = 0;
    public array $questions = [];
    public array $prompts = [];
    public array $sections = [];
    public array $answers = [];
    public string $activeTab = 'survey';

    public function mount(): void
    {
        $this->showSurvey = EnergyModel::canSubmit(auth()->id());

        if (!$this->showSurvey) return;

        $this->fill(app(EnergyQuestionService::class)->getQuestions());

        $this->totalSteps = count($this->questions);
        $this->answers = array_map(fn($qs) => array_fill(0, count($qs), false), $this->questions);

        $this->updateState();
    }

    public function nextStep(): void
    {
        if ($this->step < $this->totalSteps) {
            $this->step++;
            $this->updateState();
        }
    }

    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
            $this->updateState();
        }
    }

    public function submitTest(SubmitTestAction $action): void
    {
        try {
            $action->execute($this->answers, $this->questions, $this->monthIndex);
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
            return;
        }

        $this->showSurvey = false;
        $this->dispatch('toast', message: 'پاسخ با موفقیت ذخیره شد.', type: 'success');
        $this->dispatch('energy-test-submitted')->to('dashboard.energy.chart');
    }

    public function switchTab(string $tabId): void
    {
        $this->activeTab = $tabId;
    }

    public function updatedAnswers(mixed $value, string $name): void
    {
        if (!$value) {
            $this->updateState();
            return;
        }

        [$category, $index] = explode('.', $name);
        $index = (int) $index;
        $count = count($this->questions[$category]);

        $uncheckIndices = $index === $count - 1 ? range(0, $count - 2) : [$count - 1];
        foreach ($uncheckIndices as $i) {
            $this->answers[$category][$i] = false;
        }

        $this->updateState();
    }

    #[Computed]
    public function userTestCount(): int
    {
        return EnergyModel::where('user_id', auth()->id())->count();
    }

    public function render(): View
    {
        return view('livewire.dashboard.energy', [
            'categoryKeys' => array_keys($this->questions),
            'presenter' => new EnergyPresenter(),
        ]);
    }

    private function updateState(): void
    {
        $keys = array_keys($this->questions);

        if (empty($keys) || $this->step < 1 || $this->step > count($keys)) {
            $this->canProceed = false;
            $this->canSubmit = false;
            $this->progress = 0;
            return;
        }

        $currentCategory = $keys[$this->step - 1];

        $this->canProceed = in_array(true, $this->answers[$currentCategory] ?? [], strict: true);

        $this->canSubmit = $this->step === $this->totalSteps
            && collect($this->questions)->every(fn($qs, $cat) => in_array(true, $this->answers[$cat] ?? [], strict: true));

        $this->progress = (($this->step - 1) / $this->totalSteps) * 100;
    }
}
