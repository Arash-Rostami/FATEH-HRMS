<div class="flex items-center justify-center min-h-[calc(100vh-6rem)] w-full px-4 py-8">
    <div class="glass-panel w-full max-w-[520px] p-10 md:p-12 rounded-[2.5rem] relative overflow-hidden isolate shadow-2xl ring-1 ring-white/10"
         x-data="{ showPassword: false, showConfirm: false, strength: 0 }">

        <div class="absolute -top-40 -right-40 w-80 h-80 bg-[var(--md-sys-color-primary)]/20 rounded-full blur-[100px] pointer-events-none mix-blend-screen opacity-50"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-[var(--md-sys-color-tertiary)]/20 rounded-full blur-[100px] pointer-events-none mix-blend-screen opacity-50"></div>

        <div class="relative mb-10 text-center flex flex-col items-center gap-6">
            <div class="inline-flex items-center gap-3 px-5 py-2 rounded-full bg-[var(--md-sys-color-surface-variant)]/40 border border-[var(--md-sys-color-outline-variant)]/20 backdrop-blur-xl shadow-lg shadow-black/5">
                <span class="relative flex h-2.5 w-2.5">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[var(--md-sys-color-tertiary)] opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-[var(--md-sys-color-tertiary)]"></span>
                </span>
                <span class="text-[12px] font-bold text-[var(--md-sys-color-on-surface-variant)] tracking-[0.25em] uppercase font-mono">New Account</span>
            </div>

            <h1 class="text-4xl md:text-5xl font-bold text-[var(--md-sys-color-on-surface)] tracking-tight font-display drop-shadow-sm">
                ایجاد حساب کاربری
            </h1>
            <p class="text-base text-[var(--md-sys-color-on-surface-variant)]/80 max-w-sm mx-auto leading-relaxed">
                به جمع متخصصین ما بپیوندید و دسترسی کامل به پلتفرم سازمانی داشته باشید.
            </p>
        </div>

        <form wire:submit.prevent="register" class="space-y-8 relative z-10">
            <div class="space-y-6">
                <div class="md3-input-group group relative">
                    <div class="relative transition-transform duration-300 focus-within:scale-[1.02]">
                        <input
                            wire:model.lazy="name"
                            type="text"
                            id="name"
                            class="md3-input peer ps-14 pe-4 py-4 text-lg bg-[var(--md-sys-color-surface-variant)]/30 focus:bg-[var(--md-sys-color-surface-variant)]/50"
                            placeholder=" "
                            required
                            autocomplete="name"
                        />
                        <label for="name" class="md3-label rtl:right-14 ltr:left-14 text-base peer-focus:rtl:!right-4 peer-focus:ltr:!left-4 peer-[:not(:placeholder-shown)]:rtl:!right-4 peer-[:not(:placeholder-shown)]:ltr:!left-4">
                            نام و نام خانوادگی
                        </label>
                        <span class="material-symbols-rounded absolute top-[22px] rtl:right-4 ltr:left-4 text-[24px] text-[var(--md-sys-color-on-surface-variant)] transition-colors duration-300 peer-focus:text-[var(--md-sys-color-primary)] pointer-events-none">
                            badge
                        </span>
                    </div>
                    @error('name')
                    <div class="mt-2 flex items-center gap-2 text-sm font-medium text-[var(--md-sys-color-error)] animate-slide-up">
                        <span class="material-symbols-rounded text-[18px]">error</span>
                        <span>{{ $message }}</span>
                    </div>
                    @enderror
                </div>

                <div class="md3-input-group group relative">
                    <div class="relative transition-transform duration-300 focus-within:scale-[1.02]">
                        <input
                            wire:model.lazy="email"
                            type="email"
                            id="email"
                            class="md3-input peer ps-14 pe-4 py-4 text-lg bg-[var(--md-sys-color-surface-variant)]/30 focus:bg-[var(--md-sys-color-surface-variant)]/50"
                            placeholder=" "
                            required
                            autocomplete="email"
                        />
                        <label for="email" class="md3-label rtl:right-14 ltr:left-14 text-base peer-focus:rtl:!right-4 peer-focus:ltr:!left-4 peer-[:not(:placeholder-shown)]:rtl:!right-4 peer-[:not(:placeholder-shown)]:ltr:!left-4">
                            آدرس ایمیل سازمانی
                        </label>
                        <span class="material-symbols-rounded absolute top-[22px] rtl:right-4 ltr:left-4 text-[24px] text-[var(--md-sys-color-on-surface-variant)] transition-colors duration-300 peer-focus:text-[var(--md-sys-color-primary)] pointer-events-none">
                            alternate_email
                        </span>
                    </div>
                    @error('email')
                    <div class="mt-2 flex items-center gap-2 text-sm font-medium text-[var(--md-sys-color-error)] animate-slide-up">
                        <span class="material-symbols-rounded text-[18px]">error</span>
                        <span>{{ $message }}</span>
                    </div>
                    @enderror
                </div>

                <div class="md3-input-group group relative">
                    <div class="relative transition-transform duration-300 focus-within:scale-[1.02]">
                        <input
                            wire:model.lazy="password"
                            :type="showPassword ? 'text' : 'password'"
                            id="password"
                            @input="strength = $el.value.length < 6 ? 1 : $el.value.length < 10 ? 2 : 3"
                            class="md3-input peer ps-14 pe-12 py-4 text-lg bg-[var(--md-sys-color-surface-variant)]/30 focus:bg-[var(--md-sys-color-surface-variant)]/50"
                            placeholder=" "
                            required
                            autocomplete="new-password"
                        />
                        <label for="password" class="md3-label rtl:right-14 ltr:left-14 text-base peer-focus:rtl:!right-4 peer-focus:ltr:!left-4 peer-[:not(:placeholder-shown)]:rtl:!right-4 peer-[:not(:placeholder-shown)]:ltr:!left-4">
                            رمز عبور
                        </label>
                        <span class="material-symbols-rounded absolute top-[22px] rtl:right-4 ltr:left-4 text-[24px] text-[var(--md-sys-color-on-surface-variant)] transition-colors duration-300 peer-focus:text-[var(--md-sys-color-primary)] pointer-events-none">
                            key
                        </span>
                        <button type="button"
                                @click="showPassword = !showPassword"
                                class="absolute top-1/2 -translate-y-1/2 rtl:left-2 ltr:right-2 p-2 rounded-full hover:bg-[var(--md-sys-color-on-surface)]/10 text-[var(--md-sys-color-on-surface-variant)] transition-all active:scale-95 outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)]"
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
                    <div class="mt-2 flex items-center gap-2 text-sm font-medium text-[var(--md-sys-color-error)] animate-slide-up">
                        <span class="material-symbols-rounded text-[18px]">error</span>
                        <span>{{ $message }}</span>
                    </div>
                    @enderror
                </div>

                <div class="md3-input-group group relative">
                    <div class="relative transition-transform duration-300 focus-within:scale-[1.02]">
                        <input
                            wire:model.lazy="password_confirmation"
                            :type="showConfirm ? 'text' : 'password'"
                            id="password_confirmation"
                            class="md3-input peer ps-14 pe-12 py-4 text-lg bg-[var(--md-sys-color-surface-variant)]/30 focus:bg-[var(--md-sys-color-surface-variant)]/50"
                            placeholder=" "
                            required
                            autocomplete="new-password"
                        />
                        <label for="password_confirmation" class="md3-label rtl:right-14 ltr:left-14 text-base peer-focus:rtl:!right-4 peer-focus:ltr:!left-4 peer-[:not(:placeholder-shown)]:rtl:!right-4 peer-[:not(:placeholder-shown)]:ltr:!left-4">
                            تکرار رمز عبور
                        </label>
                        <span class="material-symbols-rounded absolute top-[22px] rtl:right-4 ltr:left-4 text-[24px] text-[var(--md-sys-color-on-surface-variant)] transition-colors duration-300 peer-focus:text-[var(--md-sys-color-primary)] pointer-events-none">
                            lock_reset
                        </span>
                        <button type="button"
                                @click="showConfirm = !showConfirm"
                                class="absolute top-1/2 -translate-y-1/2 rtl:left-2 ltr:right-2 p-2 rounded-full hover:bg-[var(--md-sys-color-on-surface)]/10 text-[var(--md-sys-color-on-surface-variant)] transition-all active:scale-95 outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)]"
                                tabindex="-1">
                            <span class="material-symbols-rounded text-[22px] block" x-text="showConfirm ? 'visibility_off' : 'visibility'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="space-y-6 pt-6">
                <button type="submit"
                        class="md3-btn w-full h-14 text-lg shadow-xl shadow-[var(--md-sys-color-primary)]/20 hover:shadow-[var(--md-sys-color-primary)]/40 hover:-translate-y-1 active:translate-y-0 active:shadow-md transition-all duration-300 group"
                        wire:loading.attr="disabled"
                        wire:target="register">
                    <div class="relative flex items-center justify-center gap-3 w-full">
                        <div class="absolute inset-0 bg-white/20 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700 ease-in-out skew-x-12 opacity-50"></div>

                        <span class="material-symbols-rounded text-[26px] transition-transform group-hover:scale-110" wire:loading.remove wire:target="register">person_add</span>

                        <svg class="animate-spin h-6 w-6 text-white absolute left-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" wire:loading wire:target="register">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <span wire:loading.remove wire:target="register" class="font-bold tracking-wide">ثبت نام و ایجاد حساب</span>
                        <span wire:loading wire:target="register" class="font-bold tracking-wide">در حال ایجاد حساب...</span>
                    </div>
                </button>

                <div class="relative flex py-2 items-center">
                    <div class="flex-grow border-t-2 border-[var(--md-sys-color-outline-variant)]/20"></div>
                    <span class="flex-shrink-0 mx-6 text-xs font-bold text-[var(--md-sys-color-outline)] uppercase tracking-[0.2em] opacity-70">حساب دارید؟</span>
                    <div class="flex-grow border-t-2 border-[var(--md-sys-color-outline-variant)]/20"></div>
                </div>

                <a href="{{ route('login') }}"
                   class="md3-btn-outlined w-full h-14 text-lg border-2 border-[var(--md-sys-color-outline-variant)] hover:border-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary)]/5 text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-primary)] transition-all duration-300">
                    <span class="material-symbols-rounded text-[24px]">login</span>
                    <span class="font-bold">ورود به سیستم</span>
                </a>
            </div>
        </form>

        <div class="mt-8 text-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[var(--md-sys-color-surface-variant)]/30 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/10">
                <span class="material-symbols-rounded text-[16px]">verified_user</span>
                <span>اطلاعات شما با استاندارد های امنیتی بانکی محافظت می‌شود</span>
            </div>
        </div>
    </div>
</div>
