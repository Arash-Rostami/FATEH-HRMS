<div class="animate-fade" x-data="{ moreOpen: false }" @project-kanban-refresh.window="$wire.loadKanbanBoard()" role="status" aria-label="در حال بارگذاری برد وظایف">
    <div class="flex items-center gap-2.5 p-2 mb-3 rounded-xl bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-low)_88%,transparent)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
        <x-ui.loaders.skeleton.bar width="w-full max-w-xs" height="h-9" class="rounded-xl"/>
        <x-ui.loaders.skeleton.bar width="w-28" height="h-7" class="rounded-lg"/>
    </div>

    <div class="w-full overflow-x-auto flex items-start gap-3 md:gap-4 pb-4">
        @for($i = 0; $i < 4; $i++)
            <x-ui.loaders.skeleton.column-stack :cards="3"/>
        @endfor
    </div>
</div>
