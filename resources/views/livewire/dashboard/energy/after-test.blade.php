<div class="max-w-md mx-auto px-4">
    <div
        class="rounded-2xl border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface)] shadow-sm overflow-hidden">
        <div class="h-[3px] bg-[var(--md-sys-color-primary)]"
             style="box-shadow: 0 0 12px color-mix(in srgb, var(--md-sys-color-primary) 40%, transparent)"></div>
        <div class="p-8 flex flex-col items-center text-center gap-4">
            <div
                class="w-16 h-16 rounded-2xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center">
                <span class="material-symbols-rounded text-[32px] font-fill">task_alt</span>
            </div>
            <div>
                <h3 class="text-base font-bold mb-1.5">پاسخ‌ها ثبت شد</h3>
                <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] leading-relaxed">
                    پرسشنامه‌ی انرژی این دوره با موفقیت تکمیل شده است.<br>
                    برای مشاهده‌ی نتایج تب <strong>نتایج</strong> را باز کنید.
                </p>
            </div>
            @if($this->cooldownDays !== null)
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] text-xs font-bold"
                     title="دورهٔ خنک‌سازی ۲۵ روزه است">
                    <span class="material-symbols-rounded text-[16px]">hourglass_bottom</span>
                    @if($this->cooldownDays === 0)
                        ثبت دورهٔ بعدی: امروز
                    @else
                        تا ثبت دورهٔ بعدی: {{ convertToPersian((string) $this->cooldownDays) }} روز
                    @endif
                </div>
            @endif
            <button @click="activeTab='chart'"
                    class="flex items-center gap-2 mt-2 px-5 py-2.5 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] text-sm font-bold transition-all hover:brightness-95 active:scale-[0.97]">
                <span class="material-symbols-rounded text-[18px]">bar_chart</span>
                مشاهده‌ی نتایج
            </button>
        </div>
    </div>
</div>
