<div class="animate-fade w-full max-w-[88rem] mx-auto flex flex-col"
     dir="rtl"
     x-data="report()"
     wire:ignore.self>

    <div>
        <x-ui.title
            icon="show_chart"
            title="گزارشات"
            :count="$this->totalReports"
            countLabel="گزارش"
        />
    </div>

    @include('livewire.dashboard.tab.reports.navigations')


    <div x-show="view === 'card'"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="w-full relative group/container hidden md:block"
         style="height: clamp(420px, calc(100svh - 200px), 800px);">

        @include('livewire.dashboard.tab.reports.cards')

    </div>

    <div x-show="view === 'list'"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="w-full px-4 md:px-12 pb-8">

        @include('livewire.dashboard.tab.reports.list')
    </div>

    @include('livewire.dashboard.tab.reports.modal')
</div>
