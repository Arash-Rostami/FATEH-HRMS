<div dir="rtl"
     class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade"
    
     role="status" aria-label="در حال بارگذاری اسناد">
    <div class="max-w-[88rem] mx-auto page-wrapper">
        <x-ui.title icon="folder_open" title="مدیریت اسناد">
            <x-slot:actions>
                <x-ui.loaders.skeleton.bar width="w-24" height="h-7"/>
                <x-ui.loaders.skeleton.bar width="w-8" height="h-8"/>
                <x-ui.loaders.skeleton.bar width="w-16" height="h-6"/>
            </x-slot:actions>
        </x-ui.title>

        <div class="mb-6 flex items-center gap-3">
            <x-ui.loaders.skeleton.bar width="flex-1" height="h-11"/>
            <x-ui.loaders.skeleton.bar width="w-28" height="h-11"/>
        </div>

        <x-ui.loaders.skeleton.table-stripe :columns="7" :rows="8"/>
    </div>
</div>
