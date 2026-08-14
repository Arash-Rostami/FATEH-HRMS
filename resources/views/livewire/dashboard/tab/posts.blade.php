<div
    class="animate-fade h-full w-full max-w-[88rem] mx-auto max-h-[calc(100svh-10rem)] relative overflow-hidden flex flex-col gap-6"
    dir="rtl"
    x-data="share()"
    @open-post-panel.window="panelOpen = true"
>
    <x-ui.title icon="campaign" title="اعلانات‌" :count="$this->totalPosts">
        <x-slot:actions>
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
    </x-ui.title>

    <x-dashboard.modal.badge-legend
        name="posts-badge-legend"
        :items="[\App\Services\Menu\BadgeLegendCatalog::get('posts-controller')]"
    />

    <x-ui.modals.dialog name="posts-legend" title="راهنمای اعلانات">
        @include('livewire.dashboard.tab.posts.legend')
    </x-ui.modals.dialog>

    @include('components.dashboard.header.focus-chip')


    <div class="flex-1 w-full relative overflow-hidden flex flex-col lg:flex-row gap-6">
        @include('livewire.dashboard.tab.posts.pinned')

        @include('livewire.dashboard.tab.posts.grid')

        @if($this->selectedPost)
            @include('livewire.dashboard.tab.posts.details')
        @endif
    </div>
</div>
