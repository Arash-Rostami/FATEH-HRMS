<?php

namespace App\View\Composers;

use App\Models\Permission;
use App\Models\User;
use App\Services\Cache\ModelCacheVersion;
use BackedEnum;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AdminActionsStartComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();

        $view->with([
            'path' => Filament::getCurrentPanel()?->getPath(),
            'resourceMap' => $this->resourceMap(),
            'createGroups' => $user ? $this->createGroups($user) : [],
        ]);
    }

    private function resourceMap(): array
    {
        return Cache::remember(
            'admin-resource-map:' . app()->getLocale(),
            3600,
            function () {
                $map = [];

                foreach (Filament::getResources() as $resource) {
                    $map[$resource::getSlug()] = [
                        'label' => (string) $resource::getNavigationLabel(),
                        'group' => $this->groupName($resource::getNavigationGroup()),
                    ];
                }

                return $map;
            }
        );
    }

    private function createGroups(User $user): array
    {
        return ModelCacheVersion::remember(
            Permission::class,
            'quick-create:' . $user->id,
            now()->addMinutes(5),
            function () {
                $groups = [];

                foreach (Filament::getResources() as $resource) {
                    if (! $resource::hasPage('create')
                        || ! $resource::shouldRegisterNavigation()
                        || ! $resource::canCreate()
                        || ! $resource::canViewAny()
                    ) {
                        continue;
                    }

                    $icon = $resource::getNavigationIcon();

                    $groups[$this->groupName($resource::getNavigationGroup())][] = [
                        'label' => (string) $resource::getNavigationLabel(),
                        'icon' => is_string($icon) ? $icon : null,
                        'url' => $resource::getUrl('create'),
                    ];
                }

                return $groups;
            }
        );
    }

    private function groupName(mixed $group): string
    {
        return $group instanceof BackedEnum
            ? (string) $group->value
            : (string) ($group ?: 'سایر');
    }
}
