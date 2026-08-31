<div class="mt-4 flex flex-col gap-4 animate-fade" role="status" aria-label="در حال بارگذاری تحلیل‌ها">
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
        @for ($i = 0; $i < 3; $i++)
            <x-ui.loaders.skeleton.bar width="w-full" height="h-14" class="rounded-xl"/>
        @endfor
    </div>
    <x-ui.loaders.skeleton.card :lines="1" class="min-h-[340px]"/>
</div>
