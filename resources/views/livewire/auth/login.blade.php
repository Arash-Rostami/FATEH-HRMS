<div class="flex items-center justify-center min-h-[calc(100vh-6rem)] w-full px-4 py-8">
    <div class="glass-panel w-full max-w-[520px] p-10 md:p-12 rounded-[2.5rem] relative overflow-hidden isolate shadow-2xl ring-1 ring-white/10"
         x-data="{
            showPassword: false,
            remember: @entangle('remember')
         }">

        <div class="absolute -top-40 -right-40 w-80 h-80 bg-[var(--md-sys-color-primary)]/20 rounded-full pointer-events-none opacity-50"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-[var(--md-sys-color-tertiary)]/20 rounded-full pointer-events-none opacity-50"></div>

        <div class="relative mb-12 text-center flex flex-col items-center gap-6">
            <div class="inline-flex items-center gap-3 px-5 py-2 rounded-full bg-[var(--md-sys-color-surface-variant)]/40 border border-[var(--md-sys-color-outline-variant)]/20 shadow-lg shadow-black/5">
                <span class="relative flex h-2.5 w-2.5">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[var(--md-sys-color-primary)] opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-[var(--md-sys-color-primary)]"></span>
                </span>
                <span class="text-[12px] font-bold text-[var(--md-sys-color-on-surface-variant)] tracking-[0.25em] uppercase font-mono">ERP Secure Gate</span>
            </div>

            <h1 class="text-4xl md:text-5xl font-bold text-[var(--md-sys-color-on-surface)] tracking-tight font-display">
                ورود به سیستم
            </h1>
        </div>

        <form wire:submit.prevent="login" class="space-y-8 relative z-10">
            <div class="space-y-6">
                <div class="md3-input-group group relative">
                    <div class="relative transition-transform duration-200 focus-within:scale-[1.01]">
                        <input
                            wire:model.lazy="email"
                            type="email"
                            id="email"
                            class="md3-input peer ps-14 pe-4 py-4 text-lg"
                            placeholder=" "
                            required
                            autocomplete="username"
                        />
                        <label for="email" class="md3-label rtl:right-14 ltr:left-14 text-base peer-focus:rtl:!right-4 peer-focus:ltr:!left-4 peer-[:not(:placeholder-shown)]:rtl:!right-4 peer-[:not(:placeholder-shown)]:ltr:!left-4">
                            آدرس ایمیل سازمانی
                        </label>
                        <span class="material-symbols-rounded absolute top-[22px] rtl:right-4 ltr:left-4 text-[24px] text-[var(--md-sys-color-on-surface-variant)] transition-colors duration-200 peer-focus:text-[var(--md-sys-color-primary)] pointer-events-none">
                            admin_panel_settings
                        </span>
                    </div>
                    @error('email')
                    <div class="mt-2 flex items-center gap-2 text-sm font-medium text-[var(--md-sys-color-error)]" role="alert">
                        <span class="material-symbols-rounded text-[18px]">error</span>
                        <span>{{ $message }}</span>
                    </div>
                    @enderror
                </div>

                <div class="md3-input-group group relative">
                    <div class="relative transition-transform duration-200 focus-within:scale-[1.01]">
                        <input
                            wire:model.lazy="password"
                            :type="showPassword ? 'text' : 'password'"
                            id="password"
                            class="md3-input peer ps-14 pe-14 py-4 text-lg"
                            placeholder=" "
                            required
                            autocomplete="current-password"
                        />
                        <label for="password" class="md3-label rtl:right-14 ltr:left-14 text-base peer-focus:rtl:!right-4 peer-focus:ltr:!left-4 peer-[:not(:placeholder-shown)]:rtl:!right-4 peer-[:not(:placeholder-shown)]:ltr:!left-4">
                            کد امنیتی / رمز عبور
                        </label>
                        <span class="material-symbols-rounded absolute top-[22px] rtl:right-4 ltr:left-4 text-[24px] text-[var(--md-sys-color-on-surface-variant)] transition-colors duration-200 peer-focus:text-[var(--md-sys-color-primary)] pointer-events-none">
                            fingerprint
                        </span>
                        <button type="button"
                                @click="showPassword = !showPassword"
                                class="absolute top-1/2 -translate-y-1/2 rtl:left-2 ltr:right-2 p-2.5 rounded-full hover:bg-[var(--md-sys-color-on-surface)]/10 text-[var(--md-sys-color-on-surface-variant)] transition-opacity active:scale-95 outline-none"
                                tabindex="-1">
                            <span class="material-symbols-rounded text-[24px] block" x-text="showPassword ? 'visibility_off' : 'visibility'"></span>
                        </button>
                    </div>
                    @error('password')
                    <div class="mt-2 flex items-center gap-2 text-sm font-medium text-[var(--md-sys-color-error)]" role="alert">
                        <span class="material-symbols-rounded text-[18px]">error</span>
                        <span>{{ $message }}</span>
                    </div>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-between pt-2 px-1">
                <label class="flex items-center gap-3 cursor-pointer group select-none py-1">
                    <input x-model="remember" type="checkbox" class="sr-only">
                    <div class="relative w-[52px] h-[32px] rounded-full transition-colors duration-200 ease-in-out shadow-inner"
                         :class="remember ? 'bg-[var(--md-sys-color-primary)]' : 'bg-[var(--md-sys-color-surface-container-highest)]'">
                        <div class="absolute top-1/2 -translate-y-1/2 ltr:left-1 rtl:right-1 w-6 h-6 rounded-full bg-white shadow-md transform transition-all duration-200 ease-in-out flex items-center justify-center"
                             :class="remember ? 'ltr:translate-x-5 rtl:-translate-x-5' : 'translate-x-0'">
            <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-primary)] transition-opacity duration-150"
                  :class="remember ? 'opacity-100' : 'opacity-0'">check</span>
                        </div>
                    </div>
                    <span class="text-sm font-medium text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-on-surface)] transition-colors">
        شناسایی دستگاه
    </span>
                </label>
                <a href="{{ route('password.request') }}"
                   class="text-sm font-bold text-[var(--md-sys-color-primary)] hover:underline decoration-2 underline-offset-4 px-3 py-1.5 rounded-lg transition-colors">
                    بازیابی دسترسی
                </a>
            </div>

            <div class="space-y-6 pt-6">
                <button type="submit"
                        class="md3-btn w-full h-14 text-lg shadow-lg shadow-[var(--md-sys-color-primary)]/10 active:shadow-none transition-all group"
                        wire:loading.attr="disabled"
                        wire:target="login">
                    <div class="relative flex items-center justify-center gap-3 w-full">
                        <span class="material-symbols-rounded text-[26px]" wire:loading.remove wire:target="login">login</span>

                        <svg class="animate-spin h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" wire:loading wire:target="login">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <span wire:loading.remove wire:target="login" class="font-bold tracking-wide">احراز هویت و ورود</span>
                        <span wire:loading wire:target="login" class="font-bold tracking-wide">درحال بررسی اعتبار...</span>
                    </div>
                </button>

                <div class="relative flex py-2 items-center">
                    <div class="flex-grow border-t border-[var(--md-sys-color-outline-variant)]/30"></div>
                    <span class="flex-shrink-0 mx-6 text-xs font-bold text-[var(--md-sys-color-outline)] uppercase tracking-[0.2em] opacity-70">یا</span>
                    <div class="flex-grow border-t border-[var(--md-sys-color-outline-variant)]/30"></div>
                </div>

                <a href="{{ route('register') }}"
                   class="md3-btn-outlined w-full h-14 text-lg border-2 border-[var(--md-sys-color-outline-variant)]/50 text-[var(--md-sys-color-on-surface-variant)] transition-all">
                    <span class="material-symbols-rounded text-[24px]">person_add_alt</span>
                    <span class="font-bold">ثبت نام کاربر جدید</span>
                </a>
            </div>
        </form>
    </div>
</div>
