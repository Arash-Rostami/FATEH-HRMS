<style>{!! $chatFontFace !!}</style>
<div x-data="{ open: false, pos: {} }">
    <button type="button" id="chat-font-trigger"
            x-on:click="pos = $el.getBoundingClientRect().toJSON(); open = !open"
            aria-label="فونت پیام‌ها" title="فونت پیام‌ها" aria-haspopup="menu" :aria-expanded="open"
            class="flex items-center justify-center w-8 h-8 rounded-lg hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
            :class="messageFont !== 'default' ? 'text-[var(--md-sys-color-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)]'">
        <span class="material-symbols-rounded text-lg">text_fields</span>
    </button>

    <template x-if="open"><template x-teleport="body">
        <div dir="rtl"
             x-on:click.away="if (!document.getElementById('chat-font-trigger')?.contains($event.target)) open = false"
             x-on:keydown.escape.window="open = false"
             :style="{ position: 'fixed', top: (pos.bottom + 8) + 'px', left: pos.left + 'px' }"
             class="w-48 p-1.5 rounded-xl z-40 bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-[0_12px_48px_color-mix(in_srgb,var(--md-sys-color-shadow)_18%,transparent)]"
             role="menu" aria-label="فونت پیام‌ها">
            <button type="button" role="menuitemradio" :aria-checked="messageFont === 'default'"
                    x-on:click="setMessageFont('default'); open = false"
                    class="w-full flex items-center justify-between px-2 py-1.5 rounded-lg text-xs hover:bg-[var(--md-sys-color-surface-container-high)]"
                    :class="messageFont === 'default' ? 'bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-primary)] font-medium' : ''">
                <span>پیش‌فرض (فونت برنامه)</span>
                <span class="material-symbols-rounded text-[14px]" x-show="messageFont === 'default'">check</span>
            </button>
            @foreach($chatFonts as $key => $label)
                <button type="button" role="menuitemradio" :aria-checked="messageFont === '{{ $key }}'"
                        x-on:click="setMessageFont('{{ $key }}'); open = false"
                        style="font-family: '{{ $key }}', inherit"
                        class="w-full flex items-center justify-between px-2 py-1.5 rounded-lg text-xs hover:bg-[var(--md-sys-color-surface-container-high)]"
                        :class="messageFont === '{{ $key }}' ? 'bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-primary)] font-medium' : ''">
                    <span>{{ $label }}</span>
                    <span class="material-symbols-rounded text-[14px]" x-show="messageFont === '{{ $key }}'">check</span>
                </button>
            @endforeach
        </div>
    </template></template>
</div>
