<div
    class="w-full max-w-[88rem] mx-auto !pt-0 text-[var(--md-sys-color-on-surface)] transition-colors duration-300 antialiased selection:bg-[var(--md-sys-color-primary)] selection:text-[var(--md-sys-color-on-primary)]"
    dir="rtl"
    x-data="home('{{ addslashes(shortGreeting(auth()->user()?->casual_name ?? '')) }}')">

    <x-ui.title icon="home" title="خانه"/>

    {{-- ═══════════════════════ HERO DECK (MULTI-PANE) ═══════════════════════ --}}
    <div x-data="heroDeck()"
         x-on:keydown.escape.window="if (maximizedPane) toggleMaximize(maximizedPane); if (pickerFor) pickerFor = null"
         class="relative mb-8 select-none-subtle">

        <div class="relative overflow-hidden rounded-2xl
                    bg-[var(--md-sys-color-primary-container)] transition-all duration-300
                    border border-[var(--md-sys-color-outline-variant)]/10 shadow-sm shadow-[0_4px_30px_color-mix(in_srgb,var(--md-sys-color-primary)_16%,transparent)]">

            <div class="absolute inset-0 pointer-events-none"
                 style="background-image: radial-gradient(circle, var(--md-sys-color-primary) 1.2px, transparent 1.2px); background-size: 24px 24px; opacity: 10%;"></div>
            <div class="absolute inset-0 pointer-events-none opacity-[0.04]"
                 style="background-image: linear-gradient(to right, var(--md-sys-color-on-primary-container) 1px, transparent 1px), linear-gradient(to bottom, var(--md-sys-color-on-primary-container) 1px, transparent 1px); background-size: 96px 96px;"></div>

            <div class="absolute -left-12 top-1/2 -translate-y-1/2 opacity-[0.05] pointer-events-none select-none">
                <span class="material-symbols-rounded font-fill" style="font-size: 280px; line-height: 1;">
                    dashboard_customize
                </span>
            </div>

            <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full pointer-events-none"
                 style="background: var(--md-sys-color-primary); opacity:08%"></div>
            <div class="absolute -bottom-24 left-1/4 w-80 h-80 rounded-full pointer-events-none"
                 style="background: var(--md-sys-color-tertiary);  opacity:05%"></div>

            {{-- Status Bar --}}
            <div x-show="slide === 0 "
                 class="relative z-10 px-5 pt-3.5 pb-2.5 flex items-center justify-between gap-3 border-b border-[var(--md-sys-color-primary-container)] text-xs">
                <div class="flex items-center gap-2 min-w-0">
                    <span
                        class="inline-flex items-center justify-center w-5 h-5 rounded-md bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] font-mono text-[10px] font-bold pt-1 shrink-0">⌘</span>
                    <span
                        class="font-bold text-[var(--md-sys-color-on-primary-container)]/90 truncate">میز کار متمرکز سازمانی</span>
                </div>

                @if($teamPulse->isNotEmpty())
                    @php($visiblePulse = $teamPulse->take(15))
                    @php($mobileRemainder = max(0, $teamPulse->count() - 5))
                    @php($desktopRemainder = max(0, $teamPulse->count() - $visiblePulse->count()))
                    <div wire:key="home-team-pulse" class="shrink-0 flex items-center gap-2 h-7 -my-1 rounded-lg pl-2 pr-1">
                            <span class="flex items-center -space-x-2 rtl:space-x-reverse">
                                @foreach($visiblePulse as $member)
                                    @php($mp = presence($member->presence))
                                    <span class="shrink-0 {{ $loop->iteration > 5 ? 'hidden sm:inline-flex' : 'inline-flex' }}">
                                    <x-ui.hover-popover wire:key="home-team-pulse-{{ $member->id }}" width="w-48">
                                        <x-slot:trigger>
                                            <span class="relative block" title="{{ $member->casual_name }}">
                                                <img src="{{ $member->getProfileImageUrl() ?? $member->getInitialsAvatarUrl() }}"
                                                     alt="{{ $member->name }}" loading="lazy"
                                                     class="w-6 h-6 rounded-md object-cover ring-2 ring-[var(--md-sys-color-primary-container)]">
                                                <span
                                                    class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-{{ $mp->color() }}-500 border-2 border-[var(--md-sys-color-primary-container)]"></span>
                                            </span>
                                        </x-slot:trigger>
                                        <x-slot:body>
                                            <div class="p-3 flex flex-col items-center gap-2 text-center">
                                                <img src="{{ $member->getProfileImageUrl() ?? $member->getInitialsAvatarUrl() }}"
                                                     alt="{{ $member->name }}"
                                                     class="w-10 h-10 rounded-md object-cover ring-2 ring-[var(--md-sys-color-primary-container)]">
                                                <div>
                                                    <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">{{ $member->casual_name }}</p>
                                                    <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5">می‌خواهید گفتگو را شروع کنید؟</p>
                                                </div>
                                                <a href="{{ route('contact', ['open' => $member->id]) }}" wire:navigate
                                                   class="w-full text-center text-[11px] font-bold py-1.5 rounded-lg bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:opacity-90 transition-opacity">
                                                    شروع گفتگو
                                                </a>
                                            </div>
                                        </x-slot:body>
                                    </x-ui.hover-popover>
                                    </span>
                                @endforeach
                                @if($mobileRemainder > 0)
                                    <button type="button" wire:key="home-team-pulse-remainder-mobile"
                                            wire:click="openTeamSearch(5)" aria-label="مشاهده بقیه همکاران آنلاین"
                                            class="sm:hidden relative shrink-0 w-6 h-6 rounded-md bg-[var(--md-sys-color-on-primary-container)]/15 text-[var(--md-sys-color-on-primary-container)] ring-2 ring-[var(--md-sys-color-primary-container)] flex items-center justify-center hover:bg-[var(--md-sys-color-on-primary-container)]/25 transition-colors focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-on-primary-container)]/40">
                                        <span class="text-[9px] font-bold">+{{ convertToPersian($mobileRemainder) }}</span>
                                    </button>
                                @endif
                                @if($desktopRemainder > 0)
                                    <button type="button" wire:key="home-team-pulse-remainder-desktop"
                                            wire:click="openTeamSearch(15)" aria-label="مشاهده بقیه همکاران آنلاین"
                                            class="hidden sm:flex relative shrink-0 w-6 h-6 rounded-md bg-[var(--md-sys-color-on-primary-container)]/15 text-[var(--md-sys-color-on-primary-container)] ring-2 ring-[var(--md-sys-color-primary-container)] items-center justify-center hover:bg-[var(--md-sys-color-on-primary-container)]/25 transition-colors focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-on-primary-container)]/40">
                                        <span class="text-[9px] font-bold">+{{ convertToPersian($desktopRemainder) }}</span>
                                    </button>
                                @endif
                            </span>
                        <button type="button" wire:key="home-team-pulse-status-link"
                                wire:click='$dispatch("switch-tab", { tab: "status" })'
                                aria-label="مشاهده وضعیت همکاران" title="مشاهده وضعیت همکاران"
                                class="text-[10px] font-bold text-[var(--md-sys-color-on-primary-container)]/80 whitespace-nowrap hover:underline focus:outline-none">
                            {{ convertToPersian($teamPulse->count()) }} حاضر
                        </button>
                    </div>
                @endif
            </div>

            <div wire:key="home-team-search" x-data="{ show: false }" x-on:open-team-search.window="show = true">
                <x-ui.modals.panel icon="chat" title="گفتگو با همکاران آنلاین"
                                    subtitle="برای شروع گفتگو، همکار مورد نظر را انتخاب کنید"
                                    max-width="max-w-md sm:max-w-lg" height="h-[75dvh] sm:h-[560px]">
                    <x-slot:search>
                        <x-ui.forms.search name="teamSearch" model="teamSearch" placeholder="جستجوی همکار..."
                                            debounce="300" icon="search" :clearable="true"/>
                    </x-slot:search>

                    <div class="px-4 py-2 flex flex-col gap-1">
                        @forelse($this->remainingTeam->take($teamShowCount) as $member)
                            @php($mp = presence($member->presence))
                            <a wire:key="home-team-search-{{ $member->id }}"
                               href="{{ route('contact', ['open' => $member->id]) }}" wire:navigate
                               class="flex items-center gap-3 p-2 rounded-xl hover:bg-[var(--md-sys-color-surface-variant)] transition-colors">
                                <span class="relative shrink-0">
                                    <img src="{{ $member->getProfileImageUrl() ?? $member->getInitialsAvatarUrl() }}"
                                         alt="{{ $member->name }}" loading="lazy"
                                         class="w-8 h-8 rounded-md object-cover ring-2 ring-[var(--md-sys-color-primary-container)]">
                                    <span
                                        class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-{{ $mp->color() }}-500 border-2 border-[var(--md-sys-color-surface)]"></span>
                                </span>
                                <span class="flex-1 min-w-0">
                                    <span class="block text-sm font-bold text-[var(--md-sys-color-on-surface)] truncate">{{ $member->casual_name }}</span>
                                    <span class="block text-[11px] text-[var(--md-sys-color-on-surface-variant)]">{{ $mp->label() }}</span>
                                </span>
                                <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">chat</span>
                            </a>
                        @empty
                            <div wire:key="home-team-search-empty" class="contents">
                                <x-ui.empty icon="group" title="موردی یافت نشد"/>
                            </div>
                        @endforelse
                    </div>

                    @if($this->remainingTeam->count() > $teamShowCount)
                        <x-slot:footer>
                            <div wire:key="home-team-search-load-more" class="flex justify-center py-3">
                                <x-ui.buttons.load-more
                                    action="loadMoreTeam"
                                    text="بارگذاری بیشتر"
                                    loading-text="در حال دریافت..."
                                    icon="expand_more"
                                    class="font-medium text-[var(--md-sys-color-primary)]
                                           bg-[var(--md-sys-color-surface)]
                                           px-5 py-2.5 rounded-xl
                                           border border-[var(--md-sys-color-outline-variant)]
                                           hover:bg-[var(--md-sys-color-primary-container)]
                                           hover:text-[var(--md-sys-color-on-primary-container)]
                                           hover:border-[var(--md-sys-color-primary)]
                                           shadow-sm hover:shadow-md"
                                />
                            </div>
                        </x-slot:footer>
                    @endif
                </x-ui.modals.panel>
            </div>

            <div
                class="relative z-10 px-6 pt-8 pb-16 sm:px-10 sm:pt-12 sm:pb-20 md:pt-14 md:pb-20 flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-8 transition-opacity duration-300"
                :class="slide === 0 ? 'opacity-100' : 'opacity-0 invisible pointer-events-none'">

                <div class="space-y-6 min-w-0 max-w-xl">
                    <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-[var(--md-sys-color-on-primary-container)] leading-snug break-words"
                        x-text="displayed"></h1>

                    <p class="text-xs sm:text-sm md:text-base text-[var(--md-sys-color-on-primary-container)]/85 leading-relaxed font-normal">
                        ✨ ابزارهای کلیدی، ماژولها و راهنمای سیستم همه در یکجا
                    </p>

                    <div
                        class="text-[11px] tracking-[0.2em] inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border transition-all duration-300"
                        :class="online ? 'bg-[var(--md-sys-color-primary)]/15 border-[var(--md-sys-color-primary)]/20 text-[var(--md-sys-color-on-primary-container)]' : 'bg-red-500/15 border-red-500/30 text-red-500'">
                        <span class="material-symbols-rounded text-[14px]"
                              :class="online ? 'animate-pulse text-[var(--md-sys-color-primary)]' : 'text-red-500'"
                              x-text="connection.icon"></span>
                        <span x-text="connection.label"></span>

                        <span class="opacity-50 mx-0.5">|</span>

                        <span class="material-symbols-rounded text-base text-[var(--md-sys-color-primary)]">sync</span>
                        <span>همگامسازی ابری پیوسته</span>
                    </div>
                </div>

                <div
                    class="grid grid-cols-3 sm:grid-cols-3 gap-2.5 sm:gap-3.5 shrink-0 self-center lg:self-auto w-full sm:w-auto">
                    @foreach($stats as $stat)
                        @php($isRoute = ($stat['nav']['type'] ?? '') === 'route')
                        <{{ $isRoute ? 'a' : 'button' }}
                            @if($isRoute)
                            href="{{ route($stat['nav']['name']) }}" wire:navigate
                        @else
                            type="button"
                            wire:click="{{ ($stat['nav']['type'] ?? '') === 'event'
                                        ? "\$dispatch('".$stat['nav']['name']."')"
                                        : "\$dispatch('switch-tab', { tab: '".($stat['nav']['tab'] ?? '')."' })" }}"
                        @endif
                        class="group relative flex flex-col items-center justify-between p-3 sm:p-4 rounded-xl
                        bg-[var(--md-sys-color-surface)]/80 border border-[var(--md-sys-color-outline-variant)]/30
                        min-w-0 sm:min-w-[104px] hover:-translate-y-1.5 hover:bg-[var(--md-sys-color-surface)]/95
                        hover:border-[var(--md-sys-color-primary)]/50
                        hover:shadow-[0_12px_26px_color-mix(in_srgb,var(--md-sys-color-primary)_22%,transparent)]
                        active:scale-95 transition-all duration-300 focus:outline-none focus:ring-2
                        focus:ring-[var(--md-sys-color-primary)]/50">

                        @if(($stat['value'] ?? 0) > 0)
                            <span class="absolute top-2 left-2 flex h-2 w-2" title="نیازمند بررسی">
                                    <span
                                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[var(--md-sys-color-primary)] opacity-75"></span>
                                    <span
                                        class="relative inline-flex h-2 w-2 rounded-full bg-[var(--md-sys-color-primary)]"></span>
                                </span>
                        @endif

                        <div
                            class="w-10 h-10 rounded-xl flex items-center justify-center bg-[var(--md-sys-color-primary-container)]/50 group-hover:bg-[var(--md-sys-color-primary-container)] transition-colors shadow-xs">
                                <span
                                    class="material-symbols-rounded text-xl text-[var(--md-sys-color-primary)] transition-transform duration-300 group-hover:scale-115">
                                    {{ $stat['icon'] }}
                                </span>
                        </div>

                        <div class="my-1.5 text-center">
                                <span
                                    class="text-lg sm:text-xl font-black text-[var(--md-sys-color-on-primary-container)] leading-none">
                                    {{ convertToPersian($stat['value']) }}
                                </span>
                        </div>

                        <span
                            class="text-[10px] sm:text-[11px] font-bold text-[var(--md-sys-color-on-primary-container)]/70 tracking-tight text-center whitespace-nowrap group-hover:text-[var(--md-sys-color-primary)] transition-colors">
                                {{ $stat['label'] }}
                            </span>
                </{{ $isRoute ? 'a' : 'button' }}>
                @endforeach
            </div>
        </div>

        {{-- Active Workspace Viewports --}}
        <template x-for="pane in panes" :key="pane">
            <div x-show="slide === pane" x-cloak x-transition.opacity.duration.300ms
                 class="absolute inset-0 p-3 sm:p-5 pb-16">

                <div :class="maximizedPane === pane ? 'max-widget' : ''"
                     class="h-full flex flex-col rounded-xl overflow-hidden bg-[var(--md-sys-color-surface)]
                                border border-[var(--md-sys-color-outline-variant)]/30
                                shadow-[0_8px_32px_color-mix(in_srgb,var(--md-sys-color-shadow)_16%,transparent)] transition-all">

                    <div
                        class="flex items-center justify-between gap-3 px-4 py-2.5 bg-[var(--md-sys-color-surface)] border-b border-[var(--md-sys-color-outline-variant)]/20 shrink-0">
                        <div class="flex items-center gap-2.5 min-w-0">
                                <span
                                    class="flex items-center justify-center w-7 h-7 rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] shadow-xs">
                                    <span class="material-symbols-rounded text-[12px] leading-none font-fill">keep</span>
                                </span>
                        </div>

                        <div class="flex items-center gap-1 shrink-0">
                            <button type="button" x-on:click="toggleMaximize(pane)"
                                    :title="maximizedPane === pane ? 'خروج از تمام‌صفحه' : 'تمام‌صفحه سازمانی'"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] transition-colors">
                                    <span class="material-symbols-rounded text-[17px]"
                                          x-text="maximizedPane === pane ? 'close_fullscreen' : 'open_in_full'"></span>
                            </button>
                            <button type="button" x-on:click="remove(pane)" title="حذف ماژول از میز کار"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-error-container)] hover:text-[var(--md-sys-color-on-error-container)] transition-colors">
                                <span class="material-symbols-rounded text-[17px]">delete</span>
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 relative bg-[var(--md-sys-color-surface)] overflow-hidden">
                        <div x-show="!frameReady[pane]"
                             class="absolute inset-0 flex flex-col items-center justify-center gap-3 animate-pulse"
                             style="background:var(--md-sys-color-surface-variant);">
                                <span class="material-symbols-rounded text-5xl"
                                      style="color:var(--md-sys-color-outline);"
                                      x-text="modules[pane]?.icon"></span>
                            <x-ui.loaders.spin-badge/>
                        </div>
                        <template x-if="loaded[pane]">
                            <iframe x-bind:src="modules[pane].src" class="w-full h-full border-0"
                                    title="ماژول پین‌شده" @load="onFrameLoad(pane)"></iframe>
                        </template>
                    </div>
                </div>
            </div>
        </template>

        {{-- Dock Navigation Bar --}}
        <div class="absolute bottom-1 left-1/2 -translate-x-1/2 z-30 flex items-center gap-1.5 p-1.5 rounded-xl
                        bg-[var(--md-sys-color-surface)]/90 border border-[var(--md-sys-color-outline-variant)]/30 shadow-lg">
            <button type="button" x-on:click="goto(0)" title="میز کار مرکزی (⌘0)"
                    :class="slide === 0
                                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm scale-105'
                                    : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/70'"
                    class="w-7 h-7 rounded-xl flex items-center justify-center transition-all duration-200 focus:outline-none">
                <span class="material-symbols-rounded text-[16px]" aria-hidden="true">dashboard</span>
            </button>

            <template x-for="pane in panes" :key="pane">
                <button type="button" x-on:click="goto(pane)"
                        :title="modules[pane]?.title"
                        :class="slide === pane
                                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm scale-105'
                                    : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/70'"
                        class="w-7 h-7 rounded-full flex items-center justify-center transition-all duration-200 focus:outline-none">
                    <span class="material-symbols-rounded text-[15px]" x-text="modules[pane]?.icon"></span>
                </button>
            </template>

            <button type="button" x-show="canAddPane()" x-on:click="openAddPicker()" title="افزودن میز کار جدید"
                    class="w-7 h-7 rounded-xl flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/70 transition-all duration-200 focus:outline-none">
                <span class="material-symbols-rounded text-[16px]">add_circle</span>
            </button>
        </div>

    </div>

    {{-- ═══════════════════════ PANES ═══════════════════════ --}}
    <x-ui.modals.max-backdrop state="maximizedPane" close="toggleMaximize(null)"/>
    <div wire:key="hero-gadget-picker" x-data="{ gadgetSearch: '', gadgetTitles: @js(collect($gadgetCatalog)->pluck('title')->values()) }">
        <x-ui.modals.panel state="pickerFor" close="pickerFor = null; gadgetSearch = ''"
                            icon="widgets" title="فهرست ماژول‌های فعال"
                            subtitle="جهت استقرار در میز کار شناور، ماژول را انتخاب فرمایید"
                            max-width="max-w-2xl" height="h-[85dvh] sm:h-[680px]">
            <x-slot:search>
                <div class="relative group w-full">
                    <span
                        class="material-symbols-rounded absolute right-3 top-1/2 -translate-y-1/2 text-[20px] text-[var(--md-sys-color-on-surface-variant)] group-focus-within:text-[var(--md-sys-color-primary)] transition-colors pointer-events-none">search</span>
                    <input type="text" x-model="gadgetSearch" placeholder="جستجوی عنوان ماژول..."
                           class="md3-input w-full h-10 pr-10 pl-10 rounded-xl text-sm outline-none transition-all focus:ring-2 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/50">
                    <button x-show="gadgetSearch" type="button" x-on:click="gadgetSearch = ''"
                            class="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] transition-colors">
                        <span class="material-symbols-rounded text-[18px]">close</span>
                    </button>
                </div>
            </x-slot:search>

            <div class="p-4 sm:p-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2.5">
                @foreach($gadgetCatalog as $item)
                    <button type="button"
                            data-title="{{ $item['title'] }}"
                            data-icon="{{ $item['icon'] }}"
                            data-src="{{ $item['src'] }}"
                            x-show="!gadgetSearch || $el.dataset.title.toLowerCase().includes(gadgetSearch.toLowerCase())"
                            x-on:click="pick({ title: $el.dataset.title, icon: $el.dataset.icon, src: $el.dataset.src }); gadgetSearch = ''"
                            class="flex flex-col items-center justify-center gap-2 p-3.5 rounded-xl border border-[var(--md-sys-color-outline-variant)]/25
                           hover:border-[var(--md-sys-color-primary)]/50 hover:bg-[var(--md-sys-color-primary-container)]/25 hover:-translate-y-0.5
                           hover:shadow-sm transition-all duration-200 group active:scale-95 text-center">

                            <span
                                class="flex items-center justify-center w-12 h-12 rounded-xl bg-[var(--md-sys-color-surface-variant)]/70 text-[var(--md-sys-color-primary)] group-hover:scale-110 group-hover:bg-[var(--md-sys-color-primary-container)] transition-all shadow-xs">
                                <span class="material-symbols-rounded text-2xl"
                                      aria-hidden="true">{{ $item['icon'] }}</span>
                            </span>
                        <span
                            class="text-[11px] font-bold text-[var(--md-sys-color-on-surface)] leading-tight">{{ $item['title'] }}</span>
                    </button>
                @endforeach

                <div x-cloak
                     x-show="gadgetSearch && !gadgetTitles.some(t => t.toLowerCase().includes(gadgetSearch.toLowerCase()))"
                     class="col-span-full py-8">
                    <x-ui.empty icon="search_off" title="ماژولی با این عنوان یافت نشد"/>
                </div>
            </div>
        </x-ui.modals.panel>
    </div>
