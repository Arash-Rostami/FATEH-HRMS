<?php

namespace App\Services\Search\Resources;

use App\Models\User;
use App\Services\Search\Contracts\SearchResource;
use Illuminate\Database\Eloquent\Builder;

class PeopleResource extends SearchResource
{
    protected string $type = 'people';
    protected string $group = 'همکاران';
    protected string $icon = 'badge';
    protected string $model = User::class;
    protected array $columns = ['name', 'email'];
    protected ?string $titleField = 'name';
    protected ?string $subtitleField = 'email';

    public function action($row): string
    {
        return $this->route('contact', $row->getKey());
    }

    protected function scope(Builder $query): void
    {
        $query->whereKeyNot($this->me());
    }
}
