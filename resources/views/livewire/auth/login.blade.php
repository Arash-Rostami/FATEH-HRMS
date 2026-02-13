<x-auth.card title="ورود به سیستم" description="جهت دسترسی به پنل کاربری، اطلاعات ورود خود را وارد نمایید.">
    <div x-data="{ showPassword: false, remember: @entangle('remember') }">
        <form wire:submit.prevent="login" class="space-y-6">
            <div class="space-y-5">
                {{-- Email Input --}}
                <div class="md3-input-group group relative">
                    <div class="relative transition-transform duration-200 focus-within:scale-[1.01]">
                        <input
                            wire:model.lazy="email"
                            type="email"
                            id="email"
                            class="md3-input peer ps-14 pe-4 py-3.5 text-base bg-[var(--md-sys-color-surface-container)]/50 focus:bg-[var(--md-sys-color-surface-container)]"
                            placeholder=" "
                            required
                            autocomplete="username"
                        />
                        <label for="email"
                               class="md3-label rtl:right-14 ltr:left-14 text-sm peer-focus:rtl:!right-4 peer-focus:ltr:!left-4 peer-[:not(:placeholder-shown)]:rtl:!right-4 peer-[:not(:placeholder-shown)]:ltr:!left-4">
                            آدرس ایمیل سازمانی
                        </label>
                        <span
                            class="material-symbols-rounded absolute top-[18px] rtl:right-4 ltr:left-4 text-[22px] text-[var(--md-sys-color-on-surface-variant)] transition-colors duration-200 peer-focus:text-[var(--md-sys-color-primary)] pointer-events-none">
                            admin_panel_settings
                        </span>
                    </div>
                    @error('email')
                    <div
                        class="mt-1 flex items-center gap-2 text-xs font-medium text-[var(--md-sys-color-error)] animate-slide-up"
                        role="alert">
                        <span class="material-symbols-rounded text-[16px]">error</span>
                        <span>{{ $message }}</span>
                    </div>
                    @enderror
                </div>

                {{-- Password Input --}}
                <div class="md3-input-group group relative">
                    <div class="relative transition-transform duration-200 focus-within:scale-[1.01]">
                        <input
                            wire:model.lazy="password"
                            :type="showPassword ? 'text' : 'password'"
                            id="password"
                            class="md3-input peer ps-14 pe-14 py-3.5 text-base bg-[var(--md-sys-color-surface-container)]/50 focus:bg-[var(--md-sys-color-surface-container)]"
                            placeholder=" "
                            required
                            autocomplete="current-password"
                        />
                        <label for="password"
                               class="md3-label rtl:right-14 ltr:left-14 text-sm peer-focus:rtl:!right-4 peer-focus:ltr:!left-4 peer-[:not(:placeholder-shown)]:rtl:!right-4 peer-[:not(:placeholder-shown)]:ltr:!left-4">
                            کد امنیتی / رمز عبور
                        </label>
                        <span
                            class="material-symbols-rounded absolute top-[18px] rtl:right-4 ltr:left-4 text-[22px] text-[var(--md-sys-color-on-surface-variant)] transition-colors duration-200 peer-focus:text-[var(--md-sys-color-primary)] pointer-events-none">
                            fingerprint
                        </span>
                        <button type="button"
                                @click="showPassword = !showPassword"
                                class="absolute top-1/2 -translate-y-1/2 rtl:left-2 ltr:right-2 p-2 rounded-full hover:bg-[var(--md-sys-color-on-surface)]/10 text-[var(--md-sys-color-on-surface-variant)] transition-opacity active:scale-95 outline-none"
                                tabindex="-1">
                            <span class="material-symbols-rounded text-[22px] block"
                                  x-text="showPassword ? 'visibility_off' : 'visibility'"></span>
                        </button>
                    </div>
                    @error('password')
                    <div
                        class="mt-1 flex items-center gap-2 text-xs font-medium text-[var(--md-sys-color-error)] animate-slide-up"
                        role="alert">
                        <span class="material-symbols-rounded text-[16px]">error</span>
                        <span>{{ $message }}</span>
                    </div>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-between px-1">
                <label class="flex items-center gap-2.5 cursor-pointer group select-none py-1">
                    <input x-model="remember" type="checkbox" class="sr-only">
                    <div
                        class="relative w-[44px] h-[26px] rounded-full transition-colors duration-200 ease-in-out shadow-inner"
                        :class="remember ? 'bg-[var(--md-sys-color-primary)]' : 'bg-[var(--md-sys-color-surface-container-highest)]'">
                        <div
                            class="absolute top-1/2 -translate-y-1/2 ltr:left-1 rtl:right-1 w-[18px] h-[18px] rounded-full bg-white shadow-md transform transition-all duration-200 ease-in-out flex items-center justify-center"
                            :class="remember ? 'ltr:translate-x-[18px] rtl:-translate-x-[18px]' : 'translate-x-0'">
                             <span
                                 class="material-symbols-rounded text-[12px] text-[var(--md-sys-color-primary)] transition-opacity duration-150"
                                 :class="remember ? 'opacity-100' : 'opacity-0'">check</span>
                        </div>
                    </div>
                    <span
                        class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-on-surface)] transition-colors">
                        شناسایی دستگاه
                    </span>
                </label>
                <a href="{{ route('password.request') }}"
                   class="text-xs font-bold text-[var(--md-sys-color-primary)] hover:underline decoration-2 underline-offset-4 px-2 py-1 rounded-lg transition-colors">
                    فراموشی رمز عبور؟
                </a>
            </div>

            <div class="space-y-4 pt-2">
                <button type="submit"
                        class="md3-btn w-full h-12 text-base shadow-lg shadow-[var(--md-sys-color-primary)]/15 active:shadow-none transition-all group hover:scale-[1.01]"
                        wire:loading.attr="disabled"
                        wire:target="login">
                    <div class="relative flex items-center justify-center gap-2 w-full">
                        <span class="material-symbols-rounded text-[22px]" wire:loading.remove
                              wire:target="login">login</span>

                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                             viewBox="0 0 24 24" wire:loading wire:target="login">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <span wire:loading.remove wire:target="login"
                              class="font-bold tracking-wide">احراز هویت و ورود</span>
                        <span wire:loading wire:target="login" class="font-bold tracking-wide">درحال بررسی...</span>
                    </div>
                </button>

                <div class="relative flex py-1 items-center">
                    <div class="flex-grow border-t border-[var(--md-sys-color-outline-variant)]/30"></div>
                    <span
                        class="flex-shrink-0 mx-4 text-[10px] font-bold text-[var(--md-sys-color-outline)] uppercase tracking-[0.2em] opacity-70">یا</span>
                    <div class="flex-grow border-t border-[var(--md-sys-color-outline-variant)]/30"></div>
                </div>

                <a href="{{ route('register') }}"
                   class="md3-btn-outlined w-full h-12 text-base border-2 border-[var(--md-sys-color-outline-variant)]/50 text-[var(--md-sys-color-on-surface-variant)] transition-all hover:border-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary)]/5">
                    <span class="material-symbols-rounded text-[20px]">person_add_alt</span>
                    <span class="font-bold">ثبت نام کاربر جدید</span>
                </a>
            </div>
        </form>
    </div>
</x-auth.card>