</div>

{{-- ═══════════════════════ QUICK ACCESS (SMART LAUNCHPAD + TEAM PULSE) ═══════════════════════ --}}
<div x-data="shortcut(@js($shortcuts))" x-cloak class="mb-8">
    <div class="group relative rounded-2xl overflow-hidden
                    bg-[var(--md-sys-color-surface)]
                    border border-[var(--md-sys-color-outline-variant)]/30
                    shadow-[0_2px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_8%,transparent)]
                    transition-all duration-300">

        {{-- شیمر پویا --}}
        <div class="h-[2px] w-full overflow-hidden bg-[var(--md-sys-color-surface-variant)]/50">
            <div class="h-full w-1/4 bg-[var(--md-sys-color-primary)] animate-shimmer rounded-full"></div>
        </div>

        <div class="p-4 sm:p-5">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-3">
                    <div class="relative flex items-center justify-center">
                        <span
                            class="absolute w-8 h-8 rounded-xl bg-[var(--md-sys-color-primary)]/15 animate-pulse-ring"></span>
                        <div
                            class="relative w-8 h-8 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center z-10 shadow-xs">
                            <span class="material-symbols-rounded text-base font-fill">bolt</span>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm sm:text-base font-bold text-[var(--md-sys-color-on-surface)]">دسترسی سریع
                                سازمانی</h2>
                            <span x-show="!manual && shortcuts.length"
                                  class="hidden sm:inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-[var(--md-sys-color-primary-container)]/70 text-[var(--md-sys-color-on-primary-container)]">
                                    <span
                                        class="material-symbols-rounded text-[13px] text-[var(--md-sys-color-primary)]">auto_awesome</span>
                                    <span>چینش خودکار هوشمند</span>
                                </span>
                        </div>
                        <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">مسیرهای پرکاربرد بر اساس
                            پردازش هوشمند تعاملات روزانه</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" @click="toggleEdit()"
                            class="h-8 px-3 rounded-xl flex items-center gap-1.5 transition-all duration-200 active:scale-95 text-xs font-semibold shadow-xs"
                            :class="editing
                                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm'
                                    : 'bg-[var(--md-sys-color-surface-variant)]/70 text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]'"
                            :title="editing ? 'ذخیره تنظیمات' : 'شخصی‌سازی میانبرها'">
                        <span class="material-symbols-rounded text-[16px]"
                              x-text="editing ? 'done_all' : 'tune'"></span>
                        <span x-text="editing ? 'ذخیره چینش' : 'شخصی‌سازی'"></span>
                    </button>
                </div>
            </div>

            <div x-show="!editing">
                <div x-show="shortcuts.length"
                     class="flex gap-2.5 overflow-x-auto pt-1 pb-3 px-1 -mx-1 scrollbar-thin scrollbar-thumb-[var(--md-sys-color-outline-variant)]/30 scrollbar-track-transparent snap-x">
                    <template x-for="item in shortcuts" :key="item.key">
                        <button type="button" @click="open(item)"
                                class="group/btn relative shrink-0 snap-start inline-flex items-center gap-3 px-4 h-12 rounded-2xl
                                           bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/40 shadow-xs
                                           hover:-translate-y-1 hover:border-[var(--md-sys-color-primary)]/50
                                           hover:bg-[var(--md-sys-color-surface-variant)]/25
                                           hover:shadow-[0_8px_20px_color-mix(in_srgb,var(--md-sys-color-primary)_15%,transparent)]
                                           active:scale-95 transition-all duration-200
                                           focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/40">

                                <span
                                    class="flex items-center justify-center w-7 h-7 rounded-lg bg-[var(--md-sys-color-primary-container)]/50 text-[var(--md-sys-color-primary)] group-hover/btn:bg-[var(--md-sys-color-primary-container)] group-hover/btn:scale-105 transition-all">
                                    <span class="material-symbols-rounded text-[18px]" x-text="item.icon"></span>
                                </span>

                            <span
                                class="text-[12px] sm:text-[13px] font-bold text-[var(--md-sys-color-on-surface)] whitespace-nowrap"
                                x-text="item.title"></span>
                        </button>
                    </template>
                </div>

                <div x-show="showEmpty"
                     class="flex flex-col sm:flex-row items-center justify-between gap-4 p-5 rounded-2xl text-center sm:text-right
                                bg-[var(--md-sys-color-surface-variant)]/20 border border-dashed border-[var(--md-sys-color-outline-variant)]/60">
                    <div class="flex items-center gap-3.5">
                        <div
                            class="w-10 h-10 rounded-xl bg-[var(--md-sys-color-surface)] flex items-center justify-center border border-[var(--md-sys-color-outline-variant)]/30">
                            <span
                                class="material-symbols-rounded text-2xl text-[var(--md-sys-color-primary)]">touch_app</span>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm font-bold text-[var(--md-sys-color-on-surface)]">هنوز میانبری
                                افزوده نشده است</p>
                            <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">برای دسترسی فوق سریع،
                                ماژول‌های پرکاربرد را مشخص فرمایید.</p>
                        </div>
                    </div>
                    <button type="button" @click="toggleEdit()"
                            class="shrink-0 inline-flex items-center gap-2 px-4 h-9 rounded-xl text-xs font-bold
                                       bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:brightness-105 active:scale-95 transition-all shadow-xs">
                        <span class="material-symbols-rounded text-base">add</span>
                        <span>پیکربندی میانبرها</span>
                    </button>
                </div>
            </div>

            <div x-show="editing" x-transition
                 class="rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-variant)]/10 p-4 sm:p-5 space-y-6">

                <div>
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wider">
                            میانبرهای تثبیت‌شده
                        </p>
                        <span class="text-[10px] font-mono text-[var(--md-sys-color-outline)]"
                              x-text="'تعداد: ' + pinned.length"></span>
                    </div>

                    <template x-if="manual">
                        <div class="flex flex-wrap gap-2">
                            <template x-for="(key, idx) in pinned" :key="key">
                                <div
                                    class="inline-flex items-center gap-1.5 ps-3 pe-1 h-9 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] border border-[var(--md-sys-color-primary)]/20 shadow-xs">
                                        <span class="material-symbols-rounded text-[17px]"
                                              x-text="itemByKey(key)?.icon"></span>
                                    <span class="text-[12px] font-bold whitespace-nowrap"
                                          x-text="itemByKey(key)?.title"></span>

                                    <div
                                        class="flex items-center border-r border-[var(--md-sys-color-on-primary-container)]/10 mr-1 pr-1">
                                        <button type="button" @click="move(key, -1)" :disabled="idx === 0"
                                                class="w-5 h-5 rounded-lg flex items-center justify-center disabled:opacity-20 hover:bg-black/10 transition-colors"
                                                title="انتقال به راست">
                                            <span class="material-symbols-rounded text-[14px]">chevron_right</span>
                                        </button>
                                        <button type="button" @click="move(key, 1)"
                                                :disabled="idx === pinned.length - 1"
                                                class="w-5 h-5 rounded-lg flex items-center justify-center disabled:opacity-20 hover:bg-black/10 transition-colors"
                                                title="انتقال به چپ">
                                            <span class="material-symbols-rounded text-[14px]">chevron_left</span>
                                        </button>
                                        <button type="button" @click="unpin(key)"
                                                class="w-5 h-5 rounded-lg flex items-center justify-center text-[var(--md-sys-color-error)] hover:bg-[var(--md-sys-color-error)]/10 transition-colors"
                                                title="حذف">
                                            <span class="material-symbols-rounded text-[14px]">close</span>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="!manual">
                        <div
                            class="p-3.5 rounded-xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/25 text-[11px] text-[var(--md-sys-color-on-surface-variant)] leading-relaxed">
                            میانبرها هم‌اکنون به شیوهٔ <b>هوشمند خودکار</b> بر اساس نرخ تعاملات شما چیده شده‌اند. برای
                            شخصی‌سازی، آیتم‌ها را از جدول زیر تعیین نمایید.
                        </div>
                    </template>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wider">
                            پایگاه ماژول‌های در دسترس
                        </p>
                        <button type="button" x-show="manual" @click="resetToSmart()"
                                class="text-[11px] font-bold text-[var(--md-sys-color-primary)] hover:underline flex items-center gap-1">
                            <span class="material-symbols-rounded text-[14px]">auto_awesome</span>
                            <span>بازگشت به چیدمان خودکار</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2.5">
                        <template x-for="item in catalog" :key="item.key">
                            <button type="button" @click="isPinned(item.key) ? unpin(item.key) : pin(item.key)"
                                    class="group flex items-center gap-2.5 p-2.5 rounded-xl border text-right transition-all duration-200 active:scale-[0.98]"
                                    :class="isPinned(item.key)
                                            ? 'bg-[var(--md-sys-color-primary-container)] border-[var(--md-sys-color-primary)]/40 text-[var(--md-sys-color-on-primary-container)]'
                                            : 'bg-[var(--md-sys-color-surface)] border-[var(--md-sys-color-outline-variant)]/30 text-[var(--md-sys-color-on-surface)] hover:border-[var(--md-sys-color-primary)]/40'">
                                    <span
                                        class="material-symbols-rounded text-[19px] text-[var(--md-sys-color-primary)] shrink-0"
                                        x-text="item.icon"></span>
                                <span class="text-[11px] font-bold leading-tight flex-1 truncate"
                                      x-text="item.title"></span>
                                <span class="material-symbols-rounded text-[17px] shrink-0"
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

