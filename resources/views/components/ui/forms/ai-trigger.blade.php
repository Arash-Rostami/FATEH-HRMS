@props(['tip', 'need', 'long', 'short', 'limitSubject', 'limitDescription'])

<button type="button"
        title="{{ $tip }}"
        x-bind:title="$wire.ticket.requestArea === '' ? $el.dataset.aiNeed : (aiOverLimit($el) ? $el.dataset.aiShort : $el.dataset.aiTip)"
        aria-label="پیشنهاد هوشمند"
        data-ai-need="{{ $need }}"
        data-ai-tip="{{ $tip }}"
        data-ai-long="{{ $long }}"
        data-ai-short="{{ $short }}"
        data-ai-limit-subject="{{ $limitSubject }}"
        data-ai-limit-description="{{ $limitDescription }}"
        x-on:click="aiRun($el.dataset.aiNeed, $el)"
        x-bind:aria-disabled="aiBlocked()"
        {{ $attributes->merge(['class' => 'absolute h-7 px-2 flex items-center gap-1 bg-[var(--ai-bg)] text-[var(--ai-text)] hover:bg-[color-mix(in_srgb,var(--ai-bg)_78%,var(--ai-color))] rounded-lg text-[11px] font-bold transition-all z-10 cursor-pointer aria-disabled:opacity-50 aria-disabled:cursor-not-allowed']) }}>
    <span class="material-symbols-rounded !text-[14px] text-[var(--ai-color)]"
          x-bind:class="aiLoading ? 'animate-alert-soft' : ''">auto_awesome</span>
    <span>پیشنهاد</span>
</button>
