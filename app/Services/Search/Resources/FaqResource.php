<?php

namespace App\Services\Search\Resources;

use App\Models\FAQ;
use App\Services\Search\Contracts\SearchResource;

class FaqResource extends SearchResource
{
    protected string $type = 'faq';
    protected string $group = 'پرسش‌های متداول';
    protected string $icon = 'help';
    protected string $model = FAQ::class;
    protected array $columns = ['question', 'answer', 'category'];
    protected ?string $titleField = 'question';
    protected ?string $subtitleField = 'answer';

    public function action($row): string
    {
        return $this->tab('faqs', $row->getKey());
    }
}
