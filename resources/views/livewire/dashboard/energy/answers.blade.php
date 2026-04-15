<div class="p-4 space-y-2.5">
    @foreach($qs as $idx => $question)
        @php
            $isChecked = $answers[$cat][$idx] ?? false;
            $isNone    = $idx === $lastIdx;
        @endphp
        <label class="block cursor-pointer" wire:key="opt-{{ $cat }}-{{ $idx }}">
            <input type="checkbox"
                   wire:model.live="answers.{{ $cat }}.{{ $idx }}"
                   class="sr-only">
            <div @class([
                        'flex items-start gap-3 p-3.5 rounded-xl border-2 transition-all duration-200 select-none',
                        'border-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary-container)]' => $isChecked && !$isNone,
                        'border-[var(--md-sys-color-secondary)] bg-[var(--md-sys-color-secondary-container)]' => $isChecked && $isNone,
                        'border-[var(--md-sys-color-outline-variant)]/60 bg-[var(--md-sys-color-surface)] hover:bg-[var(--md-sys-color-surface-variant)]/30' => !$isChecked,
                    ])>
                <div @class([
                            'w-5 h-5 rounded-md border-2 flex items-center justify-center flex-shrink-0 mt-0.5 transition-all duration-200',
                            'border-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary)]' => $isChecked && !$isNone,
                            'border-[var(--md-sys-color-secondary)] bg-[var(--md-sys-color-secondary)]' => $isChecked && $isNone,
                            'border-[var(--md-sys-color-outline-variant)]' => !$isChecked,
                        ])>
                    @if($isChecked)
                        <span class="material-symbols-rounded font-fill text-white"
                              style="font-size:13px">check</span>
                    @endif
                </div>
                <span @class([
                            'text-sm leading-relaxed',
                            'text-[var(--md-sys-color-on-primary-container)] font-medium' => $isChecked && !$isNone,
                            'text-[var(--md-sys-color-on-secondary-container)] font-bold' => $isChecked && $isNone,
                            'text-[var(--md-sys-color-on-surface)]' => !$isChecked && !$isNone,
                            'text-[var(--md-sys-color-on-surface-variant)] font-semibold' => !$isChecked && $isNone,
                        ])>
                    {{ $question }}
                </span>
            </div>
        </label>
    @endforeach
</div>
