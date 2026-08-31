<div dir="rtl" class="w-full h-full px-4 py-4 md:px-6 md:py-8" role="status" aria-label="در حال بارگذاری پیشنهادات">
    <div class="max-w-[88rem] mx-auto flex flex-col gap-4">
        <x-ui.loaders.skeleton.bar width="w-32" height="h-7"/>

        <div class="flex flex-col md:flex-row gap-4">
            <aside class="w-full md:w-80 shrink-0 flex flex-col gap-3">
                <x-ui.loaders.skeleton.bar width="w-full" height="h-10"/>
                @for($i = 0; $i < 4; $i++)
                    <x-ui.loaders.skeleton.card :lines="2"/>
                @endfor
            </aside>

            <main class="flex-1 min-w-0 flex flex-col gap-3">
                <x-ui.loaders.skeleton.card :lines="2"/>
                <x-ui.loaders.skeleton.card :lines="2"/>
            </main>
        </div>
    </div>
</div>
