@props(['kpis' => 4, 'rows' => 4])

<div aria-busy="true" {{ $attributes->merge(['class' => 'relative flex flex-col gap-4 w-full text-start print:hidden [&_.animate-pulse]:!animate-none']) }}>
    <span class="sr-only">در حال آماده‌سازی گزارش…</span>
    <x-ui.loaders.ai-shimmer class="rounded-2xl"/>
    <div class="flex flex-col gap-4" aria-hidden="true">
        <x-ui.loaders.skeleton.bar width="w-1/2" height="h-5"/>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            @for($i = 0; $i < $kpis; $i++)
                <div class="flex flex-col gap-2 p-3.5 rounded-xl bg-[var(--md-sys-color-surface-container-low)] border border-[var(--md-sys-color-outline-variant)]/40">
                    <x-ui.loaders.skeleton.bar width="w-1/2" height="h-3"/>
                    <x-ui.loaders.skeleton.bar width="w-3/4" height="h-4"/>
                </div>
            @endfor
        </div>
        <div class="flex flex-col gap-2">
            <x-ui.loaders.skeleton.bar width="w-full" height="h-3.5"/>
            <x-ui.loaders.skeleton.bar width="w-11/12" height="h-3.5"/>
            <x-ui.loaders.skeleton.bar width="w-2/3" height="h-3.5"/>
        </div>
        <x-ui.loaders.skeleton.table-stripe :columns="3" :rows="$rows"/>
    </div>
</div>
