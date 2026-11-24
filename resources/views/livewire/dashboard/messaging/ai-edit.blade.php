@if($aiOn)
    @if($part === 'status')
        <div wire:key="{{ $scope }}-edit-ai-status" class="contents">
            <p wire:ignore x-show="(aiEditBusy && aiEditId === {{ $msgId }}) || aiEditNote" x-cloak role="status" aria-live="polite"
               x-text="aiEditBusy && aiEditId === {{ $msgId }} ? 'در حال بازنویسی پیام…' : aiEditNote"
               :class="(aiEditBusy && aiEditId === {{ $msgId }}) || aiEditOk ? 'text-[var(--ai-text)]' : (aiEditSoft ? 'text-[var(--md-sys-color-on-surface-variant)]' : 'text-[var(--md-sys-color-error)]')"
               class="px-4 pb-1 text-[10px] font-medium leading-4"></p>
        </div>
        <x-ui.loaders.ai-shimmer wire:key="{{ $scope }}-edit-ai-shimmer" x-show="aiEditBusy && aiEditId === {{ $msgId }}" x-cloak class="rounded-xl"/>
    @else
        <div wire:key="{{ $scope }}-edit-ai-meter" class="contents">
            <div wire:ignore x-show="aiEditMeter" x-cloak x-html="aiEditMeter"
                 class="border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_20%,transparent)] px-3 py-2"></div>
        </div>
    @endif
@endif
