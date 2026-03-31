<div class="animate-fade w-full max-w-[88rem] mx-auto flex flex-col gap-4">
    <div dir="rtl">
        <x-dashboard.tab.title icon="work" title="فرصت‌های شغلی" :count="$this->stats['active']" countLabel="فرصت فعال"/>
    </div>

    @include('livewire.dashboard.tab.ads.partials.filters')

    @include('livewire.dashboard.tab.ads.partials.grid')
</div>
