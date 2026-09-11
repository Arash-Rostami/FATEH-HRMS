<div class="mt-4 flex flex-col gap-4 animate-fade" @project-report-refresh.window="$wire.refreshReport()" role="status" aria-label="در حال بارگذاری گزارش">
    <x-ui.loaders.skeleton.bar width="w-full" height="h-14" class="rounded-2xl"/>
    <x-ui.loaders.skeleton.table-stripe :columns="6" :rows="6"/>
</div>
