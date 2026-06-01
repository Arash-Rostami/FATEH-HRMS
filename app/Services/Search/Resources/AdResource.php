<?php

namespace App\Services\Search\Resources;

use App\Models\Ad;
use App\Services\Search\Contracts\SearchResource;
use Illuminate\Database\Eloquent\Builder;

class AdResource extends SearchResource
{
    protected string $type = 'ad';
    protected string $group = 'فرصت‌های شغلی';
    protected string $icon = 'work';
    protected string $model = Ad::class;
    protected array $columns = ['position', 'skill', 'experience'];
    protected ?string $titleField = 'position';
    protected ?string $subtitleField = 'skill';

    public function action($row): string
    {
        return $this->route('ads', $row->getKey());
    }

    protected function scope(Builder $query): void
    {
        $query->where('active', 1);
    }
}
