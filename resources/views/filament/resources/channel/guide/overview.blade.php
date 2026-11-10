<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«گروه» یک گفتگوی موضوعی گروهی است که چند کاربر در آن پیام رد و بدل می‌کنند</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        کاربران در پنل خود گروه می‌سازند و مدیریت می‌کنند. در این صفحه می‌توانید گروه‌های سازمان، اعضا و پیام‌ها را ببینید، گروه‌های نامناسب را موقتاً حذف کنید و خروجی اکسل بگیرید. ساخت گروه از این صفحه ممکن نیست؛ کاربر سازنده مالک گروه می‌شود.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">chat</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">هر ردیف چیست؟</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-[22px]">forum</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">یک گروه فعال</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">هر گروه نام، کد، مالک و نوع عمومی یا خصوصی دارد. تعداد اعضا و پیام‌ها در جدول دیده می‌شود. مالک خودکار عضو گروه است و نمی‌تواند از آن خارج شود.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-[22px]">auto_delete</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">یک گروه حذف‌شدهٔ نرم</p>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]">
                            <span class="material-symbols-rounded text-[12px]">history</span> deleted_at
                        </span>
                    </div>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">با حذف موقت، اطلاعات گروه باقی می‌ماند. این صفحه گروه‌های حذف‌شده را نیز نشان می‌دهد و می‌توانید آن‌ها را بازگردانید. پس از {{ convertToPersian('30') }} روز، گروه خودکار و برای همیشه حذف می‌شود.</p>
                </div>
            </div>
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                با فیلتر «در آستانهٔ حذف خودکار» می‌توانید گروه‌هایی که موعد حذف دائمی‌شان ({{ convertToPersian('30') }} روز پس از حذف موقت) فرا رسیده را ببینید و در صورت نیاز بازگردانید.
            </p>
        </div>
    </div>
</div>