<div
    class="flex flex-col md:flex-row !justify-center overflow-x-auto gap-3 md:gap-4 pb-6 pt-2 snap-x snap-mandatory md:snap-none scroll-px-4 md:scroll-px-0"
    style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;">
    @foreach($columns as $column)
        <div
            class="snap-center  md:snap-align-none shrink-0 w-full sm:w-[calc(100%-2rem)] md:w-1/3 md:flex-1 min-w-[280px] sm:min-w-[320px] md:min-w-[350px] max-w-full md:max-w-md">
            @include('livewire.dashboard.taskboard.partials.column', ['column' => $column])
        </div>
    @endforeach
</div>
