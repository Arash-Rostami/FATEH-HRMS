<div class="h-full min-h-[480px] flex flex-col items-center justify-center p-8 md:p-12 rounded-2xl bg-[var(--md-sys-color-surface)]
 border-2 border-dashed border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)]">

    <div class="contents" wire:loading wire:target="selectSuggestion">
        <div class="w-full">
            @include('livewire.dashboard.suggestion.detail-placeholder')
        </div>
    </div>

    <div class="contents" wire:loading.remove wire:target="selectSuggestion">
        <div class="flex flex-col items-center w-full">
            <span class="material-symbols-rounded mb-4 select-none !text-[96px] text-[var(--md-sys-color-on-surface)] opacity-[0.07]">
                touch_app
            </span>

            <h3 class="text-base font-bold mb-1 text-[var(--md-sys-color-on-surface)]">
                پیشنهادی انتخاب نشده
            </h3>

            <p class="text-sm mb-6 text-center text-[var(--md-sys-color-on-surface-variant)] max-w-[260px] leading-relaxed">
                از لیست سمت راست یک پیشنهاد انتخاب کنید یا پیشنهاد جدیدی ثبت نمایید.
            </p>

            @if(!in_array($this->authDeptCode, ['MA', 'MG'], true))
            <x-ui.buttons.form
                    icon="add"
                    wire:click="openCreate">
                ثبت پیشنهاد جدید
            </x-ui.buttons.form>
            @endif

            <div class="w-full h-px mt-10 bg-gradient-to-r from-transparent via-[var(--md-sys-color-outline-variant)] to-transparent opacity-40 my-6"></div>

            @include('livewire.dashboard.suggestion.flowchart')
        </div>
    </div>
</div>