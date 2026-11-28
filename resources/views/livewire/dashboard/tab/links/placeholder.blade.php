<div class="animate-fade h-full w-full max-w-[88rem] mx-auto max-h-[calc(100svh-10rem)] relative overflow-hidden space-y-6 pb-6" dir="rtl" role="status" aria-label="در حال بارگذاری لینک‌ها">
    <x-ui.title icon="open_in_new" title="لینک‌ها و مسیرهای دیجیتال سازمان">
        <x-slot:actions>
            <x-ui.loaders.skeleton.bar width="w-8" height="h-8"/>
            <x-ui.loaders.skeleton.bar width="w-16" height="h-6"/>
        </x-slot:actions>
    </x-ui.title>

    @for($section = 0; $section < 3; $section++)
        <div class="flex flex-col gap-3">
            <x-ui.loaders.skeleton.bar width="w-40" height="h-5"/>
            <div class="flex items-center gap-3 overflow-hidden">
                @for($i = 0; $i < 4; $i++)
                    <x-ui.loaders.skeleton.card :lines="1" class="flex-shrink-0 w-48 h-24"/>
                @endfor
            </div>
        </div>
    @endfor
</div>
