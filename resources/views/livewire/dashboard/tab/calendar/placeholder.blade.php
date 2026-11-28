<div dir="rtl" class="relative w-full max-w-[88rem] mx-auto flex flex-col gap-4 h-auto" role="status" aria-label="در حال بارگذاری تقویم">
    <x-ui.title icon="calendar_month" title="تقویم کاری">
        <x-slot:actions>
            <x-ui.loaders.skeleton.bar width="w-8" height="h-8"/>
            <x-ui.loaders.skeleton.bar width="w-16" height="h-6"/>
        </x-slot:actions>
    </x-ui.title>

    <div class="grid grid-cols-1 lg:grid-cols-10 gap-4">
        <div class="lg:col-span-3 flex flex-col gap-2">
            @for($i = 0; $i < 5; $i++)
                <x-ui.loaders.skeleton.avatar-row :lines="2"/>
            @endfor
        </div>

        <x-ui.loaders.skeleton.card :lines="0" class="lg:col-span-7 min-h-[500px]"/>
    </div>
</div>
