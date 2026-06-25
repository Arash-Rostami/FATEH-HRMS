<div dir="rtl"
     x-data="taskboard()"
     @keydown.escape.window="if(maximizedColumn) toggleMaximize(null)"
     class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade container-scrollbar custom-scrollbar">

    <x-ui.modals.max-backdrop state="maximizedColumn" close="toggleMaximize(null)"/>

    <div class="max-w-[88rem] mx-auto page-wrapper">
        <x-ui.title
            icon="dashboard"
            title="برد وظایف"
            :count="array_sum($totalCount)"
            countLabel="وظیفه">
        </x-ui.title>

        @include('components.dashboard.header.focus-chip')


        <x-ui.buttons.tab-selector
            :active-tab="$activeTab"
            :tabs="[
            ['id' => 'my-tasks', 'icon' => 'person', 'label' => 'وظایف من'],
            ['id' => 'assigned-tasks', 'icon' => 'assignment_ind', 'label' => 'محول شده']
        ]"/>


        @include('livewire.dashboard.taskboard.tools')


        <div
            class="flex flex-col md:flex-row flex-1 min-h-0 items-start overflow-x-auto gap-3 md:gap-4 pb-2 pt-2 snap-x snap-mandatory md:snap-none scroll-px-4 md:scroll-px-0">
            @foreach($columns as $column)
                <div
                    class="snap-center shrink-0 w-full sm:w-[calc(100%-2rem)] md:w-1/3 md:flex-1 min-w-[280px] sm:min-w-[320px] md:min-w-[350px] max-w-full md:max-w-md">
                    @include('livewire.dashboard.taskboard.column', ['column' => $column])
                </div>
            @endforeach
        </div>
    </div>

    @include('livewire.dashboard.taskboard.form')

    @include('livewire.dashboard.taskboard.bulk-bar')

    <x-ui.modals.confirmation/>
</div>

