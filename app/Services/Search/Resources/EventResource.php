<?php

namespace App\Services\Search\Resources;

use App\Models\Event;
use App\Services\Search\Contracts\SearchResource;
use Illuminate\Database\Eloquent\Builder;

class EventResource extends SearchResource
{
    protected string $type = 'event';
    protected string $group = 'تقویم و رویدادها';
    protected string $icon = 'calendar_month';
    protected string $model = Event::class;
    protected array $columns = ['title', 'description'];
    protected ?string $titleField = 'title';
    protected ?string $subtitleField = 'description';
    protected string $orderBy = 'date';

    public function action($row): string
    {
        return $this->tab('calendar', $row->getKey());
    }

    protected function scope(Builder $query): void
    {
        $me = $this->me();

        $query->withoutOffModuleSyncs()->where(fn (Builder $q) => $q
            ->where('user_id', $me)
            ->orWhere('private', false)
            ->orWhereHas('shares', fn (Builder $shared) => $shared->where('user_id', $me))
        );
    }
}
