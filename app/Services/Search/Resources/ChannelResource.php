<?php

namespace App\Services\Search\Resources;

use App\Models\Channel;
use App\Services\Search\Contracts\SearchResource;
use Illuminate\Database\Eloquent\Builder;

class ChannelResource extends SearchResource
{
    protected string $type = 'channel';
    protected string $group = 'کانال‌ها';
    protected string $icon = 'campaign';
    protected string $model = Channel::class;
    protected array $columns = ['name', 'description'];
    protected ?string $titleField = 'name';
    protected ?string $subtitleField = 'description';

    public function action($row): string
    {
        return $this->route('channels', $row->getKey());
    }

    protected function scope(Builder $query): void
    {
        $query->whereHas('memberUsers', fn (Builder $q) => $q->where('users.id', $this->me()));
    }
}