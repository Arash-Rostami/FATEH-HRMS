<?php

namespace App\Services\Search\Contracts;

use Illuminate\Database\Eloquent\Builder;

abstract class SearchResource implements Searchable
{
    /** Rows returned per module group (MySQL ranks + trims to this). */
    protected int $groupLimit = 5;

    /** Stable identifier emitted to the UI (also the record-focus type). */
    protected string $type;

    /** Human label for the result group (RTL). */
    protected string $group;

    /** Material symbol shown beside each hit. */
    protected string $icon;

    /** Eloquent model class-string this resource searches. */
    protected string $model;

    /** Columns scanned by the coarse LIKE filter + relevance ranker. */
    protected array $columns = [];

    /** Title column; null when the title is computed (override titleFor()). */
    protected ?string $titleField = null;

    /** Subtitle column; null falls back to the group label. */
    protected ?string $subtitleField = null;

    /** Tie-breaker ordering column applied after relevance. */
    protected string $orderBy = 'id';

    /** Build the deep-link action string for a matched row (e.g. "url:/..."). */
    abstract public function action($row): string;

    public function search(SearchContext $context): array
    {
        /** @var Builder $builder */
        $builder = ($this->model)::query();

        $this->applyScope($builder);
        $this->applyTokenFilter($builder, $context->tokens);

        [$relevanceSql, $bindings] = $this->relevanceExpression($context);

        $rows = $builder
            ->selectRaw('*, (' . $relevanceSql . ') as search_relevance', $bindings)
            ->orderByDesc('search_relevance')
            ->orderByDesc($this->orderBy)
            ->limit($this->groupLimit)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return [
            'type' => $this->type,
            'group' => $this->group,
            'icon' => $this->icon,
            'score' => (int) ($rows->max('search_relevance') ?? 0),
            'items' => $rows->map(fn ($row) => $this->shapeRow($row))->all(),
        ];
    }

    /**
     * Per-model visibility scope. No-op by default; override to restrict rows.
     * Empty overrides add no SQL (Laravel drops empty nested wheres).
     */
    protected function scope(Builder $query): void
    {
        //
    }

    /** Computed-title hook; defaults to the titleField column. */
    protected function titleFor($row): string
    {
        return $this->titleField ? $this->resolveField($row, $this->titleField) : '';
    }

    /** Wrap the scope in its own group: WHERE (scope) AND (token) AND ... */
    protected function applyScope(Builder $builder): void
    {
        $builder->where(function (Builder $query) {
            $this->scope($query);
        });
    }

    /** Coarse filter — every token must appear in at least one column. */
    protected function applyTokenFilter(Builder $builder, array $tokens): void
    {
        foreach ($tokens as $token) {
            $builder->where(function (Builder $query) use ($token) {
                foreach ($this->columns as $column) {
                    $query->orWhere($column, 'like', '%' . $token . '%');
                }
            });
        }
    }

    /**
     * Relevance score expression + bindings: exact title 120 · title prefix 40 · token in title 40 · token in any column 8
     */
    protected function relevanceExpression(SearchContext $context): array
    {
        $titleColumn = $this->titleField ?? $this->columns[0];

        // Fold casing + Persian ي/ك on a backticked column (MySQL).
        $norm = fn (string $column) => "REPLACE(REPLACE(LOWER(`{$column}`), 'ي', 'ی'), 'ك', 'ک')";
        $title = $norm($titleColumn);

        $parts = [];
        $bindings = [];

        $parts[] = "CASE WHEN {$title} = ? THEN 120 ELSE 0 END";
        $bindings[] = $context->query;

        $parts[] = "CASE WHEN {$title} LIKE ? THEN 40 ELSE 0 END";
        $bindings[] = $context->query . '%';

        foreach ($context->tokens as $token) {
            $parts[] = "CASE WHEN {$title} LIKE ? THEN 40 ELSE 0 END";
            $bindings[] = '%' . $token . '%';

            foreach ($this->columns as $column) {
                $parts[] = 'CASE WHEN ' . $norm($column) . ' LIKE ? THEN 8 ELSE 0 END';
                $bindings[] = '%' . $token . '%';
            }
        }

        return [implode(' + ', $parts), $bindings];
    }

    protected function shapeRow($row): array
    {
        $title = $this->titleFor($row);

        return [
            'id' => $row->getKey(),
            'title' => $title !== '' ? superClean($title, 80) : '—',
            'subtitle' => $this->subtitleFor($row),
            'icon' => $this->icon,
            'group' => $this->group,
            'type' => $this->type,
            'action' => $this->action($row),
            'score' => (int) ($row->search_relevance ?? 0),
        ];
    }

    protected function subtitleFor($row): string
    {
        if (! $this->subtitleField) {
            return $this->group;
        }

        return superClean($this->resolveField($row, $this->subtitleField), 120) ?: $this->group;
    }

    protected function resolveField($row, string $field): string
    {
        return (string) ($row->{$field} ?? '');
    }

    // -- Deep-link helpers (shared by every resource) ----------------------

    protected function tab(string $tab, int $id): string
    {
        return 'url:' . route('dashboard', ['tab' => $tab, 'open' => $id], false);
    }

    protected function route(string $name, int $id): string
    {
        return 'url:' . route($name, ['open' => $id], false);
    }

    protected function url(string $path): string
    {
        return 'url:' . $path;
    }

    protected function me(): int
    {
        return (int) auth()->id();
    }
}
