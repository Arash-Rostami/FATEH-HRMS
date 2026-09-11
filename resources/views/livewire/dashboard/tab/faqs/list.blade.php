@forelse($this->faqs as $faq)
    @php
        $badge = $presenter->badge($faq);
        $questionText = $presenter->questionText($faq);
        $answerSnippet = $presenter->answerSnippet($faq);
    @endphp
    <div wire:key="faq-list-{{ $faq->id }}" data-rf="faqs-{{ $faq->id }}"
         class="rounded-xl border border-[var(--md-sys-color-outline-variant)]/20 hover:border-[var(--md-sys-color-primary)]/30 bg-[var(--md-sys-color-surface-container-low)] shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
        <div @click="toggle({{ $faq->id }})"
             class="flex items-center gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300 group cursor-pointer">
            <div class="shrink-0 p-2 rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] group-hover:bg-[var(--md-sys-color-primary)] group-hover:text-[var(--md-sys-color-on-primary)] transition-colors">
                <span class="material-symbols-rounded text-[20px] leading-none">forum</span>
            </div>

            <div class="flex-grow min-w-0">
                <h4 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] truncate group-hover:text-[var(--md-sys-color-primary)] transition-colors"
                    x-html="highlight(@js($questionText), $wire.search)">{{ $questionText }}</h4>
                <p class="text-xs leading-relaxed text-[var(--md-sys-color-on-surface-variant)] line-clamp-1 mt-1 text-justify"
                   x-html="highlight(@js($answerSnippet), $wire.search)">{{ $answerSnippet }}</p>
                <div class="flex flex-wrap items-center gap-2 mt-2 text-[11px] text-[var(--md-sys-color-on-surface-variant)]">
                    <span class="bg-[var(--md-sys-color-surface-variant)] px-2 py-0.5 rounded-md font-medium">{{ $faq->category }}</span>
                    @if($faq->department)
                        <span class="flex items-center gap-1" title="{{ $faq->department->tooltipLabel() }}">
                            <span class="material-symbols-rounded text-[14px] leading-none">call</span>
                            {{ $faq->department->displayLabel() }}
                        </span>
                    @endif
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md border {{ $badge['colorClasses'] }}">
                        <span class="material-symbols-rounded text-[14px] leading-none {{ $badge['isUpdated'] ? 'text-[var(--md-sys-color-secondary)]' : '' }}">{{ $badge['icon'] }}</span>
                        <span dir="rtl" class="font-semibold">{{ $badge['label'] }}: {{ $badge['date'] }}</span>
                    </span>
                </div>
            </div>

            <span class="material-symbols-rounded text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-primary)] transition-transform duration-300 shrink-0"
                  :class="active === {{ $faq->id }} ? 'rotate-180' : ''">expand_more</span>
        </div>

        <div x-show="active === {{ $faq->id }}" x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="border-t border-[var(--md-sys-color-outline-variant)]/20 bg-[var(--md-sys-color-surface-container-lowest)] py-4 pr-16 pl-7">
            <div class="flex items-center gap-2 mb-2.5">
                <span class="material-symbols-rounded text-[16px] leading-none text-[var(--md-sys-color-primary)]" aria-hidden="true">reply</span>
                <span class="text-[11px] font-bold tracking-wide text-[var(--md-sys-color-primary)]">پاسخ</span>
                <span class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]/50" aria-hidden="true"></span>
            </div>
            <div class="max-w-none text-sm leading-7 text-justify prose prose-sm prose-p:text-[var(--md-sys-color-on-surface)] prose-a:text-[var(--md-sys-color-primary)] rich-colors"
                 dir="rtl"
                 x-html="highlightHtml(@js(str_replace('<a ', "<a target='_blank' class='hover:underline font-medium' ", $faq->answer)), $wire.search)"
            ></div>
        </div>
    </div>
@empty
    @include('livewire.dashboard.tab.faqs.empty')
@endforelse