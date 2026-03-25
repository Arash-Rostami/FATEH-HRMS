<div
    x-data="dms"
    class="animate-fade relative w-full max-w-[88rem] mx-auto h-full flex flex-col overflow-hidden"
    dir="rtl"
>
    <!-- Header -->
    <div class="pb-0 shrink-0">
        <x-dashboard.tab.title icon="folder_open" title="مستندات" :count="$this->totalDocs" countLabel="سند" />
    </div>

    <!-- Filters -->
    <div class="shrink-0 px-4 md:px-8 pt-4 pb-2 z-10">
        @include('livewire.dashboard.dms.partials.filters')
    </div>

    <!-- Modals -->
    <x-dashboard.modal.pdf-viewer />

    <!-- Content Area -->
    <div class="flex-1 overflow-y-auto p-4 md:p-8 space-y-4 custom-scrollbar relative bg-[var(--md-sys-color-surface-container-lowest)]">

        <div wire:loading.delay
             class="absolute inset-0 bg-[var(--md-sys-color-surface-container-lowest)]/50 z-50 flex items-center justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[var(--md-sys-color-primary)]"></div>
        </div>

        <!-- Legend -->
        <div class="flex space-x-4 items-center mb-4 text-sm font-medium">
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
            <div class="bg-[var(--md-sys-color-error-container)] border border-[var(--md-sys-color-error)]/30 text-[var(--md-sys-color-on-error-container)] rounded-2xl p-4 shadow-sm mb-6 flex gap-4 items-start animate-fade-in-up">
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
            <div class="flex justify-center py-6 pb-12">
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
