<div class="flex items-center justify-center min-h-[calc(100vh-6rem)] w-full px-4 py-8">
    <!-- Main Card -->
    <div class="glass-panel w-full max-w-[520px] p-10 md:p-12 rounded-[2.5rem] relative overflow-hidden isolate shadow-2xl ring-1 ring-white/10"
         x-data="{ showPassword: false, showConfirm: false, strength: 0 }">

        <!-- Stylistic Shapes -->
        <x-dashboard.floating-shapes/>

        <!-- Ambient Background Glows -->
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-[var(--md-sys-color-tertiary)]/20 rounded-full blur-[100px] pointer-events-none mix-blend-screen opacity-50"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-[var(--md-sys-color-secondary)]/20 rounded-full blur-[100px] pointer-events-none mix-blend-screen opacity-50"></div>

        <!-- Header -->
        <div class="relative mb-10 text-center flex flex-col items-center gap-6">
            <!-- Icon Badge -->
            <div class="relative group">
                <div class="absolute inset-0 bg-[var(--md-sys-color-tertiary)]/30 rounded-full blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-700"></div>
                <div class="relative w-24 h-24 bg-gradient-to-br from-[var(--md-sys-color-tertiary-container)] to-[var(--md-sys-color-surface)] rounded-[2rem] flex items-center justify-center shadow-xl ring-1 ring-white/20 transition-transform duration-500 group-hover:scale-105">
                    <span class="material-symbols-rounded text-5xl text-[var(--md-sys-color-tertiary)] drop-shadow-sm">encrypted</span>
                </div>
                <div class="absolute -bottom-2 -right-2 w-8 h-8 bg-[var(--md-sys-color-secondary)] rounded-full flex items-center justify-center shadow-lg ring-2 ring-[var(--md-sys-color-surface)]">
                    <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-on-secondary)]">edit</span>
                </div>
            </div>

            <!-- Status Badge -->
            <div class="inline-flex items-center gap-3 px-5 py-2 rounded-full bg-[var(--md-sys-color-surface-variant)]/40 border border-[var(--md-sys-color-outline-variant)]/20 backdrop-blur-xl shadow-lg shadow-black/5 mt-2">
                <span class="relative flex h-2.5 w-2.5">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[var(--md-sys-color-tertiary)] opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-[var(--md-sys-color-tertiary)]"></span>
                </span>
                <span class="text-[12px] font-bold text-[var(--md-sys-color-on-surface-variant)] tracking-[0.25em] uppercase font-mono">Secure Reset</span>
            </div>

            <!-- Title & Description -->
            <div class="space-y-2">
                <h1 class="text-3xl md:text-4xl font-bold text-[var(--md-sys-color-on-surface)] tracking-tight font-display drop-shadow-sm">
                    تنظیم رمز جدید
                </h1>
                <p class="text-base text-[var(--md-sys-color-on-surface-variant)]/80 max-w-xs mx-auto leading-relaxed">
                    لطفاً یک رمز عبور جدید و قوی برای حساب کاربری خود تعریف کنید.
                </p>
            </div>
        </div>

        <!-- Reset Form -->
        <form wire:submit.prevent="submitReset" class="space-y-8 relative z-10">

            <!-- Hidden Fields -->
            <input type="hidden" wire:model="token">
            <input type="hidden" wire:model="email">

            <!-- New Password -->
            <div class="md3-input-group group relative">
                <div class="relative transition-transform duration-300 focus-within:scale-[1.02]">
                    <input wire:model.lazy="password" id="password" required autocomplete="new-password"
                           :type="showPassword ? 'text' : 'password'"
                           @input="strength = $el.value.length < 6 ? 1 : $el.value.length < 10 ? 2 : 3"
                           class="md3-input peer ps-14 pe-12 py-4 text-lg bg-[var(--md-sys-color-surface-variant)]/30 focus:bg-[var(--md-sys-color-surface-variant)]/50" placeholder=" " />
                    <label for="password" class="md3-label rtl:right-14 ltr:left-14 text-base peer-focus:rtl:!right-4 peer-focus:ltr:!left-4 peer-[:not(:placeholder-shown)]:rtl:!right-4 peer-[:not(:placeholder-shown)]:ltr:!left-4">
                        رمز عبور جدید
                    </label>
                    <span class="material-symbols-rounded absolute top-[22px] rtl:right-4 ltr:left-4 text-[24px] text-[var(--md-sys-color-on-surface-variant)] transition-colors duration-300 peer-focus:text-[var(--md-sys-color-primary)] pointer-events-none">key</span>

                    <!-- Toggle Visibility -->
                    <button type="button" tabindex="-1" @click="showPassword = !showPassword"
                            class="absolute top-1/2 -translate-y-1/2 rtl:left-2 ltr:right-2 p-2 rounded-full hover:bg-[var(--md-sys-color-on-surface)]/10 text-[var(--md-sys-color-on-surface-variant)] transition-all active:scale-95 outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)]">
                        <span class="material-symbols-rounded text-[22px] block" x-text="showPassword ? 'visibility_off' : 'visibility'"></span>
                    </button>
                </div>

                <!-- Strength Bars -->
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
                <div class="mt-2 flex items-center gap-2 text-sm font-medium text-[var(--md-sys-color-error)] animate-slide-up">
                    <span class="material-symbols-rounded text-[18px]">error</span>
                    <span>{{ $message }}</span>
                </div>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div class="md3-input-group group relative">
                <div class="relative transition-transform duration-300 focus-within:scale-[1.02]">
                    <input wire:model.lazy="password_confirmation" id="password_confirmation" required autocomplete="new-password"
                           :type="showConfirm ? 'text' : 'password'"
                           class="md3-input peer ps-14 pe-12 py-4 text-lg bg-[var(--md-sys-color-surface-variant)]/30 focus:bg-[var(--md-sys-color-surface-variant)]/50" placeholder=" " />
                    <label for="password_confirmation" class="md3-label rtl:right-14 ltr:left-14 text-base peer-focus:rtl:!right-4 peer-focus:ltr:!left-4 peer-[:not(:placeholder-shown)]:rtl:!right-4 peer-[:not(:placeholder-shown)]:ltr:!left-4">
                        تکرار رمز عبور
                    </label>
                    <span class="material-symbols-rounded absolute top-[22px] rtl:right-4 ltr:left-4 text-[24px] text-[var(--md-sys-color-on-surface-variant)] transition-colors duration-300 peer-focus:text-[var(--md-sys-color-primary)] pointer-events-none">lock_reset</span>

                    <!-- Toggle Visibility -->
                    <button type="button" tabindex="-1" @click="showConfirm = !showConfirm"
                            class="absolute top-1/2 -translate-y-1/2 rtl:left-2 ltr:right-2 p-2 rounded-full hover:bg-[var(--md-sys-color-on-surface)]/10 text-[var(--md-sys-color-on-surface-variant)] transition-all active:scale-95 outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)]">
                        <span class="material-symbols-rounded text-[22px] block" x-text="showConfirm ? 'visibility_off' : 'visibility'"></span>
                    </button>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="space-y-6 pt-4">
                <button type="submit" wire:loading.attr="disabled" wire:target="submitReset"
                        class="md3-btn w-full h-14 text-lg shadow-xl shadow-[var(--md-sys-color-tertiary)]/20 hover:shadow-[var(--md-sys-color-tertiary)]/40 hover:-translate-y-1 active:translate-y-0 active:shadow-md transition-all duration-300 group overflow-hidden bg-[var(--md-sys-color-tertiary)] text-[var(--md-sys-color-on-tertiary)]">
                    <div class="relative flex items-center justify-center gap-3 w-full z-10">
                        <span class="material-symbols-rounded text-[24px] transition-transform group-hover:scale-110" wire:loading.remove wire:target="submitReset">save_as</span>

                        <!-- Loading Spinner -->
                        <svg class="animate-spin h-6 w-6 text-current absolute left-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" wire:loading wire:target="submitReset">
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

        <!-- Password Requirements -->
        <div class="mt-10 pt-6 border-t border-[var(--md-sys-color-outline-variant)]/20">
            <div class="grid grid-cols-2 gap-4">
                <div class="flex items-center gap-2 text-xs font-medium text-[var(--md-sys-color-on-surface-variant)] opacity-80">
                    <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">check_circle</span>
                    <span>حداقل ۸ کاراکتر</span>
                </div>
                <div class="flex items-center gap-2 text-xs font-medium text-[var(--md-sys-color-on-surface-variant)] opacity-80">
                    <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">check_circle</span>
                    <span>حروف و اعداد</span>
                </div>
            </div>
        </div>
    </div>
</div>
