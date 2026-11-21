<?php

namespace App\Services\Search\Resources;

use App\Models\Media;
use App\Services\Search\Contracts\SearchContext;
use App\Services\Search\Contracts\SearchResource;
use Illuminate\Database\Eloquent\Builder;

class MediaResource extends SearchResource
{
    protected string $type = 'media';

    protected string $group = 'رسانه';

    protected string $icon = 'play_circle';

    protected string $model = Media::class;

    protected array $columns = ['title', 'topic', 'description'];

    protected ?string $titleField = 'title';

    protected ?string $subtitleField = 'topic';

    public function search(SearchContext $context): array
    {
        if (tenantOff('reports:media')) {
            return [];
        }

        return parent::search($context);
    }

    public function action($row): string
    {
        return $this->route('media', $row->getKey());
    }

    protected function scope(Builder $query): void
    {
        $query->library();
    }

    protected function selectList(): string
    {
        return '`id`, `title`, `topic`, `parent_id`, `active`, `departments`, `department_id`, `expires_at`, `report_date`, `kind`';
    }
}
