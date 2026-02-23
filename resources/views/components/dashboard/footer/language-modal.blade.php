<div
    x-data="{ show: false }"
    @open-language-modal.window="show = true"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-[150] flex items-center justify-center px-4"
    style="display: none;"
>
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="show = false"></div>

    <!-- Modal -->
    <div class="relative w-full max-w-sm bg-[var(--md-sys-color-surface)] rounded-2xl shadow-xl border border-[var(--md-sys-color-outline-variant)] overflow-hidden z-[151]">

        <div class="p-6 text-center space-y-4">
            <div class="w-12 h-12 mx-auto rounded-full bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center">
                <span class="material-symbols-rounded text-2xl">translate</span>
            </div>

            <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)]">
                تغییر زبان برنامه
            </h3>

            <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] leading-relaxed">
                آیا مایل به فعال‌سازی مترجم گوگل هستید؟ این قابلیت ممکن است ظاهر برخی بخش‌ها را تغییر دهد.
            </p>

            <div class="grid grid-cols-2 gap-3 pt-2">
                <button
                    @click="show = false"
                    class="px-4 py-2 rounded-lg text-sm font-medium text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] transition-colors"
                >
                    انصراف
                </button>
                <button
                    @click="window.GoogleTranslate.toggle(); show = false;"
                    class="px-4 py-2 rounded-lg text-sm font-medium bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:shadow-md transition-all"
                >
                    تایید و فعال‌سازی
                </button>
            </div>
        </div>
    </div>
</div>
