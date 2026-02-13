<x-auth.card title="تنظیم رمز جدید" description="لطفاً یک رمز عبور جدید و قوی برای حساب خود تعریف کنید.">
    <div x-data="{ showPassword: false, showConfirm: false, strength: 0 }">
        <form wire:submit.prevent="submitReset" class="space-y-6">
            <input type="hidden" wire:model="token">
            <input type="hidden" wire:model="email">

            <div class="space-y-5">
                <div class="md3-input-group group relative">
                    <div class="relative transition-transform duration-200 focus-within:scale-[1.01]">
                        <input
                            wire:model.lazy="password"
                            :type="showPassword ? 'text' : 'password'"
                            id="password"
                            @input="strength = $el.value.length < 6 ? 1 : $el.value.length < 10 ? 2 : 3"
                            class="md3-input peer ps-14 pe-12 py-3.5 text-base bg-[var(--md-sys-color-surface-container)]/50 focus:bg-[var(--md-sys-color-surface-container)]"
                            placeholder=" "
                            required
                            autocomplete="new-password"
                        />
                        <label for="password" class="md3-label rtl:right-14 ltr:left-14 text-sm peer-focus:rtl:!right-4 peer-focus:ltr:!left-4 peer-[:not(:placeholder-shown)]:rtl:!right-4 peer-[:not(:placeholder-shown)]:ltr:!left-4">
                            رمز عبور جدید
                        </label>
                        <span class="material-symbols-rounded absolute top-[18px] rtl:right-4 ltr:left-4 text-[22px] text-[var(--md-sys-color-on-surface-variant)] transition-colors duration-200 peer-focus:text-[var(--md-sys-color-primary)] pointer-events-none">
                            key
                        </span>
                        <button type="button"
                                @click="showPassword = !showPassword"
                                class="absolute top-1/2 -translate-y-1/2 rtl:left-2 ltr:right-2 p-2 rounded-full hover:bg-[var(--md-sys-color-on-surface)]/10 text-[var(--md-sys-color-on-surface-variant)] transition-all active:scale-95 outline-none"
                                tabindex="-1">
                            <span class="material-symbols-rounded text-[22px] block" x-text="showPassword ? 'visibility_off' : 'visibility'"></span>
                        </button>
                    </div>

                    <div class="flex gap-1 mt-2 h-1 px-1 opacity-0 transition-opacity duration-300" :class="{ 'opacity-100': strength > 0 }">
                        <div class="flex-1 rounded-full bg-[var(--md-sys-color-surface-variant)] overflow-hidden">
                            <div class="h-full w-full transition-all duration-500 origin-left"
                                 :class="{ 'bg-[var(--md-sys-color-error)]': strength >= 1, 'translate-x-full rtl:-translate-x-full': strength < 1 }"></div>
                        </div>
                        <div class="flex-1 rounded-full bg-[var(--md-sys-color-surface-variant)] overflow-hidden">
                            <div class="h-full w-full transition-all duration-500 origin-left"
                                 :class="{ 'bg-yellow-500': strength >= 2, 'translate-x-full rtl:-translate-x-full': strength < 2 }"></div>
                        </div>
                        <div class="flex-1 rounded-full bg-[var(--md-sys-color-surface-variant)] overflow-hidden">
                            <div class="h-full w-full transition-all duration-500 origin-left"
                                 :class="{ 'bg-green-500': strength >= 3, 'translate-x-full rtl:-translate-x-full': strength < 3 }"></div>
                        </div>
                    </div>

                    @error('password')
                    <div class="mt-1 flex items-center gap-2 text-xs font-medium text-[var(--md-sys-color-error)] animate-slide-up">
                        <span class="material-symbols-rounded text-[16px]">error</span>
                        <span>{{ $message }}</span>
                    </div>
                    @enderror
                </div>

                <div class="md3-input-group group relative">
                    <div class="relative transition-transform duration-200 focus-within:scale-[1.01]">
                        <input
                            wire:model.lazy="password_confirmation"
                            :type="showConfirm ? 'text' : 'password'"
                            id="password_confirmation"
                            class="md3-input peer ps-14 pe-12 py-3.5 text-base bg-[var(--md-sys-color-surface-container)]/50 focus:bg-[var(--md-sys-color-surface-container)]"
                            placeholder=" "
                            required
                            autocomplete="new-password"
                        />
                        <label for="password_confirmation" class="md3-label rtl:right-14 ltr:left-14 text-sm peer-focus:rtl:!right-4 peer-focus:ltr:!left-4 peer-[:not(:placeholder-shown)]:rtl:!right-4 peer-[:not(:placeholder-shown)]:ltr:!left-4">
                            تکرار رمز عبور
                        </label>
                        <span class="material-symbols-rounded absolute top-[18px] rtl:right-4 ltr:left-4 text-[22px] text-[var(--md-sys-color-on-surface-variant)] transition-colors duration-200 peer-focus:text-[var(--md-sys-color-primary)] pointer-events-none">
                            lock_reset
                        </span>
                        <button type="button"
                                @click="showConfirm = !showConfirm"
                                class="absolute top-1/2 -translate-y-1/2 rtl:left-2 ltr:right-2 p-2 rounded-full hover:bg-[var(--md-sys-color-on-surface)]/10 text-[var(--md-sys-color-on-surface-variant)] transition-all active:scale-95 outline-none"
                                tabindex="-1">
                            <span class="material-symbols-rounded text-[22px] block" x-text="showConfirm ? 'visibility_off' : 'visibility'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="space-y-4 pt-6">
                <button type="submit"
                        class="md3-btn w-full h-12 text-base shadow-lg shadow-[var(--md-sys-color-tertiary)]/20 hover:shadow-[var(--md-sys-color-tertiary)]/40 hover:-translate-y-1 active:translate-y-0 active:shadow-md transition-all duration-300 group overflow-hidden bg-[var(--md-sys-color-tertiary)] text-[var(--md-sys-color-on-tertiary)]"
                        wire:loading.attr="disabled"
                        wire:target="submitReset">
                    <div class="relative flex items-center justify-center gap-2 w-full z-10">
                        <span class="material-symbols-rounded text-[22px] transition-transform group-hover:scale-110" wire:loading.remove wire:target="submitReset">save_as</span>

                        <svg class="animate-spin h-5 w-5 text-current absolute left-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" wire:loading wire:target="submitReset">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <span wire:loading.remove wire:target="submitReset" class="font-bold tracking-wide">ذخیره رمز جدید</span>
                        <span wire:loading wire:target="submitReset" class="font-bold tracking-wide">در حال بروزرسانی...</span>
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000 ease-in-out"></div>
                </button>
            </div>
        </form>

        <div class="mt-8 pt-4 border-t border-[var(--md-sys-color-outline-variant)]/20">
            <div class="grid grid-cols-2 gap-4">
                <div class="flex items-center gap-2 text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)] opacity-80">
                    <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">check_circle</span>
                    <span>حداقل ۸ کاراکتر</span>
                </div>
                <div class="flex items-center gap-2 text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)] opacity-80">
                    <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">check_circle</span>
                    <span>حروف و اعداد</span>
                </div>
            </div>
        </div>
    </div>
</x-auth-card>
