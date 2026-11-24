@php($canWrite = $canWrite ?? true)
@php($canReply = $canReply ?? $canWrite)
@php($canForward = $canForward ?? $canWrite)
<div @class([
            'absolute bottom-0 flex items-center gap-0.5 px-1 py-1 rounded-lg opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-all duration-200 z-20 bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-[0_4px_20px_color-mix(in_srgb,var(--md-sys-color-shadow)_12%,transparent)] scale-90 group-hover:scale-100 group-focus-within:scale-100 max-md:translate-x-0',
            'left-0 -translate-x-[calc(100%+6px)] max-md:left-auto max-md:right-0' => $msg['is_mine'],
            'right-0 translate-x-[calc(100%+6px)] max-md:right-auto max-md:left-0' => !$msg['is_mine']
        ])
     x-data="{ open: false, pos: {} }"
     data-msg-actions="{{ $msg['id'] }}" data-msg-body="{{ $msg['body'] }}"
     :class="openActionsId === {{ $msg['id'] }} || open ? 'opacity-100 scale-100' : ''">
    @if($canReact ?? false)
        <div wire:key="messaging-react-tools" class="contents">
            <button type="button"
                    x-on:click.prevent="$store.activityReactionPicker.open('ch{{ $msg['id'] }}', $el)"
                    class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)] hover:scale-110 active:scale-90 text-[var(--md-sys-color-on-surface-variant)]"
                    title="افزودن واکنش" aria-label="افزودن واکنش">
                <span class="material-symbols-rounded text-[15px]">add_reaction</span>
            </button>
        </div>
    @endif
    <button x-on:click="copyMessage($el.dataset.text)"
            data-text="{{ html_entity_decode(strip_tags($msg['body_html'] . (($msg['forwarded_html'] ?? '') !== '' ? "\n" . $msg['forwarded_html'] : '')), ENT_QUOTES) }}"
            class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-tertiary)_10%,transparent)] hover:text-[var(--md-sys-color-tertiary)] hover:scale-110 active:scale-90 text-[var(--md-sys-color-on-surface-variant)]"
            title="کپی" aria-label="کپی">
        <span class="material-symbols-rounded text-[15px]">content_copy</span>
    </button>
    @if($canReply)
        <div wire:key="messaging-reply-tool" class="contents">
            <button x-on:click.prevent="startReply({{ $msg['id'] }}, $el.dataset.sender, $el.dataset.body)"
                    data-sender="{{ $msg['sender_name'] }}"
                    data-body="{{ strip_tags($msg['plain_text'] ?? $msg['body']) }}"
                    class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)] hover:scale-110 active:scale-90 text-[var(--md-sys-color-on-surface-variant)]"
                    title="پاسخ" aria-label="پاسخ">
                <span class="material-symbols-rounded text-[15px]">reply</span>
            </button>
        </div>
    @endif
    <button type="button" data-msg-menu-trigger="{{ $msg['id'] }}"
            x-on:click.prevent="pos = $el.getBoundingClientRect().toJSON(); open = !open; if (open) requestAnimationFrame(() => (document.querySelector('[data-msg-menu=&quot;{{ $msg['id'] }}&quot;] [data-default-menuitem]') || document.querySelector('[data-msg-menu=&quot;{{ $msg['id'] }}&quot;] [role=menuitem]'))?.focus())"
            :aria-expanded="open" aria-haspopup="menu"
            class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)] hover:scale-110 active:scale-90 text-[var(--md-sys-color-on-surface-variant)]"
            title="بیشتر" aria-label="بیشتر">
        <span class="material-symbols-rounded text-[15px]">more_horiz</span>
    </button>
    <template x-if="open"><template x-teleport="body">
        <div dir="rtl" data-msg-menu="{{ $msg['id'] }}"
             x-on:click.away="if (!$event.target.closest('[data-msg-menu-trigger=&quot;{{ $msg['id'] }}&quot;]')) open = false"
             x-on:keydown.escape.window="open = false; document.querySelector('[data-msg-menu-trigger=&quot;{{ $msg['id'] }}&quot;]')?.focus()"
             x-on:wheel.window.passive="$event.target.closest('[data-msg-menu]') || (open = false)"
             x-on:touchmove.window.passive="$event.target.closest('[data-msg-menu]') || (open = false)"
             :style="{ position: 'fixed', ...(pos.top > window.innerHeight / 2 ? { bottom: (window.innerHeight - pos.top + 8) + 'px' } : { top: (pos.bottom + 8) + 'px' }), ...(pos.left > window.innerWidth / 2 ? { right: (window.innerWidth - pos.right) + 'px' } : { left: pos.left + 'px' }) }"
             class="p-1.5 rounded-xl z-40 max-h-[70vh] overflow-y-auto bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-[0_12px_48px_color-mix(in_srgb,var(--md-sys-color-shadow)_18%,transparent)]"
             role="menu" aria-label="بیشتر">
            @if($msg['can_edit'] && $canWrite)
                @if(($canEnhance ?? false) && $msg['can_edit'])
                    <button type="button" role="menuitem" wire:key="messaging-ai-enhance" x-on:click.prevent="open = false; $dispatch('smart-edit', { id: {{ $msg['id'] }}, body: document.querySelector('[data-msg-actions=&quot;{{ $msg['id'] }}&quot;]')?.dataset.msgBody })"
                            class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium bg-[var(--ai-bg)] text-[var(--ai-text)] hover:bg-[color-mix(in_srgb,var(--ai-bg)_78%,var(--ai-color))]">
                        <span class="material-symbols-rounded !text-[16px] text-[var(--ai-color)]">auto_awesome</span>
                        <span>ویرایش هوشمند</span>
                    </button>
                @endif
                <button type="button" role="menuitem" data-default-menuitem x-on:click.prevent="open = false; startEdit({{ $msg['id'] }}, document.querySelector('[data-msg-actions=&quot;{{ $msg['id'] }}&quot;]')?.dataset.msgBody)"
                        class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[var(--md-sys-color-surface-container-high)]/50">
                    <span class="material-symbols-rounded text-[16px]">edit</span>
                    <span>ویرایش</span>
                </button>
            @endif
            @if($canReact ?? false)
                <div wire:key="messaging-pin-row" class="contents">
                    <button type="button" role="menuitem"
                            wire:click="toggleGroupPin({{ $msg['id'] }})"
                            wire:island="messages"
                            wire:loading.attr="disabled" wire:target="toggleGroupPin"
                            x-on:click="open = false"
                            @class([
                                'w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium disabled:opacity-50',
                                'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]' => ($pinnedId ?? null) === $msg['id'],
                                'hover:bg-[var(--md-sys-color-surface-container-high)]/50' => ($pinnedId ?? null) !== $msg['id'],
                            ])>
                        <span @class(['material-symbols-rounded text-[16px]', 'font-fill' => ($pinnedId ?? null) === $msg['id']])>push_pin</span>
                        <span>{{ $pinLabel ?? 'سنجاق گروه' }}</span>
                    </button>
                </div>
            @endif
            <button type="button" role="menuitemcheckbox"
                    x-on:click.prevent="open = false; $store.pinned.togglePin(@js($msg['id']), @js($pinScope ?? 'message'))"
                    :aria-checked="$store.pinned.isPinned(@js($msg['id']), @js($pinScope ?? 'message'))"
                    class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[var(--md-sys-color-surface-container-high)]/50"
                    :class="$store.pinned.isPinned(@js($msg['id']), @js($pinScope ?? 'message')) ? 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]' : ''">
                <span class="material-symbols-rounded text-[16px]"
                      :class="$store.pinned.isPinned(@js($msg['id']), @js($pinScope ?? 'message')) ? 'font-fill' : ''">bookmark</span>
                <span x-text="$store.pinned.isPinned(@js($msg['id']), @js($pinScope ?? 'message')) ? @js('برداشتن نشان') : @js('نشان کردن')"></span>
            </button>
            @if($canForward)
                <div wire:key="messaging-forward-row" class="contents">
                    <button type="button" role="menuitem"
                            x-on:click.prevent="open = false; openActionsId = null; $wire.openForward({{ $msg['id'] }})"
                            wire:loading.attr="disabled" wire:target="openForward"
                            class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[var(--md-sys-color-surface-container-high)]/50 disabled:opacity-50">
                        <span class="material-symbols-rounded text-[16px] rotate-180">reply</span>
                        <span>بازارسال</span>
                    </button>
                </div>
            @endif
            @if(!empty($msg['attachments']))
                <button type="button" role="menuitem" wire:key="messaging-download-row"
                        data-indices="{{ json_encode(array_keys($msg['attachments'])) }}" :disabled="downloadingAttachments"
                        x-on:click="open = false; downloadMessageFiles({{ $msg['id'] }}, JSON.parse($el.dataset.indices))"
                        class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[var(--md-sys-color-surface-container-high)]/50">
                    <span class="material-symbols-rounded text-[16px]">download</span>
                    <span>{{ count($msg['attachments']) === 1 ? 'دانلود پیوست' : 'دانلود همهٔ پیوست‌ها' }}</span>
                </button>
            @endif
            <div class="h-px my-1 bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)]" aria-hidden="true"></div>
            <div class="flex items-center justify-between gap-3 px-2 py-1 text-xs font-medium" role="group" aria-label="اندازهٔ متن">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-rounded text-[16px]">format_size</span>
                    <span>اندازهٔ متن</span>
                </span>
                <span dir="ltr" class="flex items-center gap-0.5">
                    <button type="button" x-on:click.prevent="bumpFont({{ $msg['id'] }}, -1); requestAnimationFrame(() => pos = document.querySelector('[data-msg-menu-trigger=&quot;{{ $msg['id'] }}&quot;]').getBoundingClientRect().toJSON())"
                            :disabled="fontAtMin({{ $msg['id'] }})"
                            class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-tertiary)_10%,transparent)] hover:text-[var(--md-sys-color-tertiary)] active:scale-90 text-[var(--md-sys-color-on-surface-variant)] disabled:opacity-40 disabled:pointer-events-none"
                            title="کوچک‌تر کردن متن" aria-label="کوچک‌تر کردن متن">
                        <span class="material-symbols-rounded text-[15px]">remove</span>
                    </button>
                    <button type="button" x-on:click.prevent="bumpFont({{ $msg['id'] }}, 1); requestAnimationFrame(() => pos = document.querySelector('[data-msg-menu-trigger=&quot;{{ $msg['id'] }}&quot;]').getBoundingClientRect().toJSON())"
                            :disabled="fontAtMax({{ $msg['id'] }})"
                            class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-tertiary)_10%,transparent)] hover:text-[var(--md-sys-color-tertiary)] active:scale-90 text-[var(--md-sys-color-on-surface-variant)] disabled:opacity-40 disabled:pointer-events-none"
                            title="بزرگ‌تر کردن متن" aria-label="بزرگ‌تر کردن متن">
                        <span class="material-symbols-rounded text-[15px]">add</span>
                    </button>
                </span>
            </div>
            @if($msg['can_delete'] && $canWrite)
                <div class="h-px my-1 bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)]" aria-hidden="true"></div>
                <button type="button" role="menuitem" x-on:click.prevent="open = false; confirmDelete({{ $msg['id'] }})"
                        :disabled="showUndo"
                        :class="showUndo ? 'opacity-40 pointer-events-none' : ''"
                        class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[color-mix(in_srgb,var(--md-sys-color-error)_10%,transparent)] text-[color-mix(in_srgb,var(--md-sys-color-error)_80%,var(--md-sys-color-on-surface-variant))]">
                    <span class="material-symbols-rounded text-[16px]">delete</span>
                    <span>حذف</span>
                </button>
            @endif
        </div>
    </template></template>
</div>

<div x-show="$store.pinned.isPinned(@js($msg['id']), @js($pinScope ?? 'message'))" x-cloak
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
