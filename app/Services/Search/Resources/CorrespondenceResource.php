<?php

namespace App\Services\Search\Resources;

use App\Livewire\Dashboard\Correspondence\Presentation\CorrespondencePresenter;
use App\Models\Correspondence;
use App\Services\Search\Contracts\SearchContext;
use App\Services\Search\Contracts\SearchResource;
use App\Support\CorrespondenceAccessPolicy;
use Illuminate\Database\Eloquent\Builder;

class CorrespondenceResource extends SearchResource
{
    private const TOKEN_MAX = 64;

    protected string $type = 'correspondence';

    protected string $group = 'مکاتبات';

    protected string $icon = 'mail';

    protected string $model = Correspondence::class;

    protected int $recencyMonths = 12;

    private ?bool $registry = null;

    private array $visibilitySql = [];

    protected function selectList(): string
    {
        return '`id`, `user_id`, `status`, `classification`, `number`, `subject`';
    }

    protected function applyTokenFilter(Builder $builder, array $tokens): void
    {
        foreach ($tokens as $token) {
            $like = '%' . $this->escapeToken($token) . '%';

            $builder->where(function (Builder $query) use ($like) {
                $query->orWhere('number', 'like', $like);
                $this->orWhereVisible($query, 'subject', $like);
                $this->orWhereVisible($query, 'search_text', $like);
            });
        }
    }

    protected function relevanceExpression(SearchContext $context): array
    {
        $norm = fn (string $column) => "REPLACE(REPLACE(LOWER(`{$column}`), 'ي', 'ی'), 'ك', 'ک')";
        $number = $norm('number');

        $parts = [
            "CASE WHEN {$number} = ? THEN 120 ELSE 0 END",
            "CASE WHEN {$number} LIKE ? THEN 40 ELSE 0 END",
        ];
        $bindings = [$context->query, $this->escapeToken($context->query) . '%'];

        foreach ($context->tokens as $token) {
            $like = '%' . $this->escapeToken($token) . '%';

            $parts[] = "CASE WHEN {$number} LIKE ? THEN 40 ELSE 0 END";
            $bindings[] = $like;

            foreach (['subject' => 20, 'search_text' => 8] as $column => $score) {
                [$condition, $conditionBindings] = $this->visibilitySql($column);

                $parts[] = "CASE WHEN {$condition} AND {$norm($column)} LIKE ? THEN {$score} ELSE 0 END";
                $bindings = [...$bindings, ...$conditionBindings, $like];
            }
        }

        return [implode(' + ', $parts), $bindings];
    }

    public function action($row): string
    {
        return $this->route('correspondence', (int) $row->getKey());
    }

    protected function scope(Builder $query): void
    {
        $user = auth()->user();

        if ($user === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $visible) use ($user) {
            $visible->where('user_id', $user->id)
                ->orWhere(fn (Builder $shared) => $shared->sharedWith($user->id));

            if ($this->isRegistry()) {
                $visible->orWhereIn('status', CorrespondencePresenter::REGISTRY_STATUSES);
            }
        });
    }

    protected function titleFor($row): string
    {
        if ((int) $row->user_id !== $this->me() && $row->classification !== Correspondence::CLASSIFICATION_NORMAL) {
            return filled($row->number) ? (string) $row->number : __('resources/correspondence/strings.confidential_subject');
        }

        return (string) ($row->subject ?: ($row->number ?: 'نامه بدون موضوع'));
    }

    protected function subtitleFor($row): string
    {
        if ((int) $row->user_id !== $this->me() && $row->classification !== Correspondence::CLASSIFICATION_NORMAL) {
            return __('resources/correspondence/strings.status.' . $row->status);
        }

        return filled($row->number) ? (string) $row->number : $this->group;
    }

    private function isRegistry(): bool
    {
        return $this->registry ??= CorrespondenceAccessPolicy::isRegistry(auth()->user());
    }

    private function orWhereVisible(Builder $query, string $column, string $like): void
    {
        [$condition, $bindings] = $this->visibilitySql($column);

        $query->orWhere(function (Builder $inner) use ($condition, $bindings, $column, $like) {
            $inner->whereRaw('((' . $condition . ') and `' . $column . '` like ?)', [...$bindings, $like]);
        });
    }

    private function visibilitySql(string $column): array
    {
        return $this->visibilitySql[$column] ??= $this->compile($column === 'subject');
    }

    private function compile(bool $subject): array
    {
        $builder = Correspondence::query()
            ->where(function ($query) use ($subject) {
                $query->where('user_id', $this->me());

                if (!$subject) {
                    $query->orWhere(fn ($shared) => $shared->sharedWith($this->me()));

                    return;
                }

                $query->orWhere(function ($normal) {
                    $normal->where('classification', Correspondence::CLASSIFICATION_NORMAL);

                    if (!$this->isRegistry()) {
                        $normal->sharedWith($this->me());
                    }
                });
            });

        $sql = substr((string) $builder->getQuery()->getGrammar()->compileWheres($builder->getQuery()), 6);

        return ['(' . $sql . ')', $builder->getBindings()];
    }

    private function escapeToken(string $token): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], mb_substr($token, 0, self::TOKEN_MAX));
    }
}
