<div class="flex items-center justify-center min-h-[calc(100vh-6rem)] w-full px-4 py-8">
    <div class="glass-panel w-full max-w-[500px] p-10 md:p-12 rounded-[2.5rem] relative overflow-hidden isolate shadow-2xl ring-1 ring-white/10">

        <div class="absolute -top-40 -right-40 w-80 h-80 bg-[var(--md-sys-color-secondary)]/20 rounded-full blur-[100px] pointer-events-none mix-blend-screen opacity-50"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-[var(--md-sys-color-primary)]/20 rounded-full blur-[100px] pointer-events-none mix-blend-screen opacity-50"></div>

        <div class="relative mb-10 text-center flex flex-col items-center gap-6">
            <div class="relative group">
                <div class="absolute inset-0 bg-[var(--md-sys-color-secondary)]/30 rounded-full blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-700"></div>
                <div class="relative w-24 h-24 bg-gradient-to-br from-[var(--md-sys-color-secondary-container)] to-[var(--md-sys-color-surface)] rounded-[2rem] flex items-center justify-center shadow-xl ring-1 ring-white/20">
                    <span class="material-symbols-rounded text-5xl text-[var(--md-sys-color-secondary)] drop-shadow-sm">lock_reset</span>
                </div>
                <div class="absolute -bottom-2 -right-2 w-8 h-8 bg-[var(--md-sys-color-primary)] rounded-full flex items-center justify-center shadow-lg ring-2 ring-[var(--md-sys-color-surface)]">
                    <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-on-primary)]">mail</span>
                </div>
            </div>

            <div class="inline-flex items-center gap-3 px-5 py-2 rounded-full bg-[var(--md-sys-color-surface-variant)]/40 border border-[var(--md-sys-color-outline-variant)]/20 backdrop-blur-xl shadow-lg shadow-black/5 mt-2">
                <span class="relative flex h-2.5 w-2.5">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[var(--md-sys-color-secondary)] opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-[var(--md-sys-color-secondary)]"></span>
                </span>
                <span class="text-[12px] font-bold text-[var(--md-sys-color-on-surface-variant)] tracking-[0.25em] uppercase font-mono">Password Recovery</span>
            </div>

            <div class="space-y-2">
                <h1 class="text-3xl md:text-4xl font-bold text-[var(--md-sys-color-on-surface)] tracking-tight font-display drop-shadow-sm">
                    بازیابی رمز عبور
                </h1>
                <p class="text-base text-[var(--md-sys-color-on-surface-variant)]/80 max-w-xs mx-auto leading-relaxed">
                    ایمیل خود را وارد کنید تا دستورالعمل‌های بازیابی برای شما ارسال شود.
                </p>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-8 p-4 rounded-2xl bg-[var(--md-sys-color-primary-container)]/30 border border-[var(--md-sys-color-primary)]/20 text-[var(--md-sys-color-on-primary-container)] text-sm flex items-start gap-4 shadow-lg backdrop-blur-md animate-slide-up ring-1 ring-[var(--md-sys-color-primary)]/10">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-[var(--md-sys-color-primary)]/20 flex items-center justify-center text-[var(--md-sys-color-primary)]">
                    <span class="material-symbols-rounded text-xl">check</span>
                </div>
                <div class="flex-1 pt-1">
                    <div class="font-bold mb-1">ایمیل ارسال شد</div>
                    <div class="opacity-90 leading-relaxed">{{ session('status') }}</div>
                </div>
            </div>
        @endif

        <form wire:submit.prevent="send" class="space-y-8 relative z-10">
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
                    <span class="material-symbols-rounded absolute top-[22px] rtl:right-4 ltr:left-4 text-[24px] text-[var(--md-sys-color-on-surface-variant)] transition-colors duration-300 peer-focus:text-[var(--md-sys-color-secondary)] pointer-events-none">
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

            <div class="space-y-6 pt-2">
                <button type="submit"
                        class="md3-btn w-full h-14 text-lg shadow-xl shadow-[var(--md-sys-color-primary)]/20 hover:shadow-[var(--md-sys-color-primary)]/40 hover:-translate-y-1 active:translate-y-0 active:shadow-md transition-all duration-300 group overflow-hidden"
                        wire:loading.attr="disabled"
                        wire:target="send">
                    <div class="relative flex items-center justify-center gap-3 w-full z-10">
                        <span class="material-symbols-rounded text-[24px] transition-transform group-hover:rotate-12" wire:loading.remove wire:target="send">send</span>

                        <svg class="animate-spin h-6 w-6 text-white absolute left-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" wire:loading wire:target="send">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <span wire:loading.remove wire:target="send" class="font-bold tracking-wide">ارسال لینک بازیابی</span>
                        <span wire:loading wire:target="send" class="font-bold tracking-wide">در حال پردازش...</span>
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000 ease-in-out"></div>
                </button>

                <div class="relative flex py-2 items-center">
                    <div class="flex-grow border-t-2 border-[var(--md-sys-color-outline-variant)]/20"></div>
                    <span class="flex-shrink-0 mx-6 text-xs font-bold text-[var(--md-sys-color-outline)] uppercase tracking-[0.2em] opacity-70">یا</span>
                    <div class="flex-grow border-t-2 border-[var(--md-sys-color-outline-variant)]/20"></div>
                </div>

                <a href="{{ route('login') }}"
                   class="md3-btn-outlined w-full h-14 text-lg border-2 border-[var(--md-sys-color-outline-variant)] hover:border-[var(--md-sys-color-secondary)] hover:bg-[var(--md-sys-color-secondary)]/5 text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-secondary)] transition-all duration-300">
                    <span class="material-symbols-rounded text-[24px] rtl:rotate-180">arrow_forward</span>
                    <span class="font-bold">بازگشت به ورود</span>
                </a>
            </div>
        </form>

        <div class="mt-8 text-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[var(--md-sys-color-surface-variant)]/30 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/10">
                <span class="material-symbols-rounded text-[16px]">schedule</span>
                <span>لینک ارسالی تا ۲۴ ساعت اعتبار دارد</span>
            </div>
        </div>
    </div>
</div>
