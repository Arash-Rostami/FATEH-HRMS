@php
    $panel = \Filament\Facades\Filament::getCurrentPanel();
    $path = $panel?->getPath();
    $user = auth()->user();

    $groupName = fn ($group) => $group instanceof \BackedEnum
        ? (string) $group->value
        : (string) ($group ?: 'سایر');

    $resourceMap = \Illuminate\Support\Facades\Cache::remember(
        'admin-resource-map:' . app()->getLocale(),
        3600,
        function () use ($groupName) {
            $map = [];

            foreach (\Filament\Facades\Filament::getResources() as $resource) {
                $map[$resource::getSlug()] = [
                    'label' => (string) $resource::getNavigationLabel(),
                    'group' => $groupName($resource::getNavigationGroup()),
                ];
            }

            return $map;
        }
    );

    $createGroups = $user
        ? \App\Services\Cache\ModelCacheVersion::remember(
            \App\Models\Permission::class,
            'quick-create:' . $user->id,
            now()->addMinutes(5),
            function () use ($groupName) {
                $groups = [];

                foreach (\Filament\Facades\Filament::getResources() as $resource) {
                    if (! $resource::hasPage('create')
                        || ! $resource::shouldRegisterNavigation()
                        || ! $resource::canCreate()
                        || ! $resource::canViewAny()
                    ) {
                        continue;
                    }

                    $icon = $resource::getNavigationIcon();

                    $groups[$groupName($resource::getNavigationGroup())][] = [
                        'label' => (string) $resource::getNavigationLabel(),
                        'icon' => is_string($icon) ? $icon : null,
                        'url' => $resource::getUrl('create'),
                    ];
                }

                return $groups;
            }
        )
        : [];

    $btn = 'group relative shrink-0 flex w-10 h-10 items-center justify-center rounded-xl active:scale-95 transition snap-start snap-always hover:bg-[var(--md-sys-color-on-primary)]/10 text-[var(--md-sys-color-primary-container)]';
    $menu = 'fixed z-[60] max-h-[70vh] overflow-y-auto rounded-2xl border border-[var(--md-sys-color-outline-variant)]/60 bg-[var(--md-sys-color-surface)] p-2 shadow-[0_12px_32px_-6px_color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)]';
    $item = 'flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm text-[var(--md-sys-color-on-surface)] transition-colors hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] hover:text-[var(--md-sys-color-primary)]';
@endphp

<script>window.__adminRecents = @json(['path' => $path, 'map' => $resourceMap]);</script>

@if($createGroups)
    <div class="shrink-0 snap-start snap-always"
         x-data="{ open: false, top: 0, left: 0 }"
         @click.window="open && ! $event.target.closest('[data-popover=quick-create]') && ! $el.contains($event.target) && (open = false)"
         @keydown.escape.window="open = false">
        <button type="button"
                @click="const r = $event.currentTarget.getBoundingClientRect(); top = r.bottom + 8; left = Math.max(8, Math.min(r.left + r.width / 2 - 144, window.innerWidth - 296)); open = ! open"
                class="{{ $btn }}" aria-haspopup="true" :aria-expanded="open">
            <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100">add_circle</span>
            <x-ui.modals.tooltip text="ایجاد سریع" position="bottom"/>
        </button>

        <template x-teleport="body">
            <div x-cloak x-show="open" data-popover="quick-create" dir="rtl"
                 :style="`top:${top}px;left:${left}px`" class="{{ $menu }} w-72"
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                @foreach($createGroups as $group => $items)
                    <div class="px-3 pt-2 pb-1 text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)]">{{ $group }}</div>
                    @foreach($items as $entry)
                        <a href="{{ $entry['url'] }}" wire:navigate @click="open = false" class="{{ $item }}">
                            @if($entry['icon'])
                                <x-filament::icon :icon="$entry['icon']" class="h-4 w-4 shrink-0"/>
                            @endif
                            <span class="truncate">{{ $entry['label'] }}</span>
                        </a>
                    @endforeach
                @endforeach
            </div>
        </template>
    </div>
@endif

<div class="shrink-0 snap-start snap-always"
     x-data="{
         open: false, top: 0, left: 0, entries: [],
         load() {
             try {
                 const v = JSON.parse(localStorage.getItem('recent_records_admin'));
                 this.entries = Array.isArray(v)
                     ? v.filter(e => e?.url?.startsWith('/{{ $path }}/'))
                     : [];
             } catch { this.entries = []; }
         },
         clear() {
             try { localStorage.removeItem('recent_records_admin'); } catch {}
             this.entries = [];
         },
         init() { this.load(); window.addEventListener('recents-updated', () => this.load()); }
     }"
     @click.window="open && ! $event.target.closest('[data-popover=recents]') && ! $el.contains($event.target) && (open = false)"
     @keydown.escape.window="open = false">
    <button type="button"
            @click="load(); const r = $event.currentTarget.getBoundingClientRect(); top = r.bottom + 8; left = Math.max(8, Math.min(r.left + r.width / 2 - 160, window.innerWidth - 328)); open = ! open"
            class="{{ $btn }}" aria-haspopup="true" :aria-expanded="open">
        <span class="material-symbols-rounded text-[22px] opacity-80 group-hover:opacity-100">history</span>
        <x-ui.modals.tooltip text="رکوردهای اخیر" position="bottom"/>
    </button>

    <template x-teleport="body">
        <div x-cloak x-show="open" data-popover="recents" dir="rtl"
             :style="`top:${top}px;left:${left}px`" class="{{ $menu }} w-80"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div x-show="entries.length === 0" class="flex flex-col items-center gap-2 px-4 py-6 text-center text-sm text-[var(--md-sys-color-on-surface-variant)]">
                <span class="material-symbols-rounded text-[28px] opacity-50">history</span>
                هنوز رکوردی باز نکرده‌اید.
            </div>

            <template x-for="entry in entries" :key="entry.url">
                <a :href="entry.url" wire:navigate @click="open = false" class="{{ $item }}">
                    <span class="shrink-0 rounded-md bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] px-1.5 py-0.5 text-[10px] font-bold text-[var(--md-sys-color-primary)]" x-text="entry.label"></span>
                    <span class="truncate" x-text="entry.title || ('#' + entry.id)"></span>
                    <span dir="ltr" class="ms-auto shrink-0 tabular-nums text-xs opacity-60" x-text="'#' + entry.id"></span>
                </a>
            </template>

            <div x-show="entries.length > 0" class="mt-1 border-t border-[var(--md-sys-color-outline-variant)]/60 px-3 pt-2 pb-1">
                <button type="button" @click="clear()" class="flex items-center gap-1.5 text-xs font-medium text-[var(--md-sys-color-on-surface-variant)] transition-colors hover:text-[var(--md-sys-color-primary)]">
                    <span class="material-symbols-rounded text-[15px]">delete_sweep</span>
                    پاک کردن
                </button>
            </div>
        </div>
    </template>
</div>


