<?php

namespace App\Services\Search\Resources;

use App\Models\Resource;
use App\Services\Search\Contracts\SearchResource;
use Illuminate\Database\Eloquent\Builder;

class ReservationResource extends SearchResource
{
    protected string $type = 'reservation';
    protected string $group = 'رزرو فضا و منابع';
    protected string $icon = 'meeting_room';
    protected string $model = Resource::class;
    protected array $columns = ['name', 'type'];
    protected ?string $titleField = 'name';
    protected ?string $subtitleField = 'type';

    public function action($row): string
    {
        return $this->route('reservation', $row->getKey());
    }

    protected function scope(Builder $query): void
    {
        $query->where('status', 'active');
    }
}
