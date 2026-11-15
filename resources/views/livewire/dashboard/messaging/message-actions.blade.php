<div @class([
            'absolute top-1/2 -translate-y-1/2 flex items-center gap-0.5 px-1 py-1 rounded-lg opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-all duration-200 z-20 bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-[0_4px_20px_color-mix(in_srgb,var(--md-sys-color-shadow)_12%,transparent)] scale-90 group-hover:scale-100 group-focus-within:scale-100',
            'left-0 -translate-x-[calc(100%+6px)]' => $msg['is_mine'],
            'right-0 translate-x-[calc(100%+6px)]' => !$msg['is_mine']
        ])
     :class="openActionsId === {{ $msg['id'] }} ? 'opacity-100 scale-100' : ''">
    <button x-on:click="$store.pinned.togglePin(@js($msg['id']), @js('message'))"
            :aria-pressed="$store.pinned.isPinned(@js($msg['id']), @js('message'))"
            :aria-label="$store.pinned.isPinned(@js($msg['id']), @js('message')) ? @js('برداشتن نشان پیام') : @js('نشان کردن پیام')"
            :title="$store.pinned.isPinned(@js($msg['id']), @js('message')) ? @js('برداشتن نشان پیام') : @js('نشان کردن پیام')"
            class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:scale-110 active:scale-90"
            :class="$store.pinned.isPinned(@js($msg['id']), @js('message'))
                      ? 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]'
                      : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-tertiary)_10%,transparent)] hover:text-[var(--md-sys-color-tertiary)]'">
        <span class="material-symbols-rounded text-[15px]"
              :class="$store.pinned.isPinned(@js($msg['id']), @js('message')) ? 'font-fill' : ''">bookmark</span>
    </button>
    <button x-on:click="copyMessage($el.dataset.text)"
            data-text="{{ strip_tags($msg['body_html'] . (($msg['forwarded_html'] ?? '') !== '' ? "\n" . $msg['forwarded_html'] : '')) }}"
            class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-tertiary)_10%,transparent)] hover:text-[var(--md-sys-color-tertiary)] hover:scale-110 active:scale-90 text-[var(--md-sys-color-on-surface-variant)]"
            title="کپی" aria-label="کپی">
        <span class="material-symbols-rounded text-[15px]">content_copy</span>
    </button>
    @if(count($msg['attachments'] ?? []) === 1)
        <button type="button" wire:click="downloadAttachment({{ $msg['id'] }}, 0)"
                class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-tertiary)_10%,transparent)] hover:text-[var(--md-sys-color-tertiary)] hover:scale-110 active:scale-90 text-[var(--md-sys-color-on-surface-variant)]"
                title="دانلود پیوست" aria-label="دانلود پیوست">
            <span class="material-symbols-rounded text-[15px]">download</span>
        </button>
    @endif
    <button x-on:click.prevent="startReply({{ $msg['id'] }}, $el.dataset.sender, $el.dataset.body)"
            data-sender="{{ $msg['sender_name'] }}"
            data-body="{{ strip_tags($msg['plain_text'] ?? $msg['body']) }}"
            class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)] hover:scale-110 active:scale-90 text-[var(--md-sys-color-on-surface-variant)]"
            title="پاسخ" aria-label="پاسخ">
        <span class="material-symbols-rounded text-[15px]">reply</span>
    </button>
    @if($group ?? false)
        <button type="button"
                x-on:click.prevent="if (max) toggleMaximize(null); $store.activityReactionPicker.open('ch{{ $msg['id'] }}', $el)"
                class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)] hover:scale-110 active:scale-90 text-[var(--md-sys-color-on-surface-variant)]"
                title="افزودن واکنش" aria-label="افزودن واکنش">
            <span class="material-symbols-rounded text-[15px]">add_reaction</span>
        </button>
        <button type="button"
                wire:click="toggleGroupPin({{ $msg['id'] }})"
                wire:island="messages"
                wire:loading.attr="disabled" wire:target="toggleGroupPin"
                @class([
                    'w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:scale-110 active:scale-90 disabled:opacity-50',
                    'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]' => ($pinnedId ?? null) === $msg['id'],
                    'hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-surface-variant)]' => ($pinnedId ?? null) !== $msg['id'],
                ])
                title="سنجاق گروه" aria-label="سنجاق گروه">
            <span @class(['material-symbols-rounded text-[15px]', 'font-fill' => ($pinnedId ?? null) === $msg['id']])>push_pin</span>
        </button>
    @endif
    <button type="button"
            x-on:click.prevent="openActionsId = null; if (max) toggleMaximize(null); $wire.openForward({{ $msg['id'] }})"
            wire:loading.attr="disabled" wire:target="openForward"
            class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)] hover:scale-110 active:scale-90 text-[var(--md-sys-color-on-surface-variant)] disabled:opacity-50"
            title="بازفرستادن" aria-label="بازفرستادن پیام">
        <span class="material-symbols-rounded text-[15px] rotate-180">reply</span>
    </button>
    @if($msg['can_edit'])
        <button x-on:click.prevent="startEdit({{ $msg['id'] }}, $el.dataset.body)"
                data-body="{{ $msg['body'] }}"
                class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-secondary)_10%,transparent)] hover:text-[var(--md-sys-color-secondary)] hover:scale-110 active:scale-90 text-[var(--md-sys-color-on-surface-variant)]"
                title="ویرایش" aria-label="ویرایش">
            <span class="material-symbols-rounded text-[15px]">edit</span>
        </button>
    @endif
    @if($msg['can_delete'])
        <button x-on:click.prevent="confirmDelete({{ $msg['id'] }})"
                :disabled="showUndo"
                :class="showUndo ? 'opacity-40 pointer-events-none' : ''"
                class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-error)_10%,transparent)] hover:scale-110 active:scale-90 text-[color-mix(in_srgb,var(--md-sys-color-error)_80%,var(--md-sys-color-on-surface-variant))]"
                title="حذف" aria-label="حذف">
            <span class="material-symbols-rounded text-[15px]">delete</span>
        </button>
    @endif
</div>

<div x-show="$store.pinned.isPinned(@js($msg['id']), @js('message'))" x-cloak
     @class([
         'absolute top-0 z-20 flex items-center justify-center w-5 h-5 rounded-md bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm ring-2 ring-[var(--md-sys-color-surface)] pointer-events-none',
         'right-0 -translate-y-1/2 translate-x-1/2' => $msg['is_mine'],
         'left-0 -translate-y-1/2 -translate-x-1/2' => !$msg['is_mine'],
     ])>
    <span class="material-symbols-rounded text-[10px] font-fill">bookmark</span>
</div>

@if(($pinnedId ?? null) === $msg['id'])
    <div wire:key="messaging-pin-badge" @class([
             'absolute top-0 z-20 flex items-center justify-center w-5 h-5 rounded-md bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm ring-2 ring-[var(--md-sys-color-surface)] pointer-events-none',
             'left-0 -translate-y-1/2 -translate-x-1/2' => $msg['is_mine'],
             'right-0 -translate-y-1/2 translate-x-1/2' => !$msg['is_mine'],
         ]) title="پیام سنجاق‌شده">
        <span class="material-symbols-rounded text-[10px] font-fill">push_pin</span>
    </div>
@endif
