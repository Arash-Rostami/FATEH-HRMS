<div
        x-data="links"
        class="animate-fade h-full w-full max-w-[88rem] mx-auto max-h-[calc(100svh-10rem)] relative overflow-y-auto overflow-x-hidden space-y-6 pb-6 custom-scrollbar"
        dir="rtl">

    <x-ui.title icon="open_in_new" title="لینک‌ها و مسیرهای دیجیتال سازمان" :count="$this->totalLinks" countLabel="لینک"/>

    @include('components.dashboard.header.focus-banner')

    @include('livewire.dashboard.tab.links.smart')

    @include('livewire.dashboard.tab.links.internal')

    @include('livewire.dashboard.tab.links.external')

</div>
