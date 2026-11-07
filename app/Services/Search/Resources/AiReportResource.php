<?php

namespace App\Services\Search\Resources;

use App\Models\AiReport;
use App\Services\Search\Contracts\SearchResource;
use Illuminate\Database\Eloquent\Builder;

class AiReportResource extends SearchResource
{
    protected string $type = 'ai-report';
    protected string $group = 'گزارش ساز هوشمند';
    protected string $icon = 'article';
    protected string $model = AiReport::class;
    protected array $columns = ['title'];
    protected ?string $titleField = 'title';
    protected int $recencyMonths = 6;

    public function action($row): string
    {
        return $this->url('/ai-report?r=' . $row->getKey());
    }

    protected function scope(Builder $query): void
    {
        $user = auth()->user();
        $user === null ? $query->whereRaw('1 = 0') : $query->visibleTo($user);
    }
}
