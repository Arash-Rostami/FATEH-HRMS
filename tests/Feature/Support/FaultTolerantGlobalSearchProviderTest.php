<?php

namespace Tests\Feature\Support;

use App\Support\FaultTolerantGlobalSearchProvider;
use Filament\Facades\Filament;
use Filament\GlobalSearch\GlobalSearchResult;
use Illuminate\Support\Collection;
use RuntimeException;
use Tests\TestCase;

class FaultTolerantGlobalSearchProviderTest extends TestCase
{
    public function test_admin_panel_uses_the_fault_tolerant_provider()
    {
        $provider = Filament::getPanel('admin')->getGlobalSearchProvider();

        $this->assertInstanceOf(FaultTolerantGlobalSearchProvider::class, $provider);
    }

    public function test_it_skips_a_throwing_resource_and_still_returns_other_resources_results()
    {
        $provider = new class extends FaultTolerantGlobalSearchProvider {
            protected function getResources(): array
            {
                return [ThrowingGlobalSearchResource::class, WorkingGlobalSearchResource::class];
            }
        };

        $results = $provider->getResults('anything');

        $this->assertNotNull($results);
        $this->assertTrue($results->getCategories()->has('Working'));
        $this->assertFalse($results->getCategories()->has('Throwing'));
    }

    public function test_it_skips_a_resource_that_throws_from_can_globally_search()
    {
        $provider = new class extends FaultTolerantGlobalSearchProvider {
            protected function getResources(): array
            {
                return [ThrowingCanGloballySearchResource::class, WorkingGlobalSearchResource::class];
            }
        };

        $results = $provider->getResults('anything');

        $this->assertNotNull($results);
        $this->assertTrue($results->getCategories()->has('Working'));
        $this->assertFalse($results->getCategories()->has('ThrowingCanGloballySearch'));
    }

    public function test_a_resource_that_throws_from_get_global_search_sort_still_returns_its_results()
    {
        $provider = new class extends FaultTolerantGlobalSearchProvider {
            protected function getResources(): array
            {
                return [ThrowingGlobalSearchSortResource::class, WorkingGlobalSearchResource::class];
            }
        };

        $results = $provider->getResults('anything');

        $this->assertNotNull($results);
        $this->assertTrue($results->getCategories()->has('Working'));
        $this->assertTrue($results->getCategories()->has('ThrowingGlobalSearchSort'));
    }
    public function test_it_skips_resources_whose_module_is_off_and_returns_them_when_on()
    {
        $provider = new class extends FaultTolerantGlobalSearchProvider {
            protected function getResources(): array
            {
                return [OffModuleGlobalSearchResource::class, WorkingGlobalSearchResource::class];
            }
        };

        try {
            config(['app.off' => ['gallery']]);
            $off = $provider->getResults('anything')->getCategories();

            config(['app.off' => []]);
            $on = $provider->getResults('anything')->getCategories();
        } finally {
            config(['app.off' => []]);
        }

        $this->assertTrue($off->has('Working'));
        $this->assertFalse($off->has('OffModule'));
        $this->assertTrue($on->has('OffModule'));
    }
}

class OffModuleGlobalSearchResource extends WorkingGlobalSearchResource
{
    public static function getSlug(): string
    {
        return 'galleries';
    }

    public static function getPluralModelLabel(): string
    {
        return 'OffModule';
    }
}

class ThrowingGlobalSearchResource
{
    public static function canGloballySearch(): bool
    {
        return true;
    }

    public static function getGlobalSearchSort(): ?int
    {
        return null;
    }

    public static function getGlobalSearchResults(string $query): Collection
    {
        throw new RuntimeException('Simulated resource failure.');
    }

    public static function getSlug(): string
    {
        return 'zz-throwing';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Throwing';
    }
}

class WorkingGlobalSearchResource
{
    public static function canGloballySearch(): bool
    {
        return true;
    }

    public static function getGlobalSearchSort(): ?int
    {
        return null;
    }

    public static function getGlobalSearchResults(string $query): Collection
    {
        return collect([new GlobalSearchResult(title: 'Found', url: '/x')]);
    }

    public static function getSlug(): string
    {
        return 'zz-working';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Working';
    }
}

class ThrowingCanGloballySearchResource
{
    public static function canGloballySearch(): bool
    {
        throw new RuntimeException('Simulated canGloballySearch failure.');
    }

    public static function getGlobalSearchSort(): ?int
    {
        return null;
    }

    public static function getGlobalSearchResults(string $query): Collection
    {
        return collect([new GlobalSearchResult(title: 'Found', url: '/x')]);
    }

    public static function getSlug(): string
    {
        return 'zz-throwing-can';
    }

    public static function getPluralModelLabel(): string
    {
        return 'ThrowingCanGloballySearch';
    }
}

class ThrowingGlobalSearchSortResource
{
    public static function canGloballySearch(): bool
    {
        return true;
    }

    public static function getGlobalSearchSort(): ?int
    {
        throw new RuntimeException('Simulated getGlobalSearchSort failure.');
    }

    public static function getGlobalSearchResults(string $query): Collection
    {
        return collect([new GlobalSearchResult(title: 'Found', url: '/x')]);
    }

    public static function getSlug(): string
    {
        return 'zz-throwing-sort';
    }

    public static function getPluralModelLabel(): string
    {
        return 'ThrowingGlobalSearchSort';
    }
}
