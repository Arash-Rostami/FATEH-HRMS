<div class="animate-fade h-full w-full max-w-[88rem] mx-auto max-h-[calc(100svh-10rem)] relative overflow-hidden flex flex-col gap-6" dir="rtl" role="status" aria-label="در حال بارگذاری اعلانات">
    <div class="flex items-center justify-between">
        <x-ui.loaders.skeleton.bar width="w-32" height="h-8"/>
        <x-ui.loaders.skeleton.bar width="w-20" height="h-8"/>
    </div>

    <div class="flex-1 w-full relative overflow-hidden flex flex-col lg:flex-row gap-6">
        <div class="w-full lg:w-1/3 xl:w-2/5 flex-shrink-0 flex flex-col gap-3">
            @for($i = 0; $i < 2; $i++)
                <x-ui.loaders.skeleton.card :lines="2" class="h-52"/>
            @endfor
        </div>

        <div class="flex-1 min-w-0 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-3 gap-5">
            @for($i = 0; $i < 6; $i++)
                <x-ui.loaders.skeleton.card :lines="2"/>
            @endfor
        </div>
    </div>
</div>
