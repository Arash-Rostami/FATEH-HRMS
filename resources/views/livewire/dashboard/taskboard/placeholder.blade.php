<div dir="rtl"
     class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade"
     role="status" aria-label="در حال بارگذاری برد وظایف">
    <div class="max-w-[88rem] mx-auto page-wrapper">
        <div class="mb-8">
            <x-ui.loaders.skeleton.bar width="w-40" height="h-7"/>
        </div>

        <div class="w-fit flex gap-2 mb-6">
            <x-ui.loaders.skeleton.bar width="w-28" height="h-10"/>
            <x-ui.loaders.skeleton.bar width="w-28" height="h-10"/>
        </div>

        <div class="w-full overflow-x-auto flex items-start gap-4 pb-4">
            @for($i = 0; $i < 4; $i++)
                <x-ui.loaders.skeleton.column-stack :cards="3"/>
            @endfor
        </div>
    </div>
</div>
