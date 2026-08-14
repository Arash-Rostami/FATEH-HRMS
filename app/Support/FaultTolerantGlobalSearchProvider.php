<?php

namespace App\Support;

use Filament\Facades\Filament;
use Filament\GlobalSearch\Providers\Contracts\GlobalSearchProvider;
use Filament\GlobalSearch\GlobalSearchResults;
use Throwable;

class FaultTolerantGlobalSearchProvider implements GlobalSearchProvider
{
    public function getResults(string $query): ?GlobalSearchResults
    {
        $builder = GlobalSearchResults::make();

        $resources = $this->getResources();

        usort(
            $resources,
            fn (string $a, string $b): int => $this->safeSort($a) <=> $this->safeSort($b),
        );

        foreach ($resources as $resource) {
            try {
                if (!$resource::canGloballySearch()) {
                    continue;
                }

                $resourceResults = $resource::getGlobalSearchResults($query);

                if (!$resourceResults->count()) {
                    continue;
                }

                $builder->category($resource::getPluralModelLabel(), $resourceResults);
            } catch (Throwable $e) {
                report($e);

                continue;
            }
        }

        return $builder;
    }

    protected function safeSort(string $resource): int
    {
        try {
            return $resource::getGlobalSearchSort() ?? 0;
        } catch (Throwable $e) {
            report($e);

            return 0;
        }
    }

    /**
     * @return array<class-string>
     */
    protected function getResources(): array
    {
        return Filament::getResources();
    }
}
