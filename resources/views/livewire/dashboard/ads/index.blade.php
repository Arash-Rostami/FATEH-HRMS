<div class="flex flex-col min-h-screen overflow-x-hidden transition-colors duration-500 relative isolate">

    <x-dashboard.navbar.left/>

    <div class="flex-1 w-full pl-0 md:pl-20 lg:pl-0 pt-20 md:pt-4 px-4 transition-all duration-300 mx-auto">
        <div class="animate-fade w-full max-w-[88rem] mx-auto flex flex-col gap-4 bg-[var(--md-sys-color-surface)] rounded-3xl p-4 md:p-8 shadow-sm border border-[var(--md-sys-color-outline-variant)]/50 min-h-[calc(100vh-2rem)]">
            <div dir="rtl">
                <x-dashboard.tab.title icon="work" title="فرصت‌های شغلی" :count="$this->stats['active']" countLabel="فرصت فعال"/>
            </div>

            @include('livewire.dashboard.ads.partials.filters')

            @include('livewire.dashboard.ads.partials.grid')
        </div>
    </div>
</div>
