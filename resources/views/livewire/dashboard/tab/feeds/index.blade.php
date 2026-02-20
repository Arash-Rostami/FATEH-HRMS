<div
    x-data="feed" ax-load="visible"
    class="relative w-full h-full bg-[var(--md-sys-color-background)] p-4 md:p-8"
    dir="rtl"
>
    @if($assetsLoaded)
        @include('livewire.dashboard.tab.feeds.partials.styles')
    @endif

    @include('livewire.dashboard.tab.feeds.partials.timeline')

    <x-dashboard.modal.confirmation/>
</div>
