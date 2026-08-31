<div class="px-4 pb-4 flex flex-col-reverse sm:flex-row items-center justify-between gap-3 sm:gap-4" dir="rtl">
    <div class="flex items-center gap-2 text-sm font-medium text-[var(--md-sys-color-on-surface-variant)]">
        <span title="مرحله"
              class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)] text-xs font-bold shadow-sm">
             {{ $totalSteps }} / {{ $step }}
        </span>
    </div>

    {{-- Action Buttons --}}
    <div class="flex items-center gap-3 w-full sm:w-auto justify-start sm:justify-end">
        {{-- Previous Button --}}
        <button
            wire:click="previousStep"
            :disabled="step === 1"
            title="مرحله قبل"
            aria-disabled="{{ $step === 1 ? 'true' : 'false' }}"
            @class([
                'group relative inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-xl border-1 transition-all duration-200 ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--md-sys-color-surface)]',
                'border-[var(--md-sys-color-outline-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:border-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-primary)] hover:shadow-sm active:scale-[0.98]',
                'disabled:opacity-50 disabled:cursor-not-allowed disabled:border-[var(--md-sys-color-outline)] disabled:text-[var(--md-sys-color-on-surface)]'
            ])
        >
            <span
                class="material-symbols-rounded text-[18px] transition-transform duration-200 group-hover:-translate-x-0.5">arrow_forward</span>
            <span class="hidden sm:inline">قبلی</span>
        </button>

        @if($step < $totalSteps)
            {{-- Next Button --}}
            <button
                wire:click="nextStep"
                :disabled="!canProceed"
                title="مرحله بعد"
                aria-busy="false"
                @class([
                    'group relative inline-flex items-center justify-center gap-2 px-6 py-2.5 text-sm font-semibold rounded-xl shadow-md transition-all duration-200 ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--md-sys-color-surface)]',
                    'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[var(--md-sys-color-primary)]/20 hover:brightness-105 hover:shadow-lg hover:shadow-[var(--md-sys-color-primary)]/30 active:scale-[0.98]',
                    'disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none disabled:hover:brightness-100'
                ])
            >
                <span class="hidden sm:inline">بعدی</span>
                <span
                    class="material-symbols-rounded text-[18px] transition-transform duration-200 group-hover:translate-x-0.5">arrow_back</span>
            </button>
        @else
            {{-- Submit Button --}}
            <x-ui.buttons.form
                type="button"
                wire:click="submitTest"
                :disabled="!$canSubmit"
                title="ثبت "
                loading="submitTest"
                icon="check_circle"
                class="group px-6 py-2.5 text-sm font-semibold rounded-xl shadow-md focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-tertiary)] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--md-sys-color-surface)] bg-[var(--md-sys-color-tertiary)] text-[var(--md-sys-color-on-tertiary)] shadow-[var(--md-sys-color-tertiary)]/20 hover:brightness-105 hover:shadow-lg hover:shadow-[var(--md-sys-color-tertiary)]/30 active:scale-[0.98] disabled:shadow-none disabled:hover:brightness-100"
            >
                ثبت پاسخ‌ها
            </x-ui.buttons.form>
        @endif
    </div>
</div>
