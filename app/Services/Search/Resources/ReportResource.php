<?php

namespace App\Services\Search\Resources;

use App\Models\Report;
use App\Services\Search\Contracts\SearchResource;
use Illuminate\Database\Eloquent\Builder;

class ReportResource extends SearchResource
{
    protected string $type = 'report';
    protected string $group = 'گزارشات';
    protected string $icon = 'show_chart';
    protected string $model = Report::class;
    protected array $columns = ['title', 'description'];
    protected ?string $titleField = 'title';
    protected ?string $subtitleField = 'description';

    public function action($row): string
    {
        return $this->tab('reports', $row->getKey());
    }

    protected function scope(Builder $query): void
    {
        $query->where('active', 1);
    }
}
