<div dir="rtl" class="w-full h-full px-4 py-4 md:px-6 md:py-8" role="status" aria-label="در حال بارگذاری پرسشنامه انرژی">
    <div class="max-w-[88rem] mx-auto page-wrapper flex flex-col gap-5">
        <x-ui.title icon="energy" title="پرسشنامه انرژی">
            <x-slot:actions>
                <x-ui.loaders.skeleton.bar width="w-24" height="h-7"/>
                <x-ui.loaders.skeleton.bar width="w-8" height="h-8"/>
                <x-ui.loaders.skeleton.bar width="w-16" height="h-6"/>
            </x-slot:actions>
        </x-ui.title>

        <div class="max-w-2xl mx-auto w-full px-4">
            <x-ui.loaders.skeleton.card :lines="2" class="min-h-[380px]"/>
        </div>
    </div>
</div>
