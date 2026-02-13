<x-auth-card title="تایید آدرس ایمیل" description="برای دسترسی کامل به سیستم، لطفاً ایمیل خود را تایید کنید.">
    <x-slot:imageContent>
         {{-- Custom Verify Branding --}}
         <div class="relative group-hover:rotate-12 transition-transform duration-700 ease-in-out">
            <div class="absolute inset-0 bg-gradient-to-tr from-[var(--md-sys-color-primary)] to-[var(--md-sys-color-secondary)] opacity-30 blur-2xl rounded-full"></div>
            <div class="w-32 h-32 rounded-[2.5rem] bg-gradient-to-br from-white/10 to-white/5 backdrop-blur-2xl border border-white/20 shadow-2xl flex items-center justify-center relative z-10">
                <span class="material-symbols-rounded text-[80px] text-[var(--md-sys-color-on-surface)] drop-shadow-2xl">mark_email_unread</span>
            </div>
        </div>
        <div class="space-y-4 text-center relative z-10">
            <h2 class="text-3xl font-bold text-[var(--md-sys-color-on-surface)] tracking-tight drop-shadow-sm">Verification</h2>
            <p class="text-[var(--md-sys-color-on-surface-variant)] text-lg max-w-[250px] mx-auto leading-relaxed font-medium">
                تایید هویت الکترونیکی<br>ارسال لینک فعال‌سازی
            </p>
        </div>
    </x-slot:imageContent>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-6 p-4 rounded-xl bg-[var(--md-sys-color-primary-container)]/30 border border-[var(--md-sys-color-primary)]/20 text-[var(--md-sys-color-on-primary-container)] text-sm flex items-start gap-3 shadow-sm backdrop-blur-sm animate-slide-up">
            <span class="material-symbols-rounded text-[20px] mt-0.5">send</span>
            <div class="leading-relaxed font-medium">
                لینک تایید جدید به آدرس ایمیل شما ارسال گردید.
            </div>
        </div>
    @endif

    <div class="space-y-4 mt-8">
        <button wire:click="sendVerification"
                class="md3-btn w-full h-12 text-base shadow-lg shadow-[var(--md-sys-color-primary)]/15 active:shadow-none transition-all group hover:scale-[1.01]"
                wire:loading.attr="disabled"
                wire:target="sendVerification">
            <div class="relative flex items-center justify-center gap-2 w-full">
                <span class="material-symbols-rounded text-[22px] transition-transform group-hover:scale-110" wire:loading.remove wire:target="sendVerification">forward_to_inbox</span>

                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" wire:loading wire:target="sendVerification">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>

                <span wire:loading.remove wire:target="sendVerification" class="font-bold tracking-wide">ارسال مجدد لینک تایید</span>
                <span wire:loading wire:target="sendVerification" class="font-bold tracking-wide">در حال ارسال...</span>
            </div>
        </button>

        <div class="relative flex py-1 items-center">
            <div class="flex-grow border-t border-[var(--md-sys-color-outline-variant)]/30"></div>
            <span class="flex-shrink-0 mx-4 text-[10px] font-bold text-[var(--md-sys-color-outline)] uppercase tracking-[0.2em] opacity-70">گزینه‌ها</span>
            <div class="flex-grow border-t border-[var(--md-sys-color-outline-variant)]/30"></div>
        </div>

        <button wire:click="logout"
                class="md3-btn-outlined w-full h-12 text-base border-2 border-[var(--md-sys-color-error)]/30 text-[var(--md-sys-color-error)] hover:bg-[var(--md-sys-color-error)]/5 hover:border-[var(--md-sys-color-error)] transition-all duration-300">
            <span class="material-symbols-rounded text-[20px] rtl:rotate-180">logout</span>
            <span class="font-bold">خروج از حساب کاربری</span>
        </button>
    </div>

    <div class="mt-8 pt-4 border-t border-[var(--md-sys-color-outline-variant)]/20">
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] opacity-80">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">mark_email_read</span>
                <span>اگر ایمیلی دریافت نکردید، پوشه Spam را بررسی کنید.</span>
            </div>
        </div>
    </div>
</x-auth-card>
