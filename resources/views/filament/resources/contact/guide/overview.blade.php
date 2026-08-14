<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«پیام‌رسان داخلی» گفتگوهای خصوصی یک‌به‌یک بین کاربران است</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        هر ردیف در این جدول یک پیام مستقیم بین دو همکار است — یک <strong class="font-bold text-[var(--md-sys-color-on-surface)]">فرستنده</strong> و یک <strong class="font-bold text-[var(--md-sys-color-on-surface)]">گیرنده</strong>. پیام‌ها از صفحهٔ <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">/contact</code> در پنل کاربری ساخته می‌شوند؛ در این صفحهٔ مدیریت امکان ساخت پیام وجود ندارد (<code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] font-mono text-[11px] border border-[var(--md-sys-color-outline-variant)]/50">canCreate = false</code>). شما صرفاً نظارت دارید: کل پیام‌های سازمان را می‌بینید، بدنه را ویرایش می‌کنید، حذف نرم/بازیابی می‌کنید و خروجی اکسل می‌گیرید.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">forum</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">هر ردیف چیست؟</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-[22px]">person</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">طرفین گفتگو</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">ستون «فرستنده» و «گیرنده» هر ردیف را پر می‌کنند — هر دو به یک کاربر اشاره می‌کنند. برخلاف کانال‌ها که یک‌به‌多く است، پیام‌رسان همیشه یک‌به‌یک است.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-[22px]">reply</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">زنجیرهٔ پاسخ</p>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)]">
                            <span class="material-symbols-rounded text-[12px]">link</span> reply_to_id
                        </span>
                    </div>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">یک پیام می‌تواند پاسخ به پیامِ دیگری باشد؛ فیلد <code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">reply_to_id</code> به پیامِ مادر اشاره می‌کند. ستون «پاسخ» در جدول نشان می‌دهد آیا این ردیف پاسخ است یا پیام اصلی. روی صفحهٔ ویرایش، مدیریت ارتباط «پاسخ‌ها» (RepliesRelationManager) زیرِ صفحه ظاهر می‌شود و تمام پیام‌هایی که به این پیام پاسخ داده‌اند را فهرست می‌کند.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                <div class="shrink-0 mt-0.5">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                        <span class="material-symbols-rounded text-[22px]">attach_file</span>
                    </span>
                </div>
                <div class="flex-1 flex flex-col gap-1.5">
                    <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">پیوست‌ها و وضعیت خوانده‌شدن</p>
                    <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">هر پیام می‌تواند چند فایل پیوست داشته باشد (شمارش در ستون «پیوست‌ها») و یک زمان خوانده‌شدن (<code class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] font-mono text-[11px]">read_at</code>) — تا وقتی گیرنده پیام را باز نکرده، «خوانده‌نشده» می‌ماند.</p>
                </div>
            </div>
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                کوئریِ این صفحه <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">withoutGlobalScopes([SoftDeletingScope])</code> می‌زند — یعنی پیام‌های حذف‌شدهٔ نرم را هم می‌بینید، در حالی که کاربر آن‌ها را در پنل خودش نمی‌بیند.
            </p>
        </div>
    </div>
</div>