<div class="animate-fade w-full max-w-[88rem] mx-auto !pt-0 pb-10" dir="rtl" role="status" aria-label="در حال بارگذاری خانه">
    <x-ui.loaders.skeleton.bar width="w-24" height="h-7" class="mb-4"/>

    <x-ui.loaders.skeleton.card :lines="1" class="mb-4 min-h-[200px]"/>

    <div class="flex items-center gap-3 mb-8 overflow-hidden">
        @for($i = 0; $i < 7; $i++)
            <x-ui.loaders.skeleton.bar width="w-16" height="h-16" class="flex-shrink-0 rounded-2xl"/>
        @endfor
    </div>

    <div class="flex flex-col gap-2">
        @for($i = 0; $i < 5; $i++)
            <x-ui.loaders.skeleton.bar width="w-full" height="h-12"/>
        @endfor
    </div>
</div>
