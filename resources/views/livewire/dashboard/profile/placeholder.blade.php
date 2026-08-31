<div dir="rtl" class="relative w-full h-full p-4 md:p-8" role="status" aria-label="در حال بارگذاری پروفایل">
    <div class="max-w-[88rem] mx-auto flex flex-col gap-5">
        <x-ui.loaders.skeleton.bar width="w-40" height="h-7"/>

        <div class="flex flex-col lg:flex-row gap-6 items-start">
            <aside class="w-full lg:w-64 flex-shrink-0 flex flex-col gap-2 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] rounded-2xl p-3">
                @for ($i = 0; $i < 6; $i++)
                    <x-ui.loaders.skeleton.bar width="w-full" height="h-10"/>
                @endfor
            </aside>

            <x-ui.loaders.skeleton.card :lines="2" class="flex-1 w-full min-h-[600px]"/>
        </div>
    </div>
</div>
