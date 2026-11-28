<div dir="rtl"
     class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade"
     role="status" aria-label="در حال بارگذاری تسک‌شیت">
    <div class="max-w-[64rem] mx-auto page-wrapper space-y-4">
        <x-ui.title icon="assignment_turned_in" title="تسک‌شیت">
            <x-slot:actions>
                <x-ui.loaders.skeleton.bar width="w-24" height="h-7"/>
                <x-ui.loaders.skeleton.bar width="w-24" height="h-7"/>
                <x-ui.loaders.skeleton.bar width="w-10" height="h-10"/>
                <x-ui.loaders.skeleton.bar width="w-8" height="h-8"/>
            </x-slot:actions>
        </x-ui.title>

        <x-ui.loaders.skeleton.card/>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <x-ui.loaders.skeleton.card/>
            <x-ui.loaders.skeleton.card/>
            <x-ui.loaders.skeleton.card/>
        </div>

        <x-ui.loaders.skeleton.table-stripe/>
        <x-ui.loaders.skeleton.table-stripe/>
    </div>
</div>
