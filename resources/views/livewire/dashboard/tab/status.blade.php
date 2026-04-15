<div class="animate-fade w-full max-w-[88rem] mx-auto flex flex-col gap-4">

    <div dir="rtl">
        <x-ui.title
            icon="badge"
            title="وضعیت همکاران"
            :count="array_sum($this->stats)"
            countLabel="نفر"/>
    </div>

    @include('livewire.dashboard.tab.status.filters')

    @include('livewire.dashboard.tab.status.grid')

    @include('livewire.dashboard.tab.status.about-me')
</div>
