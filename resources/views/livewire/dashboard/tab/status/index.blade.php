<div class="w-full max-w-[88rem] mx-auto flex flex-col gap-4 animate-fade-in-up">

<div dir="rtl">
        <x-dashboard.tab.title icon="badge" title="وضعیت همکاران" :count="array_sum($this->stats)" countLabel="نفر"/>
    </div>

    @include('livewire.dashboard.tab.status.partials.filters')

    @include('livewire.dashboard.tab.status.partials.grid')

    <x-dashboard.modal.toast/>
</div>
