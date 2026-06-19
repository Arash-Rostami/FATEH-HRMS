<div wire:loading.delay
     class="absolute inset-0 bg-[var(--md-sys-color-surface)]/50 z-50 flex items-center justify-center rounded-2xl">
    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[var(--md-sys-color-primary)]"></div>
</div>

@if ($this->receivePendingCount > 0 || $this->readPendingCount > 0)
    <div class="relative z-20 bg-[var(--md-sys-color-error)]/10 border border-[var(--md-sys-color-error)]/30 text-[var(--md-sys-color-on-error-container)] rounded-2xl p-4 shadow-sm flex gap-4 items-start animate-fade-in-up">
        <div class="shrink-0 mt-1">
            <span class="material-symbols-rounded text-2xl text-[var(--md-sys-color-error)]">error</span>
        </div>
        <div class="text-sm leading-relaxed text-justify">
            <p class="font-bold mb-1">اقدام مورد نیاز</p>
            در کارتابل شما،
            @if($this->receivePendingCount > 0)
                <span class="font-bold">{{ convertToPersian($this->receivePendingCount) }}</span> سند نیاز به تایید دریافت
            @endif
            @if($this->receivePendingCount > 0 && $this->readPendingCount > 0) و @endif
            @if($this->readPendingCount > 0)
                <span class="font-bold">{{ convertToPersian($this->readPendingCount) }}</span> سند نیاز به تایید مطالعه
            @endif
            دارند؛ لطفاً اقدام بفرمایید. این اقدام به عنوان امضای دیجیتال، تأیید آگاهی شما از اطلاعات ارائه شده تلقی می شود.
        </div>
    </div>
@endif

<div class="px-5 py-3 inline-flex flex-col gap-3.5">
    <div title="راهنمای وضعیت سند"
         class="flex items-center gap-6 text-sm font-medium text-[var(--md-sys-color-on-surface)] cursor-help">

        <!-- Needs Receive Confirmation -->
        <div class="flex items-center gap-2 group">
            <div class="relative flex items-center justify-center w-6 h-6 rounded-md
                        bg-[var(--md-sys-color-error)]
                        text-white
                        shadow-md border border-white/30
                        group-hover:scale-105 transition-transform"
                 title="نیازمند تایید دریافت">
                <span class="material-symbols-rounded text-[16px]">edit_document</span>
            </div>
            <span>نیازمند تایید دریافت</span>
        </div>

        <!-- Needs Read Confirmation -->
        <div class="flex items-center gap-2 group">
            <div class="relative flex items-center justify-center w-6 h-6 rounded-md
                        bg-[var(--md-sys-color-tertiary-container)]
                        text-[var(--md-sys-color-on-tertiary-container)]
                        shadow-md border border-white/30
                        group-hover:scale-105 transition-transform"
                 title="نیازمند تایید مطالعه">
                <span class="material-symbols-rounded text-[16px]">menu_book</span>
            </div>
            <span>نیازمند تایید مطالعه</span>
        </div>

        <!-- Confirmed & Read -->
        <div class="flex items-center gap-2 group">
            <div class="relative flex items-center justify-center w-6 h-6 rounded-md
                        bg-[var(--md-sys-color-primary-container)]
                        text-[var(--md-sys-color-on-primary-container)]
                        shadow-md border border-white/30
                        group-hover:scale-105 transition-transform"
                 title="مطالعه شده">
                <span class="material-symbols-rounded text-[16px]">check_circle</span>
            </div>
            <span>مطالعه شده</span>
        </div>

    </div>
</div>
