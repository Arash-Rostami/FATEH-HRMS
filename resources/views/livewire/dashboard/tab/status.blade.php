<div class="animate-fade w-full max-w-[88rem] mx-auto max-h-[calc(100svh-10rem)] flex flex-col gap-4">

    <div dir="rtl">
        <x-ui.title
            icon="badge"
            title="وضعیت همکاران"
            :count="array_sum($this->stats)"
            countLabel="نفر">
            <x-slot:actions>
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

    @include('livewire.dashboard.tab.status.filters')

    @include('livewire.dashboard.tab.status.grid')

    @include('livewire.dashboard.tab.status.about-me')
</div>
