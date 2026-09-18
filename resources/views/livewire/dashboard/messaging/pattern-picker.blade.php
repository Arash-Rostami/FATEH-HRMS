@php
    $patterns = ['mesh' => 'پیش‌فرض', 'doodle' => 'آیکون‌دار', 'twill' => 'دیاموند', 'lattice' => 'شبکه', 'motif' => 'نگاره'];
@endphp
<div x-data="{ open: false, pos: {} }">
    <button type="button" x-ref="patternTrigger"
            x-on:click="pos = $refs.patternTrigger.getBoundingClientRect().toJSON(); open = !open"
            aria-label="پیش زمینه چت" title="پیش زمینه چت"
            class="flex h-8 w-8 items-center justify-center rounded-xl transition-all duration-200 ease-out active:scale-95"
            :class="isHighlighted ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)]'">
        <span class="material-symbols-rounded text-base" x-text="isHighlighted ? 'hide_image' : 'texture'"></span>
    </button>

    <template x-teleport="body">
        <div x-show="open" x-cloak dir="rtl"
             x-on:click.away="if (!$refs.patternTrigger?.contains($event.target)) open = false"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             :style="{ position: 'fixed', top: (pos.bottom + 8) + 'px', left: pos.left + 'px' }"
             class="w-44 p-2 rounded-xl z-40 bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-[0_12px_48px_color-mix(in_srgb,var(--md-sys-color-shadow)_18%,transparent)]"
             role="dialog" aria-label="پیش زمینه چت">
            <button type="button" @click="toggleHighlight()"
                    class="w-full flex items-center justify-between px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[var(--md-sys-color-surface-container-high)]/50">
                <span>فعال</span>
                <div class="relative w-9 h-5 rounded-full transition-colors duration-200" :class="isHighlighted ? 'bg-[var(--md-sys-color-primary)]' : 'bg-[var(--md-sys-color-outline-variant)]/30'">
                    <div class="absolute top-1 left-1 w-3 h-3 rounded-full bg-white transition-transform duration-200" :class="isHighlighted ? 'translate-x-4' : 'translate-x-0'"></div>
                </div>
            </button>
            <div x-show="isHighlighted" x-transition class="mt-1 pt-1 border-t border-[var(--md-sys-color-outline-variant)]/10 space-y-0.5">
                @foreach($patterns as $id => $label)
                    <button type="button" @click="setPatternType('{{ $id }}')"
                            class="w-full flex items-center justify-between px-2 py-1.5 rounded-lg text-xs transition-colors hover:bg-[var(--md-sys-color-surface-container-high)]"
                            :class="backgroundPatternType === '{{ $id }}' ? 'bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-primary)] font-medium' : ''">
                        <span>{{ $label }}</span>
                        <span class="material-symbols-rounded text-[14px]" x-show="backgroundPatternType === '{{ $id }}'">check</span>
                    </button>
                @endforeach
            </div>
        </div>
    </template>
</div>
