<div dir="rtl"
     class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade"
     style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;">

    <div class="max-w-[88rem] mx-auto page-wrapper">
        <x-ui.placeholder/>

        <x-ui.title
            icon="lightbulb"
            title="پیشنهادات"
            count="{{  $this->suggestions->total() }}"
        />

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
                                lazy="true"
                            />
                        @else
                            <x-ui.loaders.spinner/>
                        @endif
                    @endif
                </main>
            </div>
        </div>
    </div>
</div>
