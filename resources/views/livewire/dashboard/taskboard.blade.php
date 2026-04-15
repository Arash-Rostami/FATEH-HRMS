<div dir="rtl"
     x-data="taskboard()"
     class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade"
     style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;">
    <div class="max-w-[88rem] mx-auto page-wrapper">

        @include('livewire.dashboard.taskboard.header')

        <x-ui.buttons.tab-selector
                :active-tab="$activeTab"
                :tabs="[
            ['id' => 'my-tasks', 'icon' => 'person', 'label' => 'وظایف من'],
            ['id' => 'assigned-tasks', 'icon' => 'assignment_ind', 'label' => 'محول شده']
        ]"/>


        <div
            class="flex flex-col md:flex-row !justify-center overflow-x-auto gap-3 md:gap-4 pb-6 pt-2 snap-x snap-mandatory md:snap-none scroll-px-4 md:scroll-px-0"
            style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;">
            @foreach($columns as $column)
                <div
                    class="snap-center  md:snap-align-none shrink-0 w-full sm:w-[calc(100%-2rem)] md:w-1/3 md:flex-1 min-w-[280px] sm:min-w-[320px] md:min-w-[350px] max-w-full md:max-w-md">
                    @include('livewire.dashboard.taskboard.column', ['column' => $column])
                </div>
            @endforeach
        </div>


    </div>

    @include('livewire.dashboard.taskboard.create')

    @include('livewire.dashboard.taskboard.edit')

    <x-ui.modals.confirmation/>
</div>

