<div x-data="status" class="w-full flex flex-col gap-4 p-4 md:p-6 animate-fade-in-up">
    {{-- Status Nexus Ribbon --}}
    @include('livewire.dashboard.tab.status.partials.filters')

    {{-- Presence Grid --}}
    @include('livewire.dashboard.tab.status.partials.grid')

    {{-- Toast Notification --}}
    <x-dashboard.modal.toast />
</div>
