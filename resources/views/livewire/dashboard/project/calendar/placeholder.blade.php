<div class="w-full h-full flex flex-col gap-5 md:gap-6 animate-fade" x-data="{ calView: 'grid' }" @project-calendar-refresh.window="$wire.refreshCalendar()" role="status" aria-label="در حال بارگذاری تقویم">
    <div class="flex flex-col md:flex-row items-center justify-between gap-4 px-1">
        <div class="flex items-center gap-2">
            <x-ui.loaders.skeleton.bar width="w-9" height="h-9" class="rounded-lg"/>
            <x-ui.loaders.skeleton.bar width="w-28" height="h-4"/>
            <x-ui.loaders.skeleton.bar width="w-9" height="h-9" class="rounded-lg"/>
        </div>
        <div class="flex items-center gap-1.5">
            @for($i = 0; $i < 4; $i++)
                <x-ui.loaders.skeleton.bar width="w-16" height="h-7" class="rounded-lg"/>
            @endfor
        </div>
    </div>

    <div class="grid grid-cols-7 gap-1.5 md:gap-2 px-1 md:px-2">
        @for($i = 0; $i < 35; $i++)
            <x-ui.loaders.skeleton.bar width="w-full" height="h-14" class="aspect-[1/0.85] rounded-[14px]"/>
        @endfor
    </div>

    <x-ui.loaders.skeleton.table-stripe :columns="4" :rows="4"/>
</div>
