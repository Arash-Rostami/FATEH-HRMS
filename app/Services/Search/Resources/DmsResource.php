<?php

namespace App\Services\Search\Resources;

use App\Models\DMS;
use App\Services\Search\Contracts\SearchResource;
use Illuminate\Database\Eloquent\Builder;

class DmsResource extends SearchResource
{
    protected string $type = 'dms';
    protected string $group = 'مدیریت اسناد';
    protected string $icon = 'folder_managed';
    protected string $model = DMS::class;
    protected array $columns = ['title', 'code'];
    protected ?string $titleField = 'title';
    protected ?string $subtitleField = 'code';

    public function action($row): string
    {
        return $this->route('dms', $row->getKey());
    }

    protected function scope(Builder $query): void
    {
        $query->visibleToUser();
    }
}
