<div class="animate-fade w-full max-w-[88rem] mx-auto max-h-[calc(100svh-10rem)] flex flex-col gap-4">

    <div dir="rtl">
        <x-ui.title
            icon="badge"
            title="وضعیت همکاران"
            :count="array_sum($this->stats)"
            countLabel="نفر">
            <x-slot:actions>
                @if(count($this->todaysOccasions))
                    <button
                        type="button"
                        x-on:click="$refs.occasions?.scrollIntoView({ behavior: 'smooth', block: 'start' })"                        title="مشاهده مناسبت‌های امروز"
                        class="flex items-center gap-1 px-2.5 h-8 rounded-lg text-[11px] font-bold bg-[var(--tool-gold-bg)] text-[var(--tool-gold-text)] hover:brightness-95 transition-all animate-pulse-slow"
                    >
                        <span class="material-symbols-rounded text-sm">celebration</span>
                        {{ count($this->todaysOccasions) }}
                    </button>
                @endif
                <button
                    type="button"
                    @click="$dispatch('open-modal', { name: 'status-skill-legend' })"
                    title="راهنمای نشان‌های سطح مهارت"
                    class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                >
                    <span class="material-symbols-rounded text-lg">help</span>
                </button>
            </x-slot:actions>
        </x-ui.title>
    </div>

    <x-ui.modals.dialog name="status-skill-legend" title="راهنمای نشان‌های سطح مهارت">
        @include('livewire.dashboard.tab.status.legend')
    </x-ui.modals.dialog>

    @include('livewire.dashboard.tab.status.occasions')

    @include('livewire.dashboard.tab.status.filters')

    @include('livewire.dashboard.tab.status.grid')

    @include('livewire.dashboard.tab.status.about-me')
</div>
