<x-auth-card title="ایجاد حساب کاربری" description="به جمع متخصصین ما بپیوندید و دسترسی کامل داشته باشید.">
    <x-slot:imageContent>
        {{-- Custom Register Branding --}}
        <div class="relative group-hover:rotate-6 transition-transform duration-700 ease-in-out">
             <div class="absolute inset-0 bg-gradient-to-bl from-[var(--md-sys-color-secondary)] to-[var(--md-sys-color-tertiary)] opacity-30 blur-2xl rounded-full"></div>
            <div class="w-32 h-32 rounded-[2.5rem] bg-gradient-to-br from-white/10 to-white/5 backdrop-blur-2xl border border-white/20 shadow-2xl flex items-center justify-center relative z-10">
                <span class="material-symbols-rounded text-[80px] text-[var(--md-sys-color-on-surface)] drop-shadow-2xl">person_add</span>
            </div>
        </div>
        <div class="space-y-4 text-center relative z-10">
            <h2 class="text-3xl font-bold text-[var(--md-sys-color-on-surface)] tracking-tight drop-shadow-sm">Join the Future</h2>
            <p class="text-[var(--md-sys-color-on-surface-variant)] text-lg max-w-[250px] mx-auto leading-relaxed font-medium">
                شروع یک تجربه کاری متفاوت<br>ثبت نام در سامانه
            </p>
        </div>
    </x-slot:imageContent>

    <div x-data="{ showPassword: false, showConfirm: false, strength: 0 }">
        <form wire:submit.prevent="register" class="space-y-5">
            {{-- Name --}}
            <div class="md3-input-group group relative">
                <div class="relative transition-transform duration-200 focus-within:scale-[1.01]">
                    <input
                        wire:model.lazy="name"
                        type="text"
                        id="name"
                        class="md3-input peer ps-14 pe-4 py-3.5 text-base bg-[var(--md-sys-color-surface-container)]/50 focus:bg-[var(--md-sys-color-surface-container)]"
                        placeholder=" "
                        required
                        autocomplete="name"
                    />
                    <label for="name" class="md3-label rtl:right-14 ltr:left-14 text-sm peer-focus:rtl:!right-4 peer-focus:ltr:!left-4 peer-[:not(:placeholder-shown)]:rtl:!right-4 peer-[:not(:placeholder-shown)]:ltr:!left-4">
                        نام و نام خانوادگی
                    </label>
                    <span class="material-symbols-rounded absolute top-[18px] rtl:right-4 ltr:left-4 text-[22px] text-[var(--md-sys-color-on-surface-variant)] transition-colors duration-200 peer-focus:text-[var(--md-sys-color-primary)] pointer-events-none">
                        badge
                    </span>
                </div>
                @error('name')
                <div class="mt-1 flex items-center gap-2 text-xs font-medium text-[var(--md-sys-color-error)] animate-slide-up">
                    <span class="material-symbols-rounded text-[16px]">error</span>
                    <span>{{ $message }}</span>
                </div>
                @enderror
            </div>

            {{-- Email --}}
            <div class="md3-input-group group relative">
                <div class="relative transition-transform duration-200 focus-within:scale-[1.01]">
                    <input
                        wire:model.lazy="email"
                        type="email"
                        id="email"
                        class="md3-input peer ps-14 pe-4 py-3.5 text-base bg-[var(--md-sys-color-surface-container)]/50 focus:bg-[var(--md-sys-color-surface-container)]"
                        placeholder=" "
                        required
                        autocomplete="email"
                    />
                    <label for="email" class="md3-label rtl:right-14 ltr:left-14 text-sm peer-focus:rtl:!right-4 peer-focus:ltr:!left-4 peer-[:not(:placeholder-shown)]:rtl:!right-4 peer-[:not(:placeholder-shown)]:ltr:!left-4">
                        آدرس ایمیل سازمانی
                    </label>
                    <span class="material-symbols-rounded absolute top-[18px] rtl:right-4 ltr:left-4 text-[22px] text-[var(--md-sys-color-on-surface-variant)] transition-colors duration-200 peer-focus:text-[var(--md-sys-color-primary)] pointer-events-none">
                        alternate_email
                    </span>
                </div>
                @error('email')
                <div class="mt-1 flex items-center gap-2 text-xs font-medium text-[var(--md-sys-color-error)] animate-slide-up">
                    <span class="material-symbols-rounded text-[16px]">error</span>
                    <span>{{ $message }}</span>
                </div>
                @enderror
            </div>

            {{-- Passwords Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md3-input-group group relative">
                    <div class="relative transition-transform duration-200 focus-within:scale-[1.01]">
                        <input
                            wire:model.lazy="password"
                            :type="showPassword ? 'text' : 'password'"
                            id="password"
                            @input="strength = $el.value.length < 6 ? 1 : $el.value.length < 10 ? 2 : 3"
                            class="md3-input peer ps-12 pe-10 py-3.5 text-base bg-[var(--md-sys-color-surface-container)]/50 focus:bg-[var(--md-sys-color-surface-container)]"
                            placeholder=" "
                            required
                            autocomplete="new-password"
                        />
                        <label for="password" class="md3-label rtl:right-12 ltr:left-12 text-xs peer-focus:rtl:!right-4 peer-focus:ltr:!left-4 peer-[:not(:placeholder-shown)]:rtl:!right-4 peer-[:not(:placeholder-shown)]:ltr:!left-4 truncate max-w-[120px]">
                            رمز عبور
                        </label>
                        <span class="material-symbols-rounded absolute top-[18px] rtl:right-4 ltr:left-4 text-[20px] text-[var(--md-sys-color-on-surface-variant)] transition-colors duration-200 peer-focus:text-[var(--md-sys-color-primary)] pointer-events-none">
                            key
                        </span>
                        <button type="button"
                                @click="showPassword = !showPassword"
                                class="absolute top-1/2 -translate-y-1/2 rtl:left-1 ltr:right-1 p-2 rounded-full hover:bg-[var(--md-sys-color-on-surface)]/10 text-[var(--md-sys-color-on-surface-variant)] transition-all active:scale-95 outline-none"
                                tabindex="-1">
                            <span class="material-symbols-rounded text-[18px] block" x-text="showPassword ? 'visibility_off' : 'visibility'"></span>
                        </button>
                    </div>
                </div>

                <div class="md3-input-group group relative">
                    <div class="relative transition-transform duration-200 focus-within:scale-[1.01]">
                        <input
                            wire:model.lazy="password_confirmation"
                            :type="showConfirm ? 'text' : 'password'"
                            id="password_confirmation"
                            class="md3-input peer ps-12 pe-10 py-3.5 text-base bg-[var(--md-sys-color-surface-container)]/50 focus:bg-[var(--md-sys-color-surface-container)]"
                            placeholder=" "
                            required
                            autocomplete="new-password"
                        />
                        <label for="password_confirmation" class="md3-label rtl:right-12 ltr:left-12 text-xs peer-focus:rtl:!right-4 peer-focus:ltr:!left-4 peer-[:not(:placeholder-shown)]:rtl:!right-4 peer-[:not(:placeholder-shown)]:ltr:!left-4 truncate max-w-[120px]">
                            تکرار رمز
                        </label>
                        <span class="material-symbols-rounded absolute top-[18px] rtl:right-4 ltr:left-4 text-[20px] text-[var(--md-sys-color-on-surface-variant)] transition-colors duration-200 peer-focus:text-[var(--md-sys-color-primary)] pointer-events-none">
                            lock_reset
                        </span>
                         <button type="button"
                                @click="showConfirm = !showConfirm"
                                class="absolute top-1/2 -translate-y-1/2 rtl:left-1 ltr:right-1 p-2 rounded-full hover:bg-[var(--md-sys-color-on-surface)]/10 text-[var(--md-sys-color-on-surface-variant)] transition-all active:scale-95 outline-none"
                                tabindex="-1">
                            <span class="material-symbols-rounded text-[18px] block" x-text="showConfirm ? 'visibility_off' : 'visibility'"></span>
                        </button>
                    </div>
                </div>
            </div>

             <div class="flex gap-1 -mt-2 h-1 px-1 opacity-0 transition-opacity duration-300" :class="{ 'opacity-100': strength > 0 }">
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

            <div class="space-y-4 pt-4">
                <button type="submit"
                        class="md3-btn w-full h-12 text-base shadow-lg shadow-[var(--md-sys-color-primary)]/15 active:shadow-none transition-all group hover:scale-[1.01]"
                        wire:loading.attr="disabled"
                        wire:target="register">
                    <div class="relative flex items-center justify-center gap-2 w-full">
                         <div class="absolute inset-0 bg-white/20 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700 ease-in-out skew-x-12 opacity-50"></div>

                        <span class="material-symbols-rounded text-[22px]" wire:loading.remove wire:target="register">person_add</span>

                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" wire:loading wire:target="register">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <span wire:loading.remove wire:target="register" class="font-bold tracking-wide">ثبت نام و ایجاد حساب</span>
                        <span wire:loading wire:target="register" class="font-bold tracking-wide">در حال ایجاد...</span>
                    </div>
                </button>

                <div class="relative flex py-1 items-center">
                    <div class="flex-grow border-t border-[var(--md-sys-color-outline-variant)]/30"></div>
                    <span class="flex-shrink-0 mx-4 text-[10px] font-bold text-[var(--md-sys-color-outline)] uppercase tracking-[0.2em] opacity-70">حساب دارید؟</span>
                    <div class="flex-grow border-t border-[var(--md-sys-color-outline-variant)]/30"></div>
                </div>

                <a href="{{ route('login') }}"
                   class="md3-btn-outlined w-full h-12 text-base border-2 border-[var(--md-sys-color-outline-variant)]/50 text-[var(--md-sys-color-on-surface-variant)] transition-all hover:border-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary)]/5">
                    <span class="material-symbols-rounded text-[20px]">login</span>
                    <span class="font-bold">ورود به سیستم</span>
                </a>
            </div>
        </form>
    </div>
</x-auth-card>
