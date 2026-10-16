<div wire:key="ths-modal-ball" role="status"
     class="flex flex-wrap sm:flex-nowrap items-center gap-3 px-4 py-3 rounded-2xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] shadow-sm min-w-0 {{ $ball['classes'] }}">
    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $ball['iconClasses'] }}">
        <span class="material-symbols-rounded text-[20px]">{{ $ball['icon'] }}</span>
    </div>
    <div class="flex-1 min-w-0 flex flex-col gap-0.5">
        <span class="text-[13px] font-bold [overflow-wrap:anywhere]">{{ $ball['title'] }}</span>
        @if($ball['sub'])
            <span wire:key="ths-modal-ball-sub" class="text-[11px] opacity-80">{{ $ball['sub'] }}</span>
        @endif
    </div>
    @if($ball['cta'] && $ball['cta'][1] === 'rate')
        <x-ui.buttons.form wire:key="ths-modal-ball-cta-rate" type="button" icon="star"
                x-on:click="closeModal(); $wire.switchTab('rate')"
                class="w-full sm:w-auto !h-auto max-sm:min-h-[44px] !px-4 !py-2 rounded-xl text-xs font-bold">
            {{ $ball['cta'][0] }}
        </x-ui.buttons.form>
    @elseif($ball['cta'])
        <x-ui.buttons.form wire:key="ths-modal-ball-cta-response" type="button" icon="arrow_back"
                wire:click="switchTab('response')" loading="switchTab('response')"
                class="w-full sm:w-auto !h-auto max-sm:min-h-[44px] !px-4 !py-2 rounded-xl text-xs font-bold flex-row-reverse">
            {{ $ball['cta'][0] }}
        </x-ui.buttons.form>
    @endif
</div>
