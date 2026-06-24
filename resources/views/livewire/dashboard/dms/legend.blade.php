<div wire:loading.delay
     class="absolute inset-0 z-50 flex items-center justify-center rounded-2xl bg-[var(--md-sys-color-surface)]/55 backdrop-blur-[1px]">
    <div class="h-9 w-9 animate-spin rounded-full border-2 border-[var(--md-sys-color-primary)]/20 border-b-[var(--md-sys-color-primary)]"></div>
</div>

@if ($this->receivePendingCount > 0 || $this->readPendingCount > 0)
    <div class="relative z-20 flex gap-3 rounded-2xl border border-[var(--md-sys-color-error)]/20 bg-[var(--md-sys-color-error)]/10 p-4 shadow-sm animate-fade-in-up">
        <div class="mt-0.5 shrink-0">
            <span class="material-symbols-rounded text-2xl text-[var(--md-sys-color-error)]">error</span>
        </div>

        <div class="min-w-0 flex-1 text-sm leading-7 text-justify text-[var(--md-sys-color-on-error-container)]">
            <p class="mb-1.5 font-bold">اقدام مورد نیاز</p>
            <p>
                در کارتابل شما،
                @if($this->receivePendingCount > 0)
                    <span class="font-bold">{{ convertToPersian($this->receivePendingCount) }}</span> سند نیاز به تایید دریافت
                @endif
                @if($this->receivePendingCount > 0 && $this->readPendingCount > 0)
                    و
                @endif
                @if($this->readPendingCount > 0)
                    <span class="font-bold">{{ convertToPersian($this->readPendingCount) }}</span> سند نیاز به تایید مطالعه
                @endif
                دارند؛ لطفاً اقدام بفرمایید. این اقدام به عنوان امضای دیجیتال، تأیید آگاهی شما از اطلاعات ارائه شده تلقی می‌شود.
            </p>
        </div>
    </div>
@endif

<div class="pr-6 py-3 w-1/2">
    <div title="راهنمای وضعیت سند"
         class="inline-flex w-full flex-wrap items-center gap-3 rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] px-4 py-3 text-sm font-medium text-[var(--md-sys-color-on-surface)]">

        <div class="flex items-center gap-2.5 group">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[var(--md-sys-color-error)] text-white shadow-md ring-1 ring-white/30 transition-transform group-hover:scale-105"
                 title="نیازمند تایید دریافت">
                <span class="material-symbols-rounded text-[16px]">edit_document</span>
            </div>
            <span class="whitespace-nowrap">نیازمند تایید دریافت</span>
        </div>

        <div class="h-6 w-px bg-[var(--md-sys-color-outline-variant)]/70"></div>

        <div class="flex items-center gap-2.5 group">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[var(--md-sys-color-tertiary)] text-white shadow-md ring-1 ring-white/30 transition-transform group-hover:scale-105"
                 title="نیازمند تایید مطالعه">
                <span class="material-symbols-rounded text-[16px]">menu_book</span>
            </div>
            <span class="whitespace-nowrap">نیازمند تایید مطالعه</span>
        </div>

        <div class="h-6 w-px bg-[var(--md-sys-color-outline-variant)]/70"></div>

        <div class="flex items-center gap-2.5 group">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[var(--md-sys-color-primary)] text-white shadow-md ring-1 ring-white/30 transition-transform group-hover:scale-105"
                 title="مطالعه شده">
                <span class="material-symbols-rounded text-[16px]">check_circle</span>
            </div>
            <span class="whitespace-nowrap">مطالعه شده</span>
        </div>
        <div class="h-6 w-px bg-[var(--md-sys-color-outline-variant)]/70"></div>

        <button @click="toggleMaximize()"
                :title="max ? 'کوچک کردن' : 'بزرگ کردن'"
                class="flex size-9 items-center justify-center rounded-xl hover:bg-[var(--md-sys-color-surface-variant)] transition-colors text-[var(--md-sys-color-on-surface-variant)]">
            <span class="material-symbols-rounded text-[20px]" x-text="max ? 'close_fullscreen' : 'open_in_full'"></span>
        </button>

    </div>
</div>
