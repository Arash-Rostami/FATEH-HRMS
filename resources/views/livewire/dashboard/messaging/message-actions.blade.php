<div @class([
            'absolute top-1/2 -translate-y-1/2 flex items-center gap-0.5 px-1 py-1 rounded-lg opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-all duration-200 z-20 bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-[0_4px_20px_color-mix(in_srgb,var(--md-sys-color-shadow)_12%,transparent)] scale-90 group-hover:scale-100 group-focus-within:scale-100',
            'left-0 -translate-x-[calc(100%+6px)]' => $msg['is_mine'],
            'right-0 translate-x-[calc(100%+6px)]' => !$msg['is_mine']
        ])
     :class="openActionsId === {{ $msg['id'] }} ? 'opacity-100 scale-100' : ''">
    <button x-on:click="copyMessage($el.dataset.text)"
            data-text="{{ strip_tags($msg['body_html']) }}"
            class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-tertiary)_10%,transparent)] hover:text-[var(--md-sys-color-tertiary)] hover:scale-110 active:scale-90 text-[var(--md-sys-color-on-surface-variant)]"
            title="کپی" aria-label="کپی">
        <span class="material-symbols-rounded text-[15px]">content_copy</span>
    </button>
    <button x-on:click.prevent="startReply({{ $msg['id'] }}, $el.dataset.sender, $el.dataset.body)"
            data-sender="{{ $msg['sender_name'] }}"
            data-body="{{ strip_tags($msg['body']) }}"
            class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)] hover:scale-110 active:scale-90 text-[var(--md-sys-color-on-surface-variant)]"
            title="پاسخ" aria-label="پاسخ">
        <span class="material-symbols-rounded text-[15px]">reply</span>
    </button>
    @if($msg['can_edit'] && $msg['id'] === $this->lastMessageId)
        <button x-on:click.prevent="startEdit({{ $msg['id'] }}, $el.dataset.body)"
                data-body="{{ $msg['body'] }}"
                class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-secondary)_10%,transparent)] hover:text-[var(--md-sys-color-secondary)] hover:scale-110 active:scale-90 text-[var(--md-sys-color-on-surface-variant)]"
                title="ویرایش" aria-label="ویرایش">
            <span class="material-symbols-rounded text-[15px]">edit</span>
        </button>
    @endif
    @if($msg['can_delete'] && $msg['id'] === $this->lastMessageId)
        <button x-on:click.prevent="confirmDelete({{ $msg['id'] }})"
                class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-error)_10%,transparent)] hover:scale-110 active:scale-90 text-[color-mix(in_srgb,var(--md-sys-color-error)_80%,var(--md-sys-color-on-surface-variant))]"
                title="حذف" aria-label="حذف">
            <span class="material-symbols-rounded text-[15px]">delete</span>
        </button>
    @endif
</div>