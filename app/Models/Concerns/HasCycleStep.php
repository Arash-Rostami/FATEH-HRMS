<?php

namespace App\Models\Concerns;

use App\Services\ProjectTask\CycleLinkResolver;
use Illuminate\Support\Collection;

trait HasCycleStep
{
    private ?array $cycleLink = null;

    private bool $cycleLinkLoaded = false;

    public function setCycleLink(?array $link): void
    {
        $this->cycleLink = $link;
        $this->cycleLinkLoaded = true;
    }

    public function cycleStep(): ?array
    {
        if (!$this->cycleLinkLoaded) {
            $this->setCycleLink($this->project_id && $this->exists ? app(CycleLinkResolver::class)->resolve(new Collection([$this]))[$this->id] ?? null : null);
        }

        return $this->cycleLink;
    }

    public function isCycleLocked(): bool
    {
        return $this->cycleStep()['is_current'] ?? false;
    }
}