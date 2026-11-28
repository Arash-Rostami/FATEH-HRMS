<div dir="rtl"
     class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade"
     role="status" aria-label="در حال بارگذاری برد وظایف">
    <div class="max-w-[88rem] mx-auto page-wrapper">
        <x-ui.title icon="dashboard" title="برد وظایف">
            <x-slot:actions>
                <x-ui.loaders.skeleton.bar width="w-24" height="h-7"/>
                <x-ui.loaders.skeleton.bar width="w-8" height="h-8"/>
                <x-ui.loaders.skeleton.bar width="w-16" height="h-6"/>
            </x-slot:actions>
        </x-ui.title>

        <div class="w-full overflow-x-auto flex items-start gap-4 pb-4">
            @for($i = 0; $i < 4; $i++)
                <x-ui.loaders.skeleton.column-stack :cards="3"/>
            @endfor
        </div>
    </div>
</div>
