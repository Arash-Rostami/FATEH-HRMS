<div dir="rtl"
     x-data="taskboard()"
     class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade"
     style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;">
    <div class="max-w-[88rem] mx-auto page-wrapper">

        <!-- Sections with staggered animation -->
        @include('livewire.dashboard.taskboard.partials.header')
        @include('livewire.dashboard.taskboard.partials.tabs')
        @include('livewire.dashboard.taskboard.partials.board')
    </div>

    @include('livewire.dashboard.taskboard.partials.create')
    @include('livewire.dashboard.taskboard.partials.edit')
    <x-dashboard.modal.confirmation/>
</div>

