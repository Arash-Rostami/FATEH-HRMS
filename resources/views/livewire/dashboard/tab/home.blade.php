<div class="animate-fade w-full max-w-[88rem] mx-auto !pt-0 pb-10 text-[var(--md-sys-color-on-surface)]"
     dir="rtl"
     x-data="home('{{ addslashes(shortGreeting()) }}')">

    <x-ui.title icon="home" title="خانه"/>

    {{-- ═══════════════════════ HERO BANNER ═══════════════════════ --}}
    <div class="relative overflow-hidden rounded-2xl mb-4
                bg-[var(--md-sys-color-primary-container)]
                border border-[var(--md-sys-color-outline-variant)]/20 shadow-sm
                shadow-[0_2px_20px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)]">

        <div class="absolute inset-0 pointer-events-none opacity-[0.06]"
             style="background-image: radial-gradient(circle, var(--md-sys-color-primary) 1px, transparent 1px); background-size: 28px 28px;"></div>
        <div class="absolute -left-6 top-1/2 -translate-y-1/2 opacity-[0.07] pointer-events-none select-none">
            <span class="material-symbols-rounded font-fill" style="font-size: 220px; line-height:1;">menu_book</span>
        </div>
        <div class="absolute top-0 right-0 w-64 h-64 rounded-full blur-3xl opacity-30 pointer-events-none"
             style="background: var(--md-sys-color-primary); transform: translate(30%, -30%);"></div>
        <div class="absolute bottom-0 left-1/3 w-48 h-48 rounded-full blur-3xl opacity-20 pointer-events-none"
             style="background: var(--md-sys-color-tertiary); transform: translateY(40%);"></div>

        <div class="relative z-10 px-8 py-10 md:py-14 flex flex-col md:flex-row items-start md:items-center justify-between gap-8">
            <div class="space-y-4">
                <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-[var(--md-sys-color-on-primary-container)] leading-snug"
                    x-text="displayed"></h1>
                <p class="text-sm md:text-base text-[var(--md-sys-color-on-primary-container)]/70 leading-relaxed max-w-md">
                    ✨ ابزارهای کلیدی، ماژول‌ها و راهنمای سیستم همه در یک‌جا
                </p>
                <div class="text-[11px] tracking-[0.2em] inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border transition-all duration-300"
                     :class="online ? 'bg-[var(--md-sys-color-primary)]/15 border-[var(--md-sys-color-primary)]/20' : 'bg-red-500/15 border-red-500/30'">
                    <span>میز کار</span>
                    <span class="opacity-50 mx-0.5">|</span>
                    <span class="material-symbols-rounded text-[14px]"
                          :class="online ? 'animate-pulse text-[var(--md-sys-color-primary)]' : 'text-red-500'"
                          x-text="connection.icon"></span>
                    <span :class="online ? 'text-[var(--md-sys-color-on-primary-container)]' : 'text-red-500'"
                          x-text="connection.label"></span>
                </div>
            </div>

            {{-- Personalized stat chips --}}
            <div class="flex flex-wrap gap-3 shrink-0">
                @foreach($stats as $stat)
                    @php($hasItems = ($stat['value'] ?? 0) > 0)
                    @php($chipClass = 'group relative flex flex-col items-center justify-center gap-1 px-5 py-3 rounded-2xl
                        bg-[var(--md-sys-color-on-primary-container)]/8 border border-[var(--md-sys-color-on-primary-container)]/12 min-w-[96px]
                        hover:-translate-y-0.5 hover:bg-[var(--md-sys-color-on-primary-container)]/12
                        active:scale-95 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/40')
                    @if($stat['nav']['type'] === 'route')
                        <a href="{{ route($stat['nav']['name']) }}" wire:navigate class="{{ $chipClass }}">
                            @else
                                <button type="button" wire:click='$dispatch("switch-tab", { tab: "{{ $stat['nav']['tab'] }}" })' class="{{ $chipClass }}">
                                    @endif
                                    @if($hasItems)
                                        <span class="absolute top-2 left-2 flex h-2 w-2" title="نیازمند توجه">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[var(--md-sys-color-primary)] opacity-60"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-[var(--md-sys-color-primary)]"></span>
                            </span>
                                    @endif
                                    <span class="material-symbols-rounded text-lg text-[var(--md-sys-color-primary)] transition-transform group-hover:scale-110">{{ $stat['icon'] }}</span>
                                    <span class="text-xl font-bold text-[var(--md-sys-color-on-primary-container)]">{{ $stat['value'] }}</span>
                                    <span class="text-[10px] font-medium text-[var(--md-sys-color-on-primary-container)]/60 tracking-wide whitespace-nowrap">{{ $stat['label'] }}</span>
                                @if($stat['nav']['type'] === 'route')</a>@else</button>@endif
                @endforeach
            </div>
        </div>
    </div>

    {{-- ═══════════════════════ QUICK ACCESS ═══════════════════════ --}}
    <div x-data="shortcut(@js($shortcuts))" x-cloak class="mb-8">

        <div class="group relative rounded-2xl overflow-hidden
                bg-[var(--md-sys-color-surface)]
                border border-[var(--md-sys-color-primary)]/25
                shadow-[0_2px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)]">

            <div class="hidden group-hover:block relative h-[2px] overflow-hidden bg-[var(--md-sys-color-primary-container)]">
                <div class="absolute inset-y-0 w-1/3 bg-[var(--md-sys-color-primary)]/60 animate-shimmer rounded-full"></div>
            </div>

            <div class="p-4">
                <div class="flex items-center gap-3 mb-4">
                    <div class="relative flex items-center justify-center">
                        <span class="absolute w-8 h-8 rounded-xl bg-[var(--md-sys-color-primary)]/20 animate-pulse-ring"></span>
                        <div class="relative w-8 h-8 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center z-10">
                            <span class="material-symbols-rounded text-base font-fill">bolt</span>
                        </div>
                    </div>

                    <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)]">دسترسی سریع</h2>
                    <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]/50"></div>

                    <span x-show="!manual && shortcuts.length"
                          class="hidden sm:inline-flex items-center gap-1 text-[10px] font-bold text-[var(--md-sys-color-on-surface-variant)]/70">
                    <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-primary)] animate-occasion-bounce">auto_awesome</span>
                    هوشمند
                </span>

                    <button type="button" @click="toggleEdit()"
                            class="w-8 h-8 my-4 rounded-xl flex items-center justify-center transition-all duration-200 active:scale-80"
                            :class="editing
                            ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]'
                            : 'bg-[var(--md-sys-color-surface-variant)]/60 text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]'"
                            :title="editing ? 'پایان ویرایش' : 'سفارشی‌سازی'">
                        <span class="material-symbols-rounded text-[18px]" x-text="editing ? 'check' : 'tune'"></span>
                    </button>
                </div>

                <div x-show="!editing">
                    <div x-show="shortcuts.length"
                         class="flex gap-2.5 overflow-x-auto pt-2 pb-3 -mt-2 px-1 -mx-1 scrollbar-thin scrollbar-thumb-[var(--md-sys-color-outline-variant)]/20 scrollbar-track-transparent">
                        <template x-for="item in shortcuts" :key="item.key">
                            <button type="button" @click="open(item)"
                                    class="group/btn relative shrink-0 inline-flex items-center gap-2 px-4 h-12 rounded-2xl
                                       bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/40 shadow-sm
                                       hover:-translate-y-0.5 hover:border-[var(--md-sys-color-primary)]/40
                                       hover:shadow-[0_6px_18px_color-mix(in_srgb,var(--md-sys-color-primary)_18%,transparent)]
                                       active:scale-95 transition-all duration-300
                                       focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/40">
                                <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]" x-text="item.icon"></span>
                                <span class="text-[13px] font-bold text-[var(--md-sys-color-on-surface)] whitespace-nowrap" x-text="item.title"></span>
                            </button>
                        </template>
                    </div>

                    <div x-show="showEmpty"
                         class="flex flex-col sm:flex-row items-center justify-between gap-4 p-5 rounded-2xl text-center sm:text-right
                            bg-[var(--md-sys-color-surface)] border border-dashed border-[var(--md-sys-color-outline-variant)]">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-rounded text-[28px] text-[var(--md-sys-color-primary)]">touch_app</span>
                            <p class="text-sm text-[var(--md-sys-color-on-surface-variant)]">هنوز میان‌بری ندارید — ماژول‌های پرکاربردتان را انتخاب کنید تا اینجا بیایند.</p>
                        </div>
                        <button type="button" @click="toggleEdit()"
                                class="shrink-0 inline-flex items-center gap-2 px-4 h-10 rounded-xl text-sm font-bold
                                   bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:brightness-95 active:scale-95 transition-all">
                            <span class="material-symbols-rounded text-[18px]">add</span> انتخاب ماژول‌ها
                        </button>
                    </div>
                </div>

                <div x-show="editing" x-transition
                     class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-4 space-y-5">

                    <div>
                        <p class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wider mb-2">میان‌برهای شما</p>

                        <template x-if="manual">
                            <div class="flex flex-wrap gap-2">
                                <template x-for="(key, idx) in pinned" :key="key">
                                    <div class="inline-flex items-center gap-1 ps-3 pe-1 h-10 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] border border-[var(--md-sys-color-primary)]/15">
                                        <span class="material-symbols-rounded text-[18px]" x-text="itemByKey(key)?.icon"></span>
                                        <span class="text-[12px] font-bold whitespace-nowrap" x-text="itemByKey(key)?.title"></span>
                                        <button type="button" @click="move(key,-1)" :disabled="idx===0" class="w-6 h-6 rounded-lg flex items-center justify-center disabled:opacity-30 hover:bg-black/5">
                                            <span class="material-symbols-rounded text-[16px]">chevron_right</span>
                                        </button>
                                        <button type="button" @click="move(key,1)" :disabled="idx===pinned.length-1" class="w-6 h-6 rounded-lg flex items-center justify-center disabled:opacity-30 hover:bg-black/5">
                                            <span class="material-symbols-rounded text-[16px]">chevron_left</span>
                                        </button>
                                        <button type="button" @click="unpin(key)" class="w-6 h-6 rounded-lg flex items-center justify-center text-[var(--md-sys-color-error)] hover:bg-[var(--md-sys-color-error)]/10">
                                            <span class="material-symbols-rounded text-[16px]">close</span>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template x-if="!manual">
                            <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]/70 leading-relaxed">
                                در حال حاضر میان‌برها به‌صورت <b>هوشمند</b> بر اساس میزان استفادهٔ شما نمایش داده می‌شوند. برای سفارشی‌سازی دستی، از فهرست زیر انتخاب کنید.
                            </p>
                        </template>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wider">همهٔ ماژول‌ها</p>
                            <button type="button" x-show="manual" @click="resetToSmart()"
                                    class="text-[11px] font-bold text-[var(--md-sys-color-primary)] hover:underline flex items-center gap-1">
                                <span class="material-symbols-rounded text-[14px]">auto_awesome</span> بازگشت به حالت هوشمند
                            </button>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                            <template x-for="item in catalog" :key="item.key">
                                <button type="button" @click="isPinned(item.key) ? unpin(item.key) : pin(item.key)"
                                        class="group flex items-center gap-2 p-3 rounded-xl border text-right transition-all duration-200 active:scale-[0.97]"
                                        :class="isPinned(item.key)
                                        ? 'bg-[var(--md-sys-color-primary-container)] border-[var(--md-sys-color-primary)]/30 text-[var(--md-sys-color-on-primary-container)]'
                                        : 'bg-[var(--md-sys-color-surface-variant)]/30 border-[var(--md-sys-color-outline-variant)]/40 text-[var(--md-sys-color-on-surface)] hover:border-[var(--md-sys-color-primary)]/40'">
                                    <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)] shrink-0" x-text="item.icon"></span>
                                    <span class="text-[12px] font-bold leading-tight flex-1 truncate" x-text="item.title"></span>
                                    <span class="material-symbols-rounded text-[18px] shrink-0"
                                          :class="isPinned(item.key) ? 'text-[var(--md-sys-color-primary)]' : 'text-[var(--md-sys-color-outline)] group-hover:text-[var(--md-sys-color-primary)]'"
                                          x-text="isPinned(item.key) ? 'check_circle' : 'add_circle'"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- ═══════════════════════ INTERFACE GUIDE ═══════════════════════ --}}
    <div class="flex items-center gap-3 mb-4">
        <div class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center">
            <span class="material-symbols-rounded text-base font-fill">info</span>
        </div>
        <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)]">راهنمای رابط کاربری</h2>
        <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]/50"></div>
    </div>

    <div class="relative overflow-hidden rounded-3xl mb-8
                bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/20 shadow-sm
                shadow-[var(--md-sys-elevation-1)]">
        <div class="absolute top-0 right-0 bottom-0 w-1.5 rounded-r-3xl bg-[var(--md-sys-color-secondary)]"></div>
        <div class="grid md:grid-cols-[auto_1fr] gap-0">
            <div class="flex items-start justify-center p-6 md:p-8 md:border-l border-[var(--md-sys-color-outline-variant)]/50">
                <div class="w-14 h-14 rounded-2xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center shadow-sm">
                    <span class="material-symbols-rounded text-3xl font-fill">map</span>
                </div>
            </div>
            <div class="p-6 md:p-8 md:pr-6">
                <p class="text-sm leading-[2] text-[var(--md-sys-color-on-surface-variant)] text-justify">
                    {{ superClean('نوار کناری راست در حالت دسکتاپ و نوار پیمایش پایین، دسترسی سریع به ابزارهای ضروری روزمره‌ای را فراهم می‌کنند که بیشترین استفاده را در اپلیکیشن خواهید داشت. نوار کناری چپ برای بارگذاری ابزارهای کاربردی و قابلیت‌های اصلی پرتکرار طراحی شده است. نوار پیمایش بالا نیز دسترسی راحت به ابزارهای کم‌کاربرد و تنظیمات را در یک مکان مشخص فراهم می‌کند. این نوار میان‌برهایی برای تنظیمات وضعیت و دسترسی، حالت نمایش، تم و رنگ‌بندی، افکت‌های پس‌زمینه، بازنشانی حافظه و پالت دستورات جهت یافتن سریع ابزارهای مورد نظر به زبان فارسی یا انگلیسی در اختیار شما قرار می‌دهد.', 2000) }}
                </p>
                <div class="flex flex-wrap gap-2 mt-5">
                    @foreach([
                        ['icon' => 'view_sidebar', 'label' => 'نوار کناری راست', 'color' => 'primary'],
                        ['icon' => 'view_sidebar', 'label' => 'نوار کناری چپ', 'color' => 'secondary', 'class' => '-scale-x-100'],
                        ['icon' => 'menu', 'label' => 'نوار بالا', 'color' => 'tertiary'],
                        ['icon' => 'bottom_navigation', 'label' => 'نوار پایین', 'color' => 'secondary'],
                    ] as $nav)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-semibold bg-[var(--md-sys-color-{{ $nav['color'] }}-container)] text-[var(--md-sys-color-on-{{ $nav['color'] }}-container)] border border-[var(--md-sys-color-{{ $nav['color'] }})]/15">
                            <span class="material-symbols-rounded text-[13px] {{ $nav['class'] ?? '' }}">{{ $nav['icon'] }}</span>
                            {{ $nav['label'] }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════ MODULES ACCORDION ═══════════════════════ --}}
    <div class="flex items-center gap-3 mb-4">
        <div class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] flex items-center justify-center">
            <span class="material-symbols-rounded text-base font-fill">layers</span>
        </div>
        <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)]">ماژول‌های سیستم</h2>
        <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]/50"></div>
        <span class="text-[11px] font-bold px-2.5 py-1 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
            {{ count($this->modules) }} ماژول
        </span>
    </div>

    <div class="space-y-2 mb-8" x-data="{ active: null }">
        @foreach($this->modules as $index => $module)
            <div class="rounded-2xl overflow-hidden border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] transition-all duration-300"
                 :class="active === {{ $index }}
                    ? 'shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] border-[var(--md-sys-color-primary)]/30'
                    : 'hover:border-[var(--md-sys-color-outline-variant)]'">
                <button @click="active = (active === {{ $index }} ? null : {{ $index }})"
                        class="w-full flex items-center justify-between p-4 md:p-5 text-right transition-colors duration-200 focus:outline-none hover:bg-[var(--md-sys-color-surface-variant)]/30">
                    <div class="flex items-center gap-4">
                        <div class="relative flex-shrink-0">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-300"
                                 :class="active === {{ $index }}
                                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] scale-110'
                                    : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]'">
                                <span class="material-symbols-rounded text-xl">{{ $module['icon'] ?? 'extension' }}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-bold text-[var(--md-sys-color-on-surface)] block">{{ $module['title'] }}</span>
                            <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5 block" x-show="active !== {{ $index }}">کلیک کنید برای اطلاعات بیشتر</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="hidden sm:flex text-[10px] font-bold px-2 py-0.5 rounded-lg transition-all duration-200"
                              :class="active === {{ $index }}
                                ? 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]'
                                : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]'">
                            {{ sprintf('%02d', $index + 1) }}
                        </span>
                        <span class="material-symbols-rounded text-2xl transition-all duration-300"
                              :class="active === {{ $index }} ? 'rotate-180 text-[var(--md-sys-color-primary)]' : 'text-[var(--md-sys-color-outline)]'">expand_more</span>
                    </div>
                </button>
                <div x-show="active === {{ $index }}"
                     x-transition:enter="transition ease-out duration-250"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                    <div class="mx-4 mb-4 p-5 rounded-xl bg-[var(--md-sys-color-surface-variant)]/30 border border-[var(--md-sys-color-primary)]/10 border-r-2 border-r-[var(--md-sys-color-primary)]">
                        <p class="text-sm leading-[2] text-[var(--md-sys-color-on-surface-variant)] text-justify">{{ $module['content'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
