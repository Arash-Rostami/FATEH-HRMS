<div class="w-full flex flex-col gap-4 p-4 md:p-6 animate-fade-in-up p-4 md:p-8">

    <div dir="rtl">
        <x-dashboard.tab.title icon="badge" title="وضعیت همکاران" :count="array_sum($this->stats)" countLabel="نفر"/>
    </div>

    @include('livewire.dashboard.tab.status.partials.filters')

    @include('livewire.dashboard.tab.status.partials.grid')

    <x-dashboard.modal.toast/>
</div>
