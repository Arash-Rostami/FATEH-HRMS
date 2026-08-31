@php
    $columnConfig = $presenter->columnConfig();
@endphp

<div x-show="$wire.selectionMode && $wire.selectedTasks.length > 0"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 translate-y-4"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-cloak
     class="fixed bottom-4 inset-x-4 z-40 flex flex-wrap items-center gap-2 px-4 py-3 shadow-lg rounded-2xl border md:inset-x-auto md:left-1/2 md:-translate-x-1/2 bg-[var(--header-border-color)] border-[var(--md-sys-color-outline-variant)]"
     dir="rtl">

    <x-ui.badge variant="info">
        {{ count($selectedTasks) }} مورد انتخاب شده
    </x-ui.badge>

    <div x-data="{ open: false }" class="relative">
        <button @click="open = !open"
                @click.away="open = false"
                class="flex items-center gap-1 px-3 py-1.5 text-xs font-bold transition-all duration-200 rounded-xl ripple-effect text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]">
            <span class="text-base material-symbols-rounded">swap_horiz</span>
            تغییر وضعیت
        </button>

        <div x-show="open"
             x-transition
             x-cloak
             class="absolute right-0 z-50 w-48 mb-2 overflow-hidden border shadow-lg bottom-full rounded-xl bg-[var(--md-sys-color-surface)] border-[var(--md-sys-color-outline-variant)]">
            <ul>
                @foreach($columns as $column)
                    <li>
                        <x-ui.buttons.form variant="ghost"
                                           wire:click="bulkMoveStatus('{{ $column }}')"
                                           loading="bulkMoveStatus('{{ $column }}')"
                                           wire:loading.attr="disabled"
                                           @click="open = false"
                                           class="!h-auto !w-full !justify-start !rounded-none !px-3 !py-2 text-xs font-medium transition-colors hover:!bg-[var(--md-sys-color-surface-container-high)]">
                            {{ $columnConfig[$column]['title'] }}
                        </x-ui.buttons.form>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div x-data="{ open: false }" class="relative">
        <button @click="open = !open"
                @click.away="open = false"
                class="flex items-center gap-1 px-3 py-1.5 text-xs font-bold transition-all duration-200 rounded-xl ripple-effect text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]">
            <span class="text-base material-symbols-rounded">person_add</span>
            ارجاع به
        </button>

        <div x-show="open"
             x-transition
             x-cloak
             class="absolute right-0 z-50 w-48 max-h-48 mb-2 overflow-hidden overflow-y-auto border shadow-lg bottom-full rounded-xl custom-scrollbar bg-[var(--md-sys-color-surface)] border-[var(--md-sys-color-outline-variant)]">
            <ul>
                @foreach($staffMembers as $member)
                    <li>
                        <x-ui.buttons.form variant="ghost"
                                           wire:click="bulkAssign({{ $member['id'] }})"
                                           loading="bulkAssign({{ $member['id'] }})"
                                           wire:loading.attr="disabled"
                                           @click="open = false"
                                           class="!h-auto !w-full !justify-start !rounded-none !px-3 !py-2 text-xs font-medium transition-colors hover:!bg-[var(--md-sys-color-surface-container-high)]">
                            {{ $member['full_name'] }}
                        </x-ui.buttons.form>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <x-ui.buttons.form variant="ghost"
                       wire:click="bulkDelete"
                       wire:confirm="آیا از حذف موارد انتخاب‌شده اطمینان دارید؟"
                       loading="bulkDelete"
                       wire:loading.attr="disabled"
                       icon="delete"
                       class="!h-auto !px-3 !py-1.5 text-xs font-bold !text-[var(--md-sys-color-error)] hover:!bg-[var(--md-sys-color-error-container)]">
        حذف
    </x-ui.buttons.form>

    <button wire:click="toggleSelectionMode"
            class="flex items-center justify-center w-8 h-8 mr-auto transition-all duration-200 rounded-xl ripple-effect text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]"
            title="بستن">
        <span class="text-base material-symbols-rounded">close</span>
    </button>
</div>
