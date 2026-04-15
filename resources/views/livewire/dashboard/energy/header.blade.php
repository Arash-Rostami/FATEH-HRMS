<div class="px-5 py-4 border-b border-[var(--md-sys-color-outline-variant)]/40"
     style="background: color-mix(in srgb, var({{ $presenter->getContainerColorVar($cat) }}) 25%, var(--md-sys-color-surface))">
    <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: var({{ $presenter->getContainerColorVar($cat) }}); color: var({{ $presenter->getOnContainerColorVar($cat) }})">
            <span class="material-symbols-rounded text-[18px] font-fill">{{ $presenter->getIcon($cat) }}</span>
        </div>
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-[var(--md-sys-color-on-surface-variant)]">
                {{ $presenter->formatSectionTitle($sections[$cat]) }}
            </p>
        </div>
        <div class="mr-auto flex items-center gap-1.5">
            @for($i = 1; $i <= $totalSteps; $i++)
                <div @class([
                            'h-1.5 rounded-full transition-all duration-300',
                            'w-6 bg-[var(--md-sys-color-primary)]' => $step === $i,
                            'w-2 bg-[var(--md-sys-color-primary)]' => $step > $i,
                            'w-2 bg-[var(--md-sys-color-outline-variant)]/50' => $step < $i,
                        ])>

                </div>
            @endfor
        </div>
    </div>
    <p class="mt-3 text-sm font-bold leading-relaxed text-[var(--md-sys-color-on-surface)]">
        {{ $prompts[$cat] }}
    </p>
</div>
