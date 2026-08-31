<div dir="rtl"
     aria-live="polite"
     class="fixed bottom-6 right-4 sm:right-6 z-[100] flex flex-col gap-3 w-[calc(100vw-2rem)] max-w-xs sm:max-w-sm pointer-events-none">
    
    @foreach($edges as $e)
        @if($e['localRoute'] === null || $e['localRoute'] === $currentRoute)
            <div wire:key="edge-{{ $e['key'] }}:{{ $e['subject_id'] }}"
                 class="pointer-events-auto relative overflow-hidden flex flex-col gap-3 p-5 sm:p-6 rounded-[1.5rem] animate-toast-in
                    bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)]
                    dark:bg-[var(--md-sys-color-surface)] shadow-lg
                    dark:shadow-[0_14px_44px_-4px_rgba(0,0,0,0.6)]
                    border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)]">

                @php
                    $edgeUrl = $e['url'] ?? '';
                    $safeUrl = ($edgeUrl !== '' && (str_starts_with($edgeUrl, 'http') || (str_starts_with($edgeUrl, '/') && !str_starts_with($edgeUrl, '//')))) ? $edgeUrl : '#';
                @endphp

                <div class="flex items-start gap-3.5 min-w-0">
                    <a href="{{ $safeUrl }}"
                       @if($safeUrl !== '#') wire:navigate @endif
                       class="ripple-effect flex items-start gap-3.5 min-w-0 flex-1 rounded-xl -m-1 p-1">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl
                                     bg-[color-mix(in_srgb,var(--md-sys-color-primary)_14%,transparent)]">
                            <span class="material-symbols-rounded text-[22px] text-[var(--md-sys-color-primary)] font-fill">{{ $e['icon'] }}</span>
                        </span>
                        <div class="min-w-0 flex-1 pt-1">
                            <p class="text-[14px] font-bold text-[var(--md-sys-color-on-surface)] leading-tight">{{ $e['title'] }}</p>
                            @if(!empty($e['body']))
                                <p class="text-[12.5px] font-normal text-[var(--md-sys-color-on-surface-variant)] mt-1.5 leading-relaxed">{{ $e['body'] }}</p>
                            @endif
                        </div>
                    </a>

                    <div class="flex justify-end">
                        <button type="button"
                                wire:click="dismiss(@js($e['key']), @js($e['subject_id']))"
                                class="px-4 py-2 rounded-lg text-[12.5px] font-bold whitespace-nowrap active:scale-[0.97] transition-all
                               bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:opacity-90">
                            بستن
                        </button>
                    </div>
                </div>

                {{-- reuse indeterminate progress bar animation --}}
                <span class="absolute inset-x-0 bottom-0 h-[3px] overflow-hidden
                         bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)]">
                    <span class="absolute inset-y-0 w-1/3 rounded-full animate-shimmer
                                 bg-[linear-gradient(90deg,transparent,color-mix(in_srgb,var(--md-sys-color-primary)_90%,transparent),transparent)]"></span>
                </span>
            </div>
        @endif
    @endforeach
</div>
