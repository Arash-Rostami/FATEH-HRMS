<div dir="rtl"
     x-data="dms"
     class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto" style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;">
    <div class="max-w-[88rem] mx-auto page-wrapper relative">

        <!-- Header -->
        <div class="pb-6 shrink-0 border-b border-[var(--md-sys-color-outline-variant)]/30 mb-6">
            <x-dashboard.tab.title icon="folder_open" title="مستندات" :count="$this->totalDocs" countLabel="سند" />
        </div>

        <!-- Filters -->
        <div class="mb-6 z-10 relative">
            @include('livewire.dashboard.dms.partials.filters')
        </div>

        <!-- Modals -->
        <x-dashboard.modal.pdf-viewer />

        <!-- Content Area -->
        <div class="space-y-6 relative bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/30 rounded-2xl p-4 md:p-6 shadow-sm">

            <div wire:loading.delay
                 class="absolute inset-0 bg-[var(--md-sys-color-surface)]/50 z-50 flex items-center justify-center rounded-2xl">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[var(--md-sys-color-primary)]"></div>
            </div>

            <!-- Legend -->
            <div class="flex space-x-4 items-center text-sm font-medium">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-[var(--md-sys-color-error)]"></span>
                    <span class="text-[var(--md-sys-color-error)]">امضا نشده</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-[var(--md-sys-color-outline)]"></span>
                    <span class="text-[var(--md-sys-color-outline)]">خوانده نشده</span>
                </div>
            </div>

            <!-- Warning Alert -->
            @if ($this->docs->whereNotIn('id', $this->confirmedDocs)->isNotEmpty())
                <div class="bg-[var(--md-sys-color-error-container)] border border-[var(--md-sys-color-error)]/30 text-[var(--md-sys-color-on-error-container)] rounded-2xl p-4 shadow-sm flex gap-4 items-start animate-fade-in-up">
                    <div class="shrink-0 mt-1">
                        <span class="material-symbols-rounded text-2xl text-[var(--md-sys-color-error)]">error</span>
                    </div>
                    <div class="text-sm leading-relaxed text-justify">
                        <p class="font-bold mb-1">اقدام مورد نیاز</p>
                        این اسناد توسط واحد برنامه‌ریزی و بهبود سیستم به طور رسمی صادر شده‌اند. با کلیک بر روی
                        "مشاهده سند"، تأیید می‌کنید که محتوای آن‌ها را مطالعه کرده و از آن مطلع شدید.
                        این اقدام به عنوان امضای دیجیتال، تأیید آگاهی شما از اطلاعات ارائه شده تلقی می‌شود.
                    </div>
                </div>
            @endif

            <!-- Table -->
            @include('livewire.dashboard.dms.partials.table')

            @if($this->docs->hasMorePages())
                <div class="flex justify-center py-6 pb-2">
                    <button wire:click="loadMore"
                            class="group flex items-center gap-2 text-sm font-medium text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-surface)] px-5 py-2.5 rounded-xl border border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:border-[var(--md-sys-color-primary)] transition-all shadow-sm hover:shadow-md">
                        <span>بارگذاری بیشتر</span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                             stroke="currentColor" class="w-4 h-4 group-hover:translate-y-0.5 transition-transform">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </button>
                </div>
            @endif

        </div>
    </div>
</div>
