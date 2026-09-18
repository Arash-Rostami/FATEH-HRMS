<div x-on:keydown.escape.window="if (max) { toggleMaximize() } else { $wire.clearFocus() }">

    <div class="w-fit z-1 bg-[var(--md-sys-color-surface)] mb-6" x-on:click="if (max) toggleMaximize()">
        <x-ui.buttons.tab-selector
            wire:key="reservation-canvas-tabs-{{ $canvasTab }}"
            :active-tab="$canvasTab"
            :has-a11y="true"
            button-base-class="group relative flex items-center justify-center gap-2 px-6 py-3 text-sm font-semibold rounded-xl transition-all duration-200 outline-none flex-1 min-w-[140px]"
            button-active-class="bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md shadow-[var(--md-sys-color-primary)]/20"
            button-inactive-class="text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)]"
            icon-base-class="material-symbols-rounded text-xl"
            icon-active-class="font-variation-fill"
            icon-inactive-class="opacity-70 group-hover:opacity-100"
            :tabs="$canvasTabs"
        />
    </div>

    @if($canvasTab === 'booking')
        <div wire:key="reservation-canvas-booking" class="grid grid-cols-1 lg:grid-cols-2 gap-6 pb-12 w-full items-start">
            <div class="flex flex-col gap-5 min-w-0">
                @include('livewire.dashboard.reservation.booking-panel')
            </div>
            <div class="min-w-0">
                @include('livewire.dashboard.reservation.dossier')
            </div>
        </div>
    @else
        <div wire:key="reservation-canvas-grid" class="contents">
            @include('livewire.dashboard.reservation.command-grid')
        </div>
    @endif
</div>
