<div wire:loading.delay
     class="absolute inset-0 bg-[var(--md-sys-color-surface)]/50 z-50 flex items-center justify-center rounded-2xl">
    <div
        class="animate-spin rounded-full h-8 w-8 border-b-2 border-[var(--md-sys-color-primary)]"></div>
</div>

@if ($this->docs->whereNotIn('id', $this->confirmedDocs)->isNotEmpty())
    <div
        class="relative z-20 bg-[var(--md-sys-color-error)]/10 border border-[var(--md-sys-color-error)]/30 text-[var(--md-sys-color-on-error-container)] rounded-2xl p-4 shadow-sm flex gap-4 items-start animate-fade-in-up">
        <div class="shrink-0 mt-1">
            <span class="material-symbols-rounded text-2xl text-[var(--md-sys-color-error)]">error</span>
        </div>
        <div class="text-sm leading-relaxed text-justify">
            <p class="font-bold mb-1">اقدام مورد نیاز</p>
            این اسناد توسط سازمان به طور رسمی صادر شده‌اند. با کلیک بر روی
            "مشاهده سند"، تأیید می‌کنید که محتوای آن‌ها را مطالعه کرده و از آن مطلع شدید.
            این اقدام به عنوان امضای دیجیتال، تأیید آگاهی شما از اطلاعات ارائه شده تلقی می‌شود.
        </div>
    </div>
@endif

<div class="px-5 py-3 inline-flex flex-col gap-3.5">
    <div title="راهنمای وضعیت سند"
         class="flex items-center gap-6 text-sm font-medium text-[var(--md-sys-color-on-surface)] cursor-help">
        <div class="flex items-center gap-3 group">
            <div
                class="relative overflow-hidden w-12 h-5 border-l-2 border-l-[var(--md-sys-color-error)] shadow-inner group-hover:scale-105 transition-transform"
                title="نشانگر امضا نشده">
                <div
                    class="absolute inset-0 bg-gradient-to-l from-[var(--md-sys-color-error)]/10 to-transparent"></div>
                <div
                    class="absolute inset-y-0 start-0 w-7 bg-[var(--md-sys-color-error)]/20 rounded-s-md"></div>
            </div>
            <span>امضا نشده</span>
        </div>

        <div class="flex items-center gap-3 group">
            <div
                class="relative overflow-hidden w-12 h-5 border-l-2 border-l-[var(--md-sys-color-outline)] shadow-inner group-hover:scale-105 transition-transform"
                title="نشانگر خوانده نشده">
                <div
                    class="absolute inset-0 bg-gradient-to-l from-[var(--md-sys-color-outline-variant)]/40 to-transparent"></div>
                <div
                    class="absolute inset-y-0 start-0 w-7 bg-[var(--md-sys-color-outline)]/40 rounded-s-md"></div>
            </div>
            <span>خوانده نشده</span>
        </div>
    </div>
</div>
