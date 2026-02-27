<div dir="rtl" x-data="taskboard" class="w-full h-full relative p-4 md:p-8 overflow-y-auto scrollbar-hide">
    <div class="max-w-[85rem] mx-auto space-y-6">
        @include('livewire.dashboard.taskboard.partials.header')
        @include('livewire.dashboard.taskboard.partials.tabs')
        @include('livewire.dashboard.taskboard.partials.board')
    </div>

    @include('livewire.dashboard.taskboard.partials.create')
    @include('livewire.dashboard.taskboard.partials.edit')
    <x-dashboard.modal.confirmation/>
    <x-dashboard.modal.toast/>
</div>
