@props(['label' => null, 'maximize' => true])

<div {{ $attributes->merge(['class' => 'flex h-7 w-full items-center justify-end gap-0.5']) }}>
    @if($label)
        <span x-show="headerDeclutter" x-cloak class="me-auto">{{ $label }}</span>
    @endif
    <div x-show="!headerDeclutter || headerDeclutterExit" x-cloak
         class="inline-flex items-center gap-0.5"
         :class="headerDeclutterExit ? 'animate-slide-fade-to-left' : ''">
        {{ $slot }}
        @if($maximize)
            <x-ui.modals.maximize-button size="xs"/>
        @endif
    </div>
    {{ $extra ?? '' }}
    <button type="button" @click="toggleHeaderDeclutter()"
            :aria-pressed="headerDeclutter" :aria-expanded="!headerDeclutter"
            :aria-label="headerDeclutter ? 'نمایش ابزارها' : 'خلوت‌سازی ابزارها'"
            :title="headerDeclutter ? 'نمایش ابزارها' : 'خلوت‌سازی ابزارها'"
            :class="headerDeclutter ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]'"
            class="inline-flex items-center justify-center p-1 rounded-lg transition-colors normal-case active:scale-95">
        <span class="material-symbols-rounded text-[18px] leading-none rotate-90" x-text="headerDeclutter ? 'unfold_more' : 'unfold_less'"></span>
    </button>
</div>
