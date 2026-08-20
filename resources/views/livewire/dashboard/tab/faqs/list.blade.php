@forelse($this->faqs as $faq)
    @php
        $badge = $presenter->badge($faq);
        $questionText = $presenter->questionText($faq);
        $answerSnippet = Str::limit(trim(preg_replace('/\s+/u', ' ', preg_replace('/<[^>]+>/', ' ', $faq->answer))), 140);
    @endphp
    <div wire:key="faq-list-{{ $faq->id }}" data-rf="faqs-{{ $faq->id }}"
         @click="view = 'card'; active = {{ $faq->id }}"
         class="flex items-center gap-4 p-4 bg-[var(--md-sys-color-surface-container-low)] hover:bg-[var(--md-sys-color-surface-container)] rounded-xl transition-all duration-300 border border-[var(--md-sys-color-outline-variant)]/20 hover:border-[var(--md-sys-color-primary)]/30 group cursor-pointer shadow-sm hover:shadow-md">
        <div class="shrink-0 p-2 rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] group-hover:bg-[var(--md-sys-color-primary)] group-hover:text-[var(--md-sys-color-on-primary)] transition-colors">
            <span class="material-symbols-rounded text-[20px] leading-none">forum</span>
        </div>

        <div class="flex-grow min-w-0">
            <h4 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] truncate group-hover:text-[var(--md-sys-color-primary)] transition-colors"
                x-html="highlight(@js($questionText))">{{ $questionText }}</h4>
            <p class="text-xs leading-relaxed text-[var(--md-sys-color-on-surface-variant)] line-clamp-1 mt-1 text-justify"
               x-html="highlight(@js($answerSnippet))">{{ $answerSnippet }}</p>
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

        <span class="material-symbols-rounded text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-primary)] transition-colors flip-rtl shrink-0">arrow_left_alt</span>
    </div>
@empty
    @include('livewire.dashboard.tab.faqs.empty')
@endforelse