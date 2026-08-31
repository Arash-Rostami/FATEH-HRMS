<div class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade" dir="rtl" role="status" aria-label="در حال بارگذاری سامانه رزرواسیون">
    <div class="max-w-[88rem] mx-auto page-wrapper">
        <div class="flex items-center justify-between mb-6">
            <x-ui.loaders.skeleton.bar width="w-56" height="h-8"/>
            <x-ui.loaders.skeleton.bar width="w-8" height="h-8"/>
        </div>

        <div class="flex flex-wrap items-center gap-2 mb-6">
            <x-ui.loaders.skeleton.bar width="w-32" height="h-10"/>
            <x-ui.loaders.skeleton.bar width="w-32" height="h-10"/>
            <x-ui.loaders.skeleton.bar width="w-32" height="h-10"/>
        </div>

        <div class="flex items-center gap-3 mb-8 overflow-hidden">
            @for($i = 0; $i < 7; $i++)
                <x-ui.loaders.skeleton.bar width="w-16" height="h-16"/>
            @endfor
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 pb-12 w-full items-start">
            <div class="lg:col-span-8 xl:col-span-9 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 md:gap-6 min-w-0">
                @for($i = 0; $i < 4; $i++)
                    <x-ui.loaders.skeleton.card :lines="2"/>
                @endfor
            </div>

            <div class="lg:col-span-4 xl:col-span-3 min-w-0 flex flex-col gap-2">
                <x-ui.loaders.skeleton.bar width="w-32" height="h-5"/>
                @for($i = 0; $i < 5; $i++)
                    <x-ui.loaders.skeleton.avatar-row :lines="2"/>
                @endfor
            </div>
        </div>
    </div>
</div>
