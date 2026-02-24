<div class="flex overflow-x-auto gap-4 md:gap-6 pb-6 pt-2 snap-x snap-mandatory scroll-pl-6 custom-scrollbar scrollbar-hide">
    @foreach($columns as $column)
        <div class="snap-center md:snap-align-none shrink-0 w-full sm:w-[calc(100%-4rem)] md:w-1/3 min-w-[320px] max-w-md">
            @include('livewire.dashboard.taskboard.partials.column', ['column' => $column])
        </div>
    @endforeach
</div>