{{-- ═══════════════════════ INTERACTIVE ARCHITECTURAL BLUEPRINT (GUIDE) ═══════════════════════ --}}
<div x-data="{ activeZone: 'all' }" class="mb-8">
    <x-ui.title icon="architecture" title="معماری رابط کاربری و زون‌های دسترسی">
        <x-slot name="actions">
            <div class="hidden sm:flex items-center gap-1 text-[10px] font-bold">
                <button type="button" @click="activeZone = 'all'"
                        :class="activeZone === 'all' ? 'bg-[var(--md-sys-color-secondary)] text-[var(--md-sys-color-on-secondary)]' : 'bg-[var(--md-sys-color-surface-variant)]/50 text-[var(--md-sys-color-on-surface-variant)]'"
                        class="px-2.5 py-1 rounded-lg transition-colors">همه زون‌ها</button>
                <button type="button" @click="activeZone = 'right'"
                        :class="activeZone === 'right' ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-surface-variant)]/50 text-[var(--md-sys-color-on-surface-variant)]'"
                        class="px-2.5 py-1 rounded-lg transition-colors">نوار راست</button>
                <button type="button" @click="activeZone = 'left'"
                        :class="activeZone === 'left' ? 'bg-[var(--md-sys-color-secondary)] text-[var(--md-sys-color-on-secondary)]' : 'bg-[var(--md-sys-color-surface-variant)]/50 text-[var(--md-sys-color-on-surface-variant)]'"
                        class="px-2.5 py-1 rounded-lg transition-colors">نوار چپ</button>
                <button type="button" @click="activeZone = 'top'"
                        :class="activeZone === 'top' ? 'bg-[var(--md-sys-color-tertiary)] text-[var(--md-sys-color-on-tertiary)]' : 'bg-[var(--md-sys-color-surface-variant)]/50 text-[var(--md-sys-color-on-surface-variant)]'"
                        class="px-2.5 py-1 rounded-lg transition-colors">نوار بالا</button>
            </div>
        </x-slot>
    </x-ui.title>

    <div class="relative overflow-hidden rounded-2xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/25 shadow-sm">
        <div class="absolute top-0 right-0 bottom-0 w-1.5 rounded-r-2xl bg-[var(--md-sys-color-secondary)]"></div>

        <div class="grid lg:grid-cols-[280px_1fr] gap-0">
            <div class="p-6 md:p-8 lg:border-l border-[var(--md-sys-color-outline-variant)]/30 bg-[var(--md-sys-color-surface-variant)]/15 flex flex-col justify-between gap-4">
                <div class="space-y-2">
                    <h3 class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">شماتیک موقعیت عناصر</h3>
                </div>

                <div class="w-full aspect-[4/3] rounded-xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/40 p-2 grid grid-rows-[auto_1fr_auto] gap-1.5 shadow-inner">
                    <div class="h-4 rounded-md border flex items-center justify-between px-2 text-[8px] font-mono transition-all"
                         :class="activeZone === 'top' || activeZone === 'all' ? 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] border-[var(--md-sys-color-tertiary)]/30' : 'bg-[var(--md-sys-color-surface-variant)]/40 border-transparent text-transparent'">
                        <span>TOP</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                    </div>

                    <div class="grid grid-cols-[1fr_2.5fr_1fr] gap-1.5 min-h-0" dir="ltr">
                        <div class="rounded-md border flex flex-col items-center justify-center p-1 text-[7px] font-mono text-center transition-all"
                             :class="activeZone === 'left' || activeZone === 'all' ? 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border-[var(--md-sys-color-secondary)]/30' : 'bg-[var(--md-sys-color-surface-variant)]/40 border-transparent text-transparent'">
                            <span>LEFT</span>
                        </div>
                        <div class="rounded-md border border-[var(--md-sys-color-outline-variant)]/20 bg-[var(--md-sys-color-surface-variant)]/20 flex items-center justify-center text-[8px] font-bold text-[var(--md-sys-color-outline)]">
                            APP
                        </div>
                        <div class="rounded-md border flex flex-col items-center justify-center p-1 text-[7px] font-mono text-center transition-all"
                             :class="activeZone === 'right' || activeZone === 'all' ? 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] border-[var(--md-sys-color-primary)]/30' : 'bg-[var(--md-sys-color-surface-variant)]/40 border-transparent text-transparent'">
                            <span>RIGHT</span>
                        </div>
                    </div>

                    <div class="h-3.5 rounded-md border flex items-center justify-center text-[7px] font-mono transition-all"
                         :class="activeZone === 'all' ? 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border-[var(--md-sys-color-secondary)]/30' : 'bg-[var(--md-sys-color-surface-variant)]/40 border-transparent text-transparent'">
                        <span>BOTTOM | MOBILE</span>
                    </div>
                </div>

                <div class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]/70 leading-normal">
                    جهت متمرکز شدن بر راهنمای هر بخش، برچسب‌های را کلیک نمایید.
                </div>
            </div>

            <div class="p-6 md:p-8 md:pr-8 flex flex-col justify-between gap-6">
                <div>
                    <b class="uppercase text-[var(--md-sys-color-secondary)]">
                        راهنمای استقرار پنل‌ها و ابزارهای سیستم در دسکتاپ، تبلت و موبایل
                    </b>
                    <p class="text-xs sm:text-sm leading-[2.1] sm:leading-[2.3] text-[var(--md-sys-color-on-surface-variant)] text-justify">
                        {{ superClean('نوار کناری راست در حالت دسکتاپ و نوار پیمایش پایین، دسترسی سریع به ابزارهای ضروری روزمرهای را فراهم میکنند که بیشترین استفاده را در اپلیکیشن خواهید داشت. نوار کناری چپ برای بارگذاری ابزارهای کاربردی و قابلیتهای اصلی پرتکرار طراحی شده است. نوار پیمایش بالا نیز دسترسی راحت به ابزارهای کمکاربرد و تنظیمات را در یک مکان مشخص فراهم میکند. این نوار میانبرهایی برای تنظیمات وضعیت و دسترسی، حالت نمایش، تم و رنگبندی، افکتهای پسزمینه، بازنشانی حافظه و پالت دستورات جهت یافتن سریع ابزارهای مورد نظر به زبان فارسی یا انگلیسی در اختیار شما قرار میدهد.', 2000) }}
                    </p>
                </div>

                <div class="grid grid-cols-2 sm:flex gap-2.5 pt-2 border-t border-[var(--md-sys-color-outline-variant)]/20">
                    <button type="button" @click="activeZone = 'right'"
                            class="flex flex-1 min-w-0 items-center justify-center gap-2 px-3 py-2 rounded-xl text-[11px] font-bold border transition-all duration-200 active:scale-95 shadow-xs"
                            :class="activeZone === 'right'
                                ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent'
                                : 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] border-[var(--md-sys-color-primary)]/20 hover:brightness-105'">
                        <span class="material-symbols-rounded text-[15px]">view_sidebar</span>
                        <span>نوار کناری راست</span>
                        <span class="opacity-30 hidden sm:inline">|</span>
                        <span class="text-[10px] font-normal opacity-85 font-mono hidden sm:inline">دسکتاپ | دسترسی فوری</span>
                    </button>
                    <button type="button" @click="activeZone = 'left'"
                            class="flex flex-1 min-w-0 items-center justify-center gap-2 px-3 py-2 rounded-xl text-[11px] font-bold border transition-all duration-200 active:scale-95 shadow-xs"
                            :class="activeZone === 'left'
                                ? 'bg-[var(--md-sys-color-secondary)] text-[var(--md-sys-color-on-secondary)] border-transparent'
                                : 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border-[var(--md-sys-color-secondary)]/20 hover:brightness-105'">
                        <span class="material-symbols-rounded text-[15px] -scale-x-100">view_sidebar</span>
                        <span>نوار کناری چپ</span>
                        <span class="opacity-30 hidden sm:inline">|</span>
                        <span class="text-[10px] font-normal opacity-85 font-mono hidden sm:inline">ابزارهای تخصصی</span>
                    </button>
                    <button type="button" @click="activeZone = 'top'"
                            class="flex flex-1 min-w-0 items-center justify-center gap-2 px-3 py-2 rounded-xl text-[11px] font-bold border transition-all duration-200 active:scale-95 shadow-xs"
                            :class="activeZone === 'top'
                                ? 'bg-[var(--md-sys-color-tertiary)] text-[var(--md-sys-color-on-tertiary)] border-transparent'
                                : 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] border-[var(--md-sys-color-tertiary)]/20 hover:brightness-105'">
                        <span class="material-symbols-rounded text-[15px]">menu</span>
                        <span>نوار پیمایش بالا</span>
                        <span class="opacity-30 hidden sm:inline">|</span>
                        <span class="text-[10px] font-normal opacity-85 font-mono hidden sm:inline">مرکز فرمان و پالت دستورات</span>
                    </button>
                    <button type="button" @click="activeZone = 'all'"
                            class="flex flex-1 min-w-0 items-center justify-center gap-2 px-3 py-2 rounded-xl text-[11px] font-bold border transition-all duration-200 active:scale-95 shadow-xs"
                            :class="activeZone === 'all'
                                ? 'bg-[var(--md-sys-color-secondary)] text-[var(--md-sys-color-on-secondary)] border-transparent'
                                : 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border-[var(--md-sys-color-secondary)]/20 hover:brightness-105'">
                        <span class="material-symbols-rounded text-[15px]">bottom_navigation</span>
                        <span>ناوبر همراه (Mobile)</span>
                        <span class="opacity-30 hidden sm:inline">|</span>
                        <span class="text-[10px] font-normal opacity-85 font-mono hidden sm:inline">تبلت و تلفن همراه</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════ MODULES MATRIX ═══════════════════════ --}}
