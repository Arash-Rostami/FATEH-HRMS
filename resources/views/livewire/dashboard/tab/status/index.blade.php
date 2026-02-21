<div class="w-full flex flex-col gap-4 p-4 md:p-6 animate-fade-in-up p-4 md:p-8">
    @include('livewire.dashboard.tab.status.partials.filters')

    @include('livewire.dashboard.tab.status.partials.grid')

    <x-dashboard.modal.toast />
</div>
