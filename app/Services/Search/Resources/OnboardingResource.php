<?php

namespace App\Services\Search\Resources;

use App\Models\Onboarding;
use App\Services\Search\Contracts\SearchResource;
use Illuminate\Database\Eloquent\Builder;

class OnboardingResource extends SearchResource
{
    protected string $type = 'onboarding';
    protected string $group = 'آنبوردینگ';
    protected string $icon = 'apartment';
    protected string $model = Onboarding::class;
    protected array $columns = ['welcome', 'mission', 'vision'];
    protected ?string $subtitleField = 'mission';

    public function action($row): string
    {
        return $this->url('/profile?tab=onboarding');
    }

    protected function scope(Builder $query): void
    {
        $query->where('is_active', 1);
    }

    protected function titleFor($row): string
    {
        return superClean((string) $row->welcome, 40) ?: 'آنبوردینگ';
    }
}
