@props(['postTitle', 'postBody', 'trigger'])

<div class="relative" x-data="{ open: false }" @click.away="open = false">
    <button
        @click="open = !open; openShare('{{ addslashes(superClean($postTitle, 200)) }}', '{{ addslashes(superClean($postBody, 300)) }}')"
        class="{{ $trigger->attributes->class(['flex items-center gap-2 px-5 py-2.5 rounded-xl hover:bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-secondary-container)] transition-colors font-bold text-sm group']) }}"
    >
        {{ $trigger }}
    </button>

    {{-- Share Popover --}}
    <div
        class="absolute bottom-full left-0 mb-2 w-48 bg-[var(--md-sys-color-surface)] rounded-xl shadow-xl border border-[var(--md-sys-color-outline-variant)]/20 overflow-hidden transform origin-bottom-left transition-all duration-200"
        x-show="open"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
        style="display: none;"
    >
        <div class="flex flex-col py-1">
            <button
                @click="copyToClipboard();"
                class="flex items-center gap-3 px-4 py-3 hover:bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-on-surface)] text-sm transition-colors text-right"
            >
                <span
                    class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">content_copy</span>
                <span>کپی متن</span>
            </button>
            <button
                @click="sendEmail(); open = false"
                class="flex items-center gap-3 px-4 py-3 hover:bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-on-surface)] text-sm transition-colors text-right border-t border-[var(--md-sys-color-outline-variant)]/20"
            >
                <span
                    class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">mail</span>
                <span>ایمیل</span>
            </button>
        </div>
    </div>
</div>
