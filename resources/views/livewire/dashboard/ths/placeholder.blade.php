<div dir="rtl"
     class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade"
    
     role="status" aria-label="در حال بارگذاری تیکتینگ">
    <div class="max-w-[88rem] mx-auto page-wrapper">
        <div class="mb-8">
            <x-ui.loaders.skeleton.bar width="w-48" height="h-7"/>
        </div>

        <div class="flex gap-2 mb-6">
            <x-ui.loaders.skeleton.bar width="flex-1" height="h-11"/>
            <x-ui.loaders.skeleton.bar width="flex-1" height="h-11"/>
            <x-ui.loaders.skeleton.bar width="flex-1" height="h-11"/>
        </div>

        <x-ui.loaders.skeleton.table-stripe :columns="7" :rows="8"/>
    </div>
</div>
