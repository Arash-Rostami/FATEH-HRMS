<?php

namespace App\Services\Search\Resources;

use App\Models\Photo;
use App\Services\Search\Contracts\SearchResource;

class GalleryResource extends SearchResource
{
    protected string $type = 'gallery';
    protected string $group = 'گالری تصاویر';
    protected string $icon = 'photo_library';
    protected string $model = Photo::class;
    protected array $columns = ['title', 'description'];
    protected ?string $titleField = 'title';
    protected ?string $subtitleField = 'description';
    protected string $orderBy = 'event_date';

    public function action($row): string
    {
        return $this->tab('gallery', $row->getKey());
    }
}
