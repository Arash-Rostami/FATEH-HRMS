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

        @if($this->topContributors->count() > 0)
            <div class="mr-auto w-full md:w-1/2 lg:w-1/3 mb-6 relative left-0 top-0 mt-2 bg-gradient-to-r from-[var(--md-sys-color-surface-variant)] to-[var(--md-sys-color-surface)] p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/50 shadow-sm flex flex-col gap-3">
                <div class="flex items-center gap-2 text-[var(--md-sys-color-on-surface)]">
                    <span class="material-symbols-rounded text-xl text-[var(--md-sys-color-primary)]">workspace_premium</span>
                    <h3 class="font-bold text-sm">برترین مشارکت‌کنندگان</h3>
                </div>
                <div class="flex flex-col gap-2">
                    @foreach($this->topContributors as $index => $contributor)
                        <div class="flex items-center justify-between text-xs bg-[var(--md-sys-color-surface)] p-2 rounded-xl border border-[var(--md-sys-color-outline-variant)]/30">
                            <div class="flex items-center gap-2">
                                <span class="flex items-center justify-center w-6 h-6 rounded-full font-bold text-[10px]
                                    @if($index === 0) bg-amber-100 text-amber-700 border border-amber-200
                                    @elseif($index === 1) bg-gray-100 text-gray-700 border border-gray-200
                                    @else bg-orange-50 text-orange-700 border border-orange-200 @endif">
                                    {{ $index + 1 }}
                                </span>
                                <span class="font-medium text-[var(--md-sys-color-on-surface)] truncate max-w-[120px]">
                                    {{ $contributor->user->name ?? 'کاربر ناشناس' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3 text-[10px] text-[var(--md-sys-color-on-surface-variant)]">
                                <span class="flex items-center gap-1" title="مجموع پیشنهادات">
                                    <span class="material-symbols-rounded !text-[14px]">lightbulb</span>
                                    {{ $contributor->total_suggestions }}
                                </span>
                                <span class="flex items-center gap-1 text-[var(--md-sys-color-primary)]" title="پیشنهادات پذیرفته شده">
                                    <span class="material-symbols-rounded !text-[14px]">check_circle</span>
                                    {{ $contributor->accepted_suggestions ?? 0 }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif


        @include('components.dashboard.header.focus-chip')


        <div x-data="{ uploading: false, uploadProgress: 0, uploaded: false }"
             x-on:livewire-upload-start="uploading = true; uploaded = false"
             x-on:livewire-upload-finish="uploading = false; uploaded = true"
             x-on:livewire-upload-error="uploading = false"
             x-on:livewire-upload-progress="uploadProgress = $event.detail.progress">

            <div class="flex flex-col md:flex-row gap-4">
                <aside class="w-full md:w-80 shrink-0">

                    @include('livewire.dashboard.suggestion.list')

                </aside>

                <main class="flex-1 min-w-0 md:sticky md:top-4 md:max-h-[calc(100vh-6rem)] md:overflow-y-auto" style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;">

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
