<div x-data="taskboard" class="w-full h-full relative p-4 md:p-8 overflow-y-auto scrollbar-hide">
    <div class="max-w-7xl mx-auto space-y-6">
        @include('livewire.dashboard.taskboard.partials.header')
        @include('livewire.dashboard.taskboard.partials.tabs')
        @include('livewire.dashboard.taskboard.partials.board')
    </div>

    @include('livewire.dashboard.taskboard.partials.create-modal')
    <x-dashboard.modal.confirmation/>
    <x-dashboard.modal.toast/>
</div>
