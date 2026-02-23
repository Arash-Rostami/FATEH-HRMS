<div class="w-full max-w-7xl mx-auto p-4 md:p-6 space-y-6 md:space-y-8 text-[var(--md-sys-color-on-surface)]" dir="rtl">

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-2">
        <div class="p-3 rounded-2xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
            <span class="material-symbols-rounded text-3xl">menu_book</span>
        </div>
        <div>
            <h1 class="text-2xl md:text-3xl font-bold tracking-tight">راهنمای اپ HRMS</h1>
            <p class="text-sm md:text-base opacity-70 mt-1">
                مرکز جامع اطلاعات و دسترسی سریع به ابزارهای کلیدی
            </p>
        </div>
    </div>

    {{-- Quick Access Tools --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
        @foreach($this->tools as $tool)
            <button
                type="button"
                wire:click="$dispatch('switch-tab', { tab: '{{ $tool['action'] }}' })"
                class="group relative overflow-hidden rounded-2xl p-4 md:p-6 text-right transition-all duration-300 hover:shadow-lg active:scale-[0.98] border border-transparent hover:border-[var(--md-sys-color-outline-variant)]"
                style="background-color: {{ $tool['bg'] }}; color: {{ $tool['text'] }};">

                <div class="flex justify-between items-start mb-3">
                    <span class="material-symbols-rounded text-3xl md:text-4xl transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-6">
                        {{ $tool['icon'] }}
                    </span>
                    <span class="material-symbols-rounded text-lg opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300">
                        arrow_back
                    </span>
                </div>

                <h3 class="text-base md:text-lg font-bold">
                    {{ $tool['title'] }}
                </h3>
            </button>
        @endforeach
    </div>

    {{-- Interface Guide --}}
    <div class="relative overflow-hidden rounded-3xl bg-[var(--md-sys-color-surface-container-high)] border border-[var(--md-sys-color-outline-variant)] p-6 md:p-8">
        <div class="absolute top-0 right-0 w-32 h-32 bg-[var(--md-sys-color-primary)] opacity-5 rounded-full blur-3xl -mr-16 -mt-16"></div>
        <div class="absolute bottom-0 left-0 w-32 h-32 bg-[var(--md-sys-color-tertiary)] opacity-5 rounded-full blur-3xl -ml-16 -mb-16"></div>

        <div class="relative z-10 flex flex-col md:flex-row gap-6 items-start">
            <div class="shrink-0 p-3 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                <span class="material-symbols-rounded text-3xl">info</span>
            </div>
            <div class="space-y-3">
                <h3 class="text-xl font-bold text-[var(--md-sys-color-on-surface)]">
                    راهنمای رابط کاربری
                </h3>
                <p class="text-base leading-relaxed opacity-80 text-justify">
                    نوار کناری راست در حالت دسکتاپ و نوار پیمایش پایین، دسترسی سریع به ابزارهای ضروری روزمره‌ای را فراهم می‌کنند که بیشترین استفاده را در اپلیکیشن خواهید داشت. نوار کناری چپ برای بارگذاری ابزارهای کاربردی و قابلیت‌های اصلی پرتکرار طراحی شده است. نوار پیمایش بالا نیز دسترسی راحت به ابزارهای کم‌کاربرد و تنظیمات را در یک مکان مشخص فراهم می‌کند. این نوار میان‌برهایی برای تنظیمات وضعیت و دسترسی، حالت نمایش، تم و رنگ‌بندی، افکت‌های پس‌زمینه، بازنشانی حافظه و پالت دستورات جهت یافتن سریع ابزارهای مورد نظر به زبان فارسی یا انگلیسی در اختیار شما قرار می‌دهد.
                </p>
            </div>
        </div>
    </div>

    {{-- Modules Accordion --}}
    <div class="space-y-3" x-data="{ active: null }">
        <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
            <span class="material-symbols-rounded text-[var(--md-sys-color-primary)]">layers</span>
            ماژول‌های سیستم
        </h2>

        @foreach($this->modules as $index => $module)
            <div class="rounded-2xl bg-[var(--md-sys-color-surface-container)] border border-[var(--md-sys-color-outline-variant)] overflow-hidden transition-all duration-300"
                 :class="{ 'ring-2 ring-[var(--md-sys-color-primary)] ring-opacity-50': active === {{ $index }} }">

                <button
                    @click="active = (active === {{ $index }} ? null : {{ $index }})"
                    class="w-full flex items-center justify-between p-4 md:p-5 text-right transition-colors hover:bg-[var(--md-sys-color-surface-container-high)] focus:outline-none">

                    <div class="flex items-center gap-4">
                        <div class="p-2 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-2xl">
                                {{ $module['icon'] ?? 'extension' }}
                            </span>
                        </div>
                        <span class="text-lg font-semibold text-[var(--md-sys-color-on-surface)]">
                            {{ $module['title'] }}
                        </span>
                    </div>

                    <span class="material-symbols-rounded text-2xl transition-transform duration-300 text-[var(--md-sys-color-outline)]"
                          :class="{ 'rotate-180 text-[var(--md-sys-color-primary)]': active === {{ $index }} }">
                        expand_more
                    </span>
                </button>

                <div x-show="active === {{ $index }}"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="border-t border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-low)]">
                    <div class="p-5 md:p-6 text-base leading-loose opacity-80 text-justify">
                        {{ $module['content'] }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Footer --}}
    <div class="pt-8 pb-4 text-center opacity-60 text-sm">
        <p>
            سیستم مدیریت منابع انسانی (HRMS) • نسخه 2026
        </p>
        <p class="mt-1">
            طراحی و توسعه توسط واحد فناوری اطلاعات
        </p>
    </div>

</div>
