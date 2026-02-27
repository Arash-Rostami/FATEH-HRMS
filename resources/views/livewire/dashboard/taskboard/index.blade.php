<div dir="rtl" x-data="taskboard" class="w-full h-full relative p-4 md:px-6 md:py-8 overflow-y-auto scrollbar-hide">
    <div class="max-w-7xl mx-auto space-y-5 animate-slideDownFade" style="animation-delay: 0.05s;">
        @include('livewire.dashboard.taskboard.partials.header')
        @include('livewire.dashboard.taskboard.partials.tabs')
        @include('livewire.dashboard.taskboard.partials.board')
    </div>
    @include('livewire.dashboard.taskboard.partials.create')
    @include('livewire.dashboard.taskboard.partials.edit')
    <x-dashboard.modal.confirmation/>
    <x-dashboard.modal.toast/>
</div>
