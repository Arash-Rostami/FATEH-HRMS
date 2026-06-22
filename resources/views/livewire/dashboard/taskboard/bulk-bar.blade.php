@php
    $columnConfig = $presenter->columnConfig();
@endphp

<div
    x-show="$wire.selectionMode && $wire.selectedTasks.length > 0"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-cloak
    class="fixed bottom-4 inset-x-4 md:inset-x-auto md:left-1/2 md:-translate-x-1/2 z-40 flex flex-wrap items-center gap-2 px-4 py-3 rounded-2xl bg-[var(--header-border-color)] border border-[var(--md-sys-color-outline-variant)] shadow-lg"
    dir="rtl"
>
    <x-ui.badge variant="info">
        {{ count($selectedTasks) }} مورد انتخاب شده
    </x-ui.badge>

    <div x-data="{ open: false }" class="relative">
        <button
            @click="open = !open"
            @click.away="open = false"
            class="ripple-effect flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] transition-all duration-200"
        >
            <span class="material-symbols-rounded text-base">swap_horiz</span>
            تغییر وضعیت
        </button>
        <div
            x-show="open"
            x-transition
            x-cloak
            class="absolute bottom-full mb-2 right-0 w-48 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] rounded-xl shadow-lg z-50 overflow-hidden"
        >
            <ul>
                @foreach($columns as $column)
                    <li>
                        <button
                            wire:click="bulkMoveStatus('{{ $column }}')"
                            @click="open = false"
                            class="w-full text-right px-3 py-2 text-xs font-medium text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-container-high)] transition-colors"
                        >
                            {{ $columnConfig[$column]['title'] }}
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div x-data="{ open: false }" class="relative">
        <button
            @click="open = !open"
            @click.away="open = false"
            class="ripple-effect flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] transition-all duration-200"
        >
            <span class="material-symbols-rounded text-base">person_add</span>
            ارجاع به
        </button>
        <div
            x-show="open"
            x-transition
            x-cloak
            class="absolute bottom-full mb-2 right-0 w-48 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] rounded-xl shadow-lg z-50 overflow-hidden max-h-48 overflow-y-auto custom-scrollbar"
        >
            <ul>
                @foreach($staffMembers as $member)
                    <li>
                        <button
                            wire:click="bulkAssign({{ $member['id'] }})"
                            @click="open = false"
                            class="w-full text-right px-3 py-2 text-xs font-medium text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-surface-container-high)] transition-colors"
                        >
                            {{ $member['full_name'] }}
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <button
        wire:click="bulkDelete"
        wire:confirm="آیا از حذف موارد انتخاب‌شده اطمینان دارید؟"
        class="ripple-effect flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold text-[var(--md-sys-color-error)] hover:bg-[var(--md-sys-color-error-container)] transition-all duration-200"
    >
        <span class="material-symbols-rounded text-base">delete</span>
        حذف
    </button>

    <button
        wire:click="toggleSelectionMode"
        class="ripple-effect flex items-center justify-center w-8 h-8 rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] transition-all duration-200 mr-auto"
        title="بستن"
    >
        <span class="material-symbols-rounded text-base">close</span>
    </button>
</div>
