<div
    class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade"
    style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;"
    dir="rtl">

    <div class="max-w-[88rem] mx-auto page-wrapper">
        <x-ui.title
            icon="work"
            title="فرصت‌های شغلی"
            :count="$this->stats['active']"
            countLabel="فرصت فعال"
        />

        <div class="mb-6">
            @include('livewire.dashboard.ads.filters')
        </div>

        @include('livewire.dashboard.ads.grid')
    </div>
</div>
