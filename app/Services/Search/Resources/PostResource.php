<?php

namespace App\Services\Search\Resources;

use App\Models\Post;
use App\Services\Search\Contracts\SearchResource;

class PostResource extends SearchResource
{
    protected string $type = 'post';
    protected string $group = 'پست و اعلانات';
    protected string $icon = 'campaign';
    protected string $model = Post::class;
    protected array $columns = ['title', 'body'];
    protected ?string $titleField = 'title';
    protected ?string $subtitleField = 'body';

    public function action($row): string
    {
        return $this->tab('post', $row->getKey());
    }
}
