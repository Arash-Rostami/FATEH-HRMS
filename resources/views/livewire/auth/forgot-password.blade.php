<x-auth-card title="بازیابی رمز عبور" description="جهت دریافت لینک تغییر رمز، ایمیل سازمانی خود را وارد نمایید.">
    <x-slot:imageContent>
        {{-- Custom Forgot Password Branding --}}
         <div class="relative group-hover:scale-105 transition-transform duration-700 ease-in-out">
            <div class="absolute inset-0 bg-gradient-to-tr from-[var(--md-sys-color-secondary)] to-[var(--md-sys-color-tertiary)] opacity-30 blur-2xl rounded-full"></div>
            <div class="w-32 h-32 rounded-[2.5rem] bg-gradient-to-br from-white/10 to-white/5 backdrop-blur-2xl border border-white/20 shadow-2xl flex items-center justify-center relative z-10">
                <span class="material-symbols-rounded text-[80px] text-[var(--md-sys-color-on-surface)] drop-shadow-2xl">lock_reset</span>
            </div>
        </div>
        <div class="space-y-4 text-center relative z-10">
            <h2 class="text-3xl font-bold text-[var(--md-sys-color-on-surface)] tracking-tight drop-shadow-sm">Account Recovery</h2>
            <p class="text-[var(--md-sys-color-on-surface-variant)] text-lg max-w-[250px] mx-auto leading-relaxed font-medium">
                بازیابی امن دسترسی<br>به پنل کاربری
            </p>
        </div>
    </x-slot:imageContent>

    @if (session('status'))
        <div class="mb-6 p-4 rounded-xl bg-[var(--md-sys-color-primary-container)]/30 border border-[var(--md-sys-color-primary)]/20 text-[var(--md-sys-color-on-primary-container)] text-sm flex items-start gap-3 shadow-sm backdrop-blur-sm animate-slide-up">
            <span class="material-symbols-rounded text-[20px] mt-0.5">check_circle</span>
            <div class="leading-relaxed font-medium">{{ session('status') }}</div>
        </div>
    @endif

    <form wire:submit.prevent="send" class="space-y-6 mt-2">
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

        <div class="space-y-4 pt-6">
            <button type="submit"
                    class="md3-btn w-full h-12 text-base shadow-lg shadow-[var(--md-sys-color-primary)]/15 active:shadow-none transition-all group hover:scale-[1.01]"
                    wire:loading.attr="disabled"
                    wire:target="send">
                <div class="relative flex items-center justify-center gap-2 w-full">
                    <span class="material-symbols-rounded text-[22px]" wire:loading.remove wire:target="send">send</span>

                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" wire:loading wire:target="send">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>

                    <span wire:loading.remove wire:target="send" class="font-bold tracking-wide">ارسال لینک بازیابی</span>
                    <span wire:loading wire:target="send" class="font-bold tracking-wide">در حال پردازش...</span>
                </div>
            </button>

            <div class="relative flex py-1 items-center">
                <div class="flex-grow border-t border-[var(--md-sys-color-outline-variant)]/30"></div>
                <span class="flex-shrink-0 mx-4 text-[10px] font-bold text-[var(--md-sys-color-outline)] uppercase tracking-[0.2em] opacity-70">یا</span>
                <div class="flex-grow border-t border-[var(--md-sys-color-outline-variant)]/30"></div>
            </div>

            <a href="{{ route('login') }}"
               class="md3-btn-outlined w-full h-12 text-base border-2 border-[var(--md-sys-color-outline-variant)]/50 text-[var(--md-sys-color-on-surface-variant)] transition-all hover:border-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary)]/5">
                <span class="material-symbols-rounded text-[20px] rtl:rotate-180">arrow_forward</span>
                <span class="font-bold">بازگشت به صفحه ورود</span>
            </a>
        </div>
    </form>

    <div class="mt-8 text-center opacity-70">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[var(--md-sys-color-surface-variant)]/30 border border-[var(--md-sys-color-outline-variant)]/10">
            <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">info</span>
            <span class="text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">
                لینک ارسالی به مدت ۲۴ ساعت معتبر خواهد بود
            </span>
        </div>
    </div>
</x-auth-card>
