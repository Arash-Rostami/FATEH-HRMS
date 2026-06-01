<?php

namespace App\Services\Search\Resources;

use App\Models\Suggestion;
use App\Services\Search\Contracts\SearchResource;

class SuggestionResource extends SearchResource
{
    protected string $type = 'suggestion';
    protected string $group = 'پیشنهادات';
    protected string $icon = 'lightbulb';
    protected string $model = Suggestion::class;
    protected array $columns = ['title', 'description', 'purpose'];
    protected ?string $titleField = 'title';
    protected ?string $subtitleField = 'description';

    public function action($row): string
    {
        return $this->route('suggestion', $row->getKey());
    }
}
