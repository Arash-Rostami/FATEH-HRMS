
<x-dashboard.tab.title
    icon="dashboard"
    title="برد وظایف"
    :count="array_sum($totalCount)"
    countLabel="وظیفه">
    <x-slot:actions>
        <button
            x-data
            wire:click="openCreateModal"
            class="group relative flex items-center justify-center gap-2 px-3 py-1.5 rounded-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm hover:shadow-md hover:shadow-[var(--md-sys-color-primary)]/30 active:scale-95 transition-all duration-300"
        >
            <span class="material-symbols-rounded text-lg group-hover:rotate-90 transition-transform duration-300">add</span>
            <span class="text-xs font-bold tracking-wide">وظیفه جدید</span>
        </button>
    </x-slot:actions>
</x-dashboard.tab.title>
