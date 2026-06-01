<?php

namespace App\Services\Search\Resources;

use App\Models\Link;
use App\Services\Search\Contracts\SearchResource;

class LinkResource extends SearchResource
{
    protected string $type = 'link';
    protected string $group = 'لینک‌ها و ابزارها';
    protected string $icon = 'open_in_new';
    protected string $model = Link::class;
    protected array $columns = ['url_title', 'url_description', 'link'];
    protected ?string $titleField = 'url_title';
    protected ?string $subtitleField = 'url_description';

    public function action($row): string
    {
        return $this->tab('links', $row->getKey());
    }
}
