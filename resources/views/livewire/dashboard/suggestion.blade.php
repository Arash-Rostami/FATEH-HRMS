<div dir="rtl"
     class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade"
    >

    <div class="max-w-[88rem] mx-auto page-wrapper">
        <x-ui.title
            icon="lightbulb"
            title="پیشنهادات"
            count="{{  $this->suggestions->total() }}"
        >
            <x-slot:actions>
                <button
                    type="button"
                    @click="$dispatch('open-modal', { name: 'suggestion-badge-legend' })"
                    title="راهنمای نشانگر اعلان"
                    class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                >
                    <span class="material-symbols-rounded text-lg">notifications</span>
                </button>
                <button
                    type="button"
                    @click="$dispatch('open-modal', { name: 'suggestion-legend' })"
                    title="راهنمای پیشنهادات"
                    class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                >
                    <span class="material-symbols-rounded text-lg">help</span>
                </button>
            </x-slot:actions>
        </x-ui.title>

        <x-dashboard.modal.badge-legend
            name="suggestion-badge-legend"
            :items="[\App\Services\Menu\BadgeLegendCatalog::get('suggestion-controller')]"
        />

        <x-ui.modals.dialog name="suggestion-legend" title="راهنمای پیشنهادات">
            @include('livewire.dashboard.suggestion.legend')
        </x-ui.modals.dialog>

        @include('livewire.dashboard.suggestion.leaderboard')

        @include('components.dashboard.header.focus-chip')


        <div x-data="{ uploading: false, progress: 0 }"
             x-on:livewire-upload-start="uploading = true"
             x-on:livewire-upload-finish="uploading = false; progress = 0"
             x-on:livewire-upload-error="uploading = false; progress = 0"
             x-on:livewire-upload-progress="progress = $event.detail.progress"
        >

            <div class="flex flex-col md:flex-row gap-4">
                <aside class="w-full md:w-80 shrink-0">

                    @include('livewire.dashboard.suggestion.list')

                </aside>

                <main class="flex-1 min-w-0">

                    @includeWhen($panel === 'empty', 'livewire.dashboard.suggestion.placeholder')

                    @includeWhen($panel === 'create', 'livewire.dashboard.suggestion.create')

                    @if($panel === 'detail')
                        @if($selectedId)
                            <livewire:dashboard.suggestion.detail
                                wire:key="detail-{{ $selectedId }}"
                                :suggestion-id="$selectedId"
                                lazy="on-load"
                            />
                        @else
                            <x-ui.loaders.spin-badge text="در حال بارگذاری..."/>
                        @endif
                    @endif
                </main>
            </div>
        </div>
    </div>
</div>
