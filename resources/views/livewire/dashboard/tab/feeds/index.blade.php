<div
        x-data="feed"
        class="relative w-full h-full bg-[var(--md-sys-color-background)]"
        dir="rtl"
>
    @if($assetsLoaded)
        @include('livewire.dashboard.tab.feeds.partials.styles')
    @endif

    @include('livewire.dashboard.tab.feeds.partials.timeline')

    <x-dashboard.modal.confirmation/>
</div>
