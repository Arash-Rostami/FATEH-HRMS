<div dir="rtl" x-data="media()" class="w-full h-[calc(100dvh-60px)] md:h-[max(1000px,calc(100dvh-80px))] relative px-4 py-4 md:px-6 md:py-8 overflow-hidden animate-fade" aria-busy="true" aria-label="در حال بارگذاری کتابخانه">
    <div class="max-w-[88rem] mx-auto page-wrapper h-full flex flex-col gap-4">
        <x-ui.loaders.skeleton.bar width="w-48" height="h-8" />
        <x-ui.loaders.skeleton.bar width="w-64" height="h-12" />
        <div class="flex flex-1 min-h-0 gap-4">
            <div class="w-full md:w-[320px] lg:w-[360px] shrink-0 space-y-4"><x-ui.loaders.skeleton.bar height="h-10" />@for($i = 0; $i < 6; $i++)<x-ui.loaders.skeleton.avatar-row />@endfor</div>
            <div class="hidden md:block flex-1 space-y-4"><x-ui.loaders.skeleton.card /><x-ui.loaders.skeleton.table-stripe :rows="7" /></div>
        </div>
        <x-ui.loaders.skeleton.bar height="h-16" />
    </div>
</div>
