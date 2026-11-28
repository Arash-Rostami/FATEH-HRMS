<div
    class="animate-fade w-full max-w-[88rem] mx-auto relative flex flex-col gap-6 lg:h-full lg:max-h-[calc(100svh-6rem)] lg:overflow-hidden"
    dir="rtl"
    x-data="share()"
    wire:ignore.self
    @open-post-panel.window="panelOpen = true"
>
    <x-ui.title icon="campaign" title="اعلانات" :count="$this->totalPosts">
        <x-slot:actions>
            <x-ui.buttons.title-more guide="announce">
            <x-slot:actions>
            <x-ui.buttons.view-toggle module="announce" />
            <x-ui.buttons.icon-toggle state="onlySaved" icon="bookmark" title="فقط نشان‌شده‌ها" class="!flex"/>
            <button
                type="button"
                @click="$dispatch('open-modal', { name: 'posts-badge-legend' })"
                title="راهنمای نشانگر اعلان"
                class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
            >
                <span class="material-symbols-rounded text-lg">notifications</span>
            </button>
            <button
                type="button"
                @click="$dispatch('open-modal', { name: 'posts-legend' })"
                title="راهنمای اعلانات"
                class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
            >
                <span class="material-symbols-rounded text-lg">help</span>
            </button>
            </x-slot:actions>
            <x-slot:menu>
                <div x-on:click.stop>
                    @unless(tenantOff('announce:card') || tenantOff('announce:list'))
                        <x-ui.modals.menu-row icon="grid_view" label="نمای کارتی" role="menuitemradio" checked="view === 'card'" click="if (view !== 'card') { view = 'card'; $wire.toggleView('card') }"/>
                        <x-ui.modals.menu-row icon="view_list" label="نمای فهرستی" role="menuitemradio" checked="view === 'list'" click="if (view !== 'list') { view = 'list'; $wire.toggleView('list') }"/>
                    @endunless
                    <x-ui.modals.menu-row icon="bookmark" label="فقط نشان‌شده‌ها" checked="onlySaved" click="onlySaved = !onlySaved"/>
                </div>
                <x-ui.modals.menu-row icon="notifications" label="راهنمای نشانگر اعلان" click="open = false; $dispatch('open-modal', { name: 'posts-badge-legend' })"/>
                <x-ui.modals.menu-row icon="help" label="راهنمای اعلانات" click="open = false; $dispatch('open-modal', { name: 'posts-legend' })"/>
            </x-slot:menu>
            </x-ui.buttons.title-more>
        </x-slot:actions>
    </x-ui.title>

    <x-dashboard.modal.badge-legend
        name="posts-badge-legend"
        :items="[\App\Services\Menu\BadgeLegendCatalog::get('posts-controller')]"
    />

    <x-ui.modals.dialog name="posts-legend" title="راهنمای اعلانات">
        @include('livewire.dashboard.tab.posts.legend')
    </x-ui.modals.dialog>

    @include('components.dashboard.header.focus-chip')

    <div class="-my-6">
        @include('livewire.dashboard.tab.posts.filters')
    </div>

    <div class="w-full relative flex flex-col gap-6 lg:flex-1 lg:overflow-hidden">
        <x-ui.saved-empty scope="post"/>

        @unless(tenantOff('announce:card'))
            <div wire:key="posts-view-card" x-show="view === 'card'" x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="w-full relative flex flex-col lg:flex-row gap-6 lg:flex-1 lg:overflow-hidden">
                @include('livewire.dashboard.tab.posts.pinned')

                @include('livewire.dashboard.tab.posts.grid')
            </div>
        @endunless

        @unless(tenantOff('announce:list'))
            <div wire:key="posts-view-list" x-show="view === 'list'" x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="w-full lg:flex-1 lg:overflow-hidden">
                @include('livewire.dashboard.tab.posts.list')
            </div>
        @endunless

        @if($this->selectedPost)
            @include('livewire.dashboard.tab.posts.details')
        @endif
    </div>
</div>
