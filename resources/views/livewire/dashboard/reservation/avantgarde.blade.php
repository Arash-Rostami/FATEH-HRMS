<div x-on:keydown.escape.window="if (max) { toggleMaximize() } else { $wire.clearFocus() }">

    <div class="w-fit mx-auto md:mx-0 z-1 mb-6" x-on:click="if (max) toggleMaximize()">
        <x-ui.buttons.tab-selector
            :active-tab="$canvasTab"
            :has-a11y="true"
            :tabs="$canvasTabs"
        />
    </div>

    @if(! empty($tabs))
        @include('livewire.dashboard.reservation.limits')
        @include('livewire.dashboard.reservation.rules')
    @endif

    @if($canvasTab === 'booking' && empty($tabs))
        <div wire:key="reservation-avantgarde-no-permission-canvas" class="contents">
            <x-ui.empty icon="lock" title="دسترسی رزرو ندارید" description="مجوز رزرو هیچ نوع منبعی برای شما فعال نشده است. با مدیر سیستم تماس بگیرید." variant="list" />
        </div>
    @elseif($canvasTab === 'booking')
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
