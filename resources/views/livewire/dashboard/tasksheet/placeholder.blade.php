<div dir="rtl"
     class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade"
     role="status" aria-label="در حال بارگذاری تسک‌شیت">
    <div class="max-w-[64rem] mx-auto page-wrapper space-y-4">
        <div class="flex items-center justify-between mb-4">
            <x-ui.loaders.skeleton.bar width="w-40" height="h-7"/>
            <div class="flex gap-2">
                <x-ui.loaders.skeleton.bar width="w-24" height="h-9"/>
                <x-ui.loaders.skeleton.bar width="w-24" height="h-9"/>
            </div>
        </div>

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
