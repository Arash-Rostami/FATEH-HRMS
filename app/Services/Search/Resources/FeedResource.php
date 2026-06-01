<?php

namespace App\Services\Search\Resources;

use App\Models\Feed;
use App\Services\Search\Contracts\SearchResource;

class FeedResource extends SearchResource
{
    protected string $type = 'feed';
    protected string $group = 'اخبار و فیدها';
    protected string $icon = 'rss_feed';
    protected string $model = Feed::class;
    protected array $columns = ['content', 'category'];
    protected ?string $subtitleField = 'content';

    public function action($row): string
    {
        return $this->tab('feed', $row->getKey());
    }

    protected function titleFor($row): string
    {
        return $row->category ?: superClean((string) $row->content, 40);
    }
}
