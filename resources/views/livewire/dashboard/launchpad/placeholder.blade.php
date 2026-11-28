<div dir="rtl" x-data="launchpadPage" class="{{ request('embed') ? 'w-full h-[calc(100dvh-60px)] md:h-[max(1000px,calc(100dvh-80px))] overflow-hidden' : 'w-full h-full overflow-y-auto px-4 py-4 md:px-6 md:py-8' }}">
    <div class="max-w-[88rem] mx-auto page-wrapper flex flex-col gap-4">
        <x-ui.title icon="apps" title="پیشخوان برنامه‌ها">
            <x-slot:actions>
                <x-ui.loaders.skeleton.bar width="w-24" height="h-7"/>
                <x-ui.loaders.skeleton.bar width="w-8" height="h-8"/>
                <x-ui.loaders.skeleton.bar width="w-16" height="h-6"/>
            </x-slot:actions>
        </x-ui.title>
        <x-ui.loaders.skeleton.table-stripe :columns="4" :rows="6"/>
    </div>
</div>
