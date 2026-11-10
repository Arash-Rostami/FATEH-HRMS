@props(['tip', 'need', 'long', 'short', 'limitSubject', 'limitDescription'])

<button type="button"
        title="{{ $tip }}"
        x-bind:title="$wire.ticket.requestArea === '' ? $el.dataset.aiNeed : (aiOverLimit($el) ? $el.dataset.aiShort : $el.dataset.aiTip)"
        aria-label="نوشتن هوشمند"
        data-ai-need="{{ $need }}"
        data-ai-tip="{{ $tip }}"
        data-ai-long="{{ $long }}"
        data-ai-short="{{ $short }}"
        data-ai-limit-subject="{{ $limitSubject }}"
        data-ai-limit-description="{{ $limitDescription }}"
        x-on:click="aiRun($el.dataset.aiNeed, $el)"
        x-bind:aria-disabled="aiBlocked()"
        {{ $attributes->merge(['class' => 'absolute p-3.5 flex items-center justify-center text-[var(--ai-color)] hover:text-[var(--ai-text)] active:text-[var(--ai-text)] hover:scale-110 aria-disabled:opacity-50 aria-disabled:cursor-not-allowed aria-disabled:hover:scale-100 transition-all z-10 cursor-pointer']) }}>
    <span class="material-symbols-rounded text-[18px]"
          x-bind:class="aiLoading ? 'animate-alert-soft' : ''">auto_awesome</span>
</button>
