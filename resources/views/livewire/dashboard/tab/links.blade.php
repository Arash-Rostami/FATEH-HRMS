<div
    class="animate-fade h-full w-full max-w-[88rem] mx-auto relative overflow-y-auto overflow-x-hidden space-y-12 pb-24 custom-scrollbar"
    dir="rtl">

    <x-ui.title icon="open_in_new" title="لینک‌ها و ابزارها" :count="$this->totalLinks" countLabel="لینک"/>

    @include('livewire.dashboard.tab.links.internal')

    @include('livewire.dashboard.tab.links.external')

</div>