<div class="flex items-center gap-3 mb-4">
    <div
        class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] flex items-center justify-center">
        <span class="material-symbols-rounded text-base font-fill">layers</span>
    </div>
    <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)]">ماژول‌های سیستم</h2>
    <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]/50"></div>
    <span
        class="text-[11px] font-bold px-2.5 py-1 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
            {{ convertToPersian(count($this->modules)) }} ماژول
        </span>
</div>

<div class="space-y-2 mb-8" x-data="{ active: null, q: '' }">
    @if(count($this->modules) > 6)
        <div class="relative mb-3">
                <span
                    class="material-symbols-rounded absolute top-1/2 -translate-y-1/2 right-3 text-[18px] text-[var(--md-sys-color-on-surface-variant)]/60">search</span>
            <input type="text" x-model="q" placeholder="جست‌وجوی ماژول…"
                   class="w-full h-11 rounded-xl bg-[var(--md-sys-color-surface-variant)]/40 border border-[var(--md-sys-color-outline-variant)]/40 pr-10 pl-4 text-sm text-[var(--md-sys-color-on-surface)] placeholder:text-[var(--md-sys-color-on-surface-variant)]/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)]/40 focus:border-[var(--md-sys-color-primary)]/40 transition-colors"/>
        </div>
    @endif
    @foreach($this->modules as $index => $module)
        <div x-data="{ t: @js(mb_strtolower($module['title'])) }"
             x-show="!q || t.includes(q.toLowerCase())"
             class="rounded-2xl overflow-hidden border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] transition-all duration-300"
             :class="active === {{ $index }}
                        ? 'shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] border-[var(--md-sys-color-primary)]/30'
                        : 'hover:border-[var(--md-sys-color-outline-variant)]'">
            <button @click="active = (active === {{ $index }} ? null : {{ $index }})"
                    aria-expanded="active === {{ $index }}" aria-controls="module-panel-{{ $index }}"
                    class="w-full flex items-center justify-between p-4 md:p-5 text-right transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)]/40 hover:bg-[var(--md-sys-color-surface-variant)]/30">
                <div class="flex items-center gap-4">
                    <div class="relative flex-shrink-0">
                        <div
                            class="w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-300"
                            :class="active === {{ $index }}
                                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] scale-110'
                                        : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]'">
                                <span
                                    class="material-symbols-rounded text-xl">{{ $module['icon'] ?? 'extension' }}</span>
                        </div>
                    </div>
                    <div class="text-right">
                            <span
                                class="text-sm font-bold text-[var(--md-sys-color-on-surface)] block">{{ $module['title'] }}</span>
                        <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] mt-0.5 block"
                              x-show="active !== {{ $index }}">کلیک کنید برای اطلاعات بیشتر</span>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                        <span
                            class="hidden sm:flex text-[10px] font-bold px-2 py-0.5 rounded-lg transition-all duration-200"
                            :class="active === {{ $index }}
                                ? 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]'
                                : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]'">
                            {{ convertToPersian(sprintf('%02d', $index + 1)) }}
                        </span>
                    <span class="material-symbols-rounded text-2xl transition-all duration-300"
                          :class="active === {{ $index }} ? 'rotate-180 text-[var(--md-sys-color-primary)]' : 'text-[var(--md-sys-color-outline)]'">expand_more</span>
                </div>
            </button>
            <div id="module-panel-{{ $index }}" x-show="active === {{ $index }}"
                 x-transition:enter="transition ease-out duration-250"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
                <div
                    class="mx-4 mb-4 p-5 rounded-xl bg-[var(--md-sys-color-surface-variant)]/30 border border-[var(--md-sys-color-primary)]/10 border-r-2 border-r-[var(--md-sys-color-primary)]">
                    <p class="text-sm leading-[2] text-[var(--md-sys-color-on-surface-variant)] text-justify">{{ $module['content'] }}</p>
                </div>
            </div>
        </div>
    @endforeach
</div>

</div>
