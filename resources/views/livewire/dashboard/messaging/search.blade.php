<div x-show="searchMessages" x-transition style="display:none;" class="absolute left-0 right-10 z-40">
    <div class="rounded-2xl bg-[var(--md-sys-color-surface)] shadow-2xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] overflow-hidden">
        @include('livewire.dashboard.channel.search-field', [
            'model' => 'messageSearch',
            'id' => 'msg-search-input',
            'debounce' => 600,
            'placeholder' => $placeholder,
            'overlayTitle' => $overlayTitle,
            'showLabel' => false,
            'loadingDisabled' => true,
            'wireIgnoreSelf' => false,
            'inputClass' => 'w-full h-11 pr-11 pl-10 rounded-2xl text-sm outline-none bg-[color-mix(in_srgb,var(--md-sys-color-surface-variant)_60%,transparent)] text-[var(--md-sys-color-on-surface)] placeholder:text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_70%,transparent)] border-none',
        ])

        @if(strlen(trim($this->messageSearch)))
            <div class="max-h-72 overflow-y-auto contact-scrollbar border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
                @forelse($this->messageSearchResults as $r)
                    <button type="button"
                            data-id="{{ $r['id'] }}"
                            x-on:click="focusSearchResult(Number($el.dataset.id))"
                            class="w-full text-right px-3 py-2 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_20%,transparent)] last:border-0 hover:bg-[color-mix(in_srgb,var(--md-sys-color-surface-variant)_60%,transparent)] cursor-pointer transition-colors">
                        <span class="block text-[12px] leading-relaxed text-[var(--md-sys-color-on-surface)] line-clamp-2">{{ $r['body'] }}</span>
                        <span class="block text-[10px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)]">
                            <span @class(['font-semibold text-[var(--md-sys-color-primary)]' => $r['is_mine'], 'text-[var(--md-sys-color-on-surface-variant)]' => !$r['is_mine']])>{{ $r['sender_name'] }}</span>
                            <span class="opacity-50 mx-1">·</span>
                            <span dir="ltr">{{ $r['time'] }}</span>
                        </span>
                    </button>
                @empty
                    <div class="px-3 py-5 text-center text-[11px] text-[var(--md-sys-color-on-surface-variant)]">پیامی یافت نشد</div>
                @endforelse
            </div>
        @endif
    </div>
</div>