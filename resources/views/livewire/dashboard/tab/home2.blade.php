<div class="w-full max-w-[88rem] mx-auto !pt-0 pb-10 text-[var(--md-sys-color-on-surface)]"
     dir="rtl"
     x-data="home('{{ addslashes(shortGreeting(auth()->user()?->casual_name ?? '')) }}')">

    <x-ui.title icon="home" title="خانه"/>

    {{-- ═══════════════════════ HERO DECK (live module panes) ═══════════════════════ --}}
    @php($panes = [['p2', 1], ['p3', 2]])
    <div x-data="heroDeck()"
         x-on:keydown.escape.window="if (maximizedPane) toggleMaximize(maximizedPane); if (pickerFor) pickerFor = null"
         class="relative mb-4">
        <div class="relative overflow-hidden rounded-2xl bg-[var(--md-sys-color-primary-container)] border
         border-[var(--md-sys-color-outline-variant)]/20 shadow-[0_2px_20px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)]">

            <div class="absolute inset-0 pointer-events-none opacity-[0.06]"
                 style="background-image: radial-gradient(circle, var(--md-sys-color-primary) 1px, transparent 1px); background-size: 28px 28px;"></div>
            <div class="absolute -left-6 top-1/2 -translate-y-1/2 opacity-[0.07] pointer-events-none select-none">
                <span class="material-symbols-rounded font-fill"
                      style="font-size: 220px; line-height:1;">menu_book</span>
            </div>
            <div class="absolute top-0 right-0 w-64 h-64 rounded-full opacity-30 pointer-events-none"
                 style="background: var(--md-sys-color-primary); transform: translate(30%, -30%);"></div>
            <div class="absolute bottom-0 left-1/3 w-48 h-48 rounded-full  opacity-20 pointer-events-none"
                 style="background: var(--md-sys-color-tertiary); transform: translateY(40%);"></div>

            <div
                class="relative z-10 px-8 pt-10 pb-16 md:pt-14 md:pb-[4.75rem] flex flex-col md:flex-row items-start md:items-center justify-between gap-8 transition-opacity duration-300"
                :class="slide === 0 ? 'opacity-100' : 'opacity-0 invisible'">
                <div class="space-y-4 min-w-0">
                    <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-[var(--md-sys-color-on-primary-container)] leading-snug break-words"
                        x-text="displayed"></h1>
                    <p class="text-sm md:text-base text-[var(--md-sys-color-on-primary-container)]/70 leading-relaxed max-w-md">
                        ✨ ابزارهای کلیدی، ماژول‌ها و راهنمای سیستم همه در یک‌جا
                    </p>
                    <div
                        class="text-[11px] tracking-[0.2em] inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border transition-all duration-300"
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
                @php($chipClass = 'group relative flex flex-col items-center justify-center gap-0.5 px-3 py-2 rounded-xl
                    bg-[var(--md-sys-color-on-primary-container)]/8 border border-[var(--md-sys-color-on-primary-container)]/12 min-w-[76px]
                    hover:-translate-y-0.5 hover:bg-[var(--md-sys-color-on-primary-container)]/12
                    active:scale-95 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/40')
                <div class="grid grid-cols-3 gap-2 shrink-0">
                    @foreach($stats as $stat)
                        @php($isRoute = $stat['nav']['type'] === 'route')
                        @php($tag = $isRoute ? 'a' : 'button')
                        @php($attrs = $isRoute
                            ? 'href="'.e(route($stat['nav']['name'])).'" wire:navigate'
                            : ($stat['nav']['type'] === 'event'
                                ? 'type="button" wire:click=\'$dispatch("'.e($stat['nav']['name']).'")\''
                                : 'type="button" wire:click=\'$dispatch("switch-tab", { tab: "'.e($stat['nav']['tab']).'" })\''))
                        <{{ $tag }} {!! $attrs !!} class="{{ $chipClass }}">
                        @if(($stat['value'] ?? 0) > 0)
                            <span wire:key="home-stat-ping-{{ $stat['key'] }}"
                                  class="absolute top-2 left-2 flex h-2 w-2" title="نیازمند توجه">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[var(--md-sys-color-primary)] opacity-60"></span>
                                <span
                                    class="relative inline-flex h-2 w-2 rounded-full bg-[var(--md-sys-color-primary)]"></span>
                            </span>
                        @endif
                        <span
                            class="material-symbols-rounded text-base text-[var(--md-sys-color-primary)] transition-transform group-hover:scale-110">{{ $stat['icon'] }}</span>
                        <span
                            class="text-lg font-bold text-[var(--md-sys-color-on-primary-container)]">{{ $stat['value'] }}</span>
                        <span
                            class="text-[10px] font-medium text-[var(--md-sys-color-on-primary-container)]/60 tracking-wide whitespace-nowrap">{{ $stat['label'] }}</span>
                </{{ $tag }}>
                @endforeach
            </div>
        </div>
    </div>

    @foreach($panes as [$pane, $slideIdx])
        <div wire:key="hero-pane-{{ $pane }}" x-show="slide === {{ $slideIdx }}" x-cloak
             x-transition.opacity.duration.300ms
             class="absolute inset-0 p-3 md:p-4">
            <div x-show="!modules.{{ $pane }}"
                 class="h-full flex flex-col items-center justify-center gap-3 rounded-xl
                        border-2 border-dashed border-[var(--md-sys-color-on-primary-container)]/25
                        text-[var(--md-sys-color-on-primary-container)]">
                <span class="material-symbols-rounded text-5xl opacity-30" aria-hidden="true">widgets</span>
                <p class="text-sm font-bold opacity-80">این خانه برای ماژول شما خالی است</p>
                <p class="text-[11px] opacity-60 px-6 text-center leading-5">یک ماژول از پنل انتخاب کنید و مثل دسکتاپ
                    ویندوز، همین‌جا پین کنید</p>
                <button type="button" x-on:click="openPicker('{{ $pane }}')"
                        class="mt-1 px-4 py-2 rounded-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] text-xs font-bold flex items-center gap-1.5 hover:brightness-110 active:scale-95 transition-all">
                    <span class="material-symbols-rounded text-base" aria-hidden="true">add_circle</span>
                    افزودن ماژول
                </button>
            </div>
            <div x-show="modules.{{ $pane }}"
                 :class="maximizedPane === '{{ $pane }}' ? 'max-widget' : ''"
                 class="h-full flex flex-col rounded-xl overflow-hidden bg-[var(--md-sys-color-surface)]
                        border border-[var(--md-sys-color-outline-variant)]/30
                        shadow-[0_8px_28px_color-mix(in_srgb,var(--md-sys-color-shadow)_12%,transparent)]">
                <div
                    class="flex items-center justify-between gap-2 px-3 py-2 border-b border-[var(--md-sys-color-outline-variant)]/20 shrink-0">
                    <div class="flex items-center gap-2 min-w-0">
                        <span
                            class="flex items-center justify-center w-6 h-6 rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                            <span class="material-symbols-rounded text-[15px]"
                                  x-text="modules.{{ $pane }}?.icon"></span>
                        </span>
                        <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)] truncate"
                           x-text="modules.{{ $pane }}?.title"></p>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <button type="button" x-on:click="toggleMaximize('{{ $pane }}')"
                                :title="maximizedPane === '{{ $pane }}' ? 'کوچک کردن' : 'بزرگ کردن'"
                                class="w-7 h-7 rounded-lg flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] transition-colors">
                            <span class="material-symbols-rounded text-[16px]"
                                  x-text="maximizedPane === '{{ $pane }}' ? 'close_fullscreen' : 'open_in_full'"></span>
                        </button>
                        <button type="button" x-on:click="remove('{{ $pane }}')" title="حذف از صفحه خانه"
                                class="w-7 h-7 rounded-lg flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-error-container)] hover:text-[var(--md-sys-color-on-error-container)] transition-colors">
                            <span class="material-symbols-rounded text-[16px]">delete</span>
                        </button>
                    </div>
                </div>
                <div class="flex-1 relative bg-[var(--md-sys-color-surface)]">
                    <div x-show="!frameReady.{{ $pane }}"
                         class="absolute inset-0 flex items-center justify-center animate-pulse"
                         style="background:var(--md-sys-color-surface-variant);">
                        <span class="material-symbols-rounded" style="font-size:36px;color:var(--md-sys-color-outline);"
                              x-text="modules.{{ $pane }}?.icon"></span>
                    </div>
                    <template x-if="loaded.{{ $pane }}">
                        <iframe x-bind:src="modules.{{ $pane }}.src" class="w-full h-full border-0"
                                title="ماژول پین‌شده" @load="onFrameLoad('{{ $pane }}')"></iframe>
                    </template>
                </div>
            </div>
        </div>
    @endforeach

    <div class="absolute bottom-3 left-1/2 -translate-x-1/2 z-20 flex items-center gap-1 p-1 rounded-full
                    bg-[var(--md-sys-color-surface)]/85 backdrop-blur border border-[var(--md-sys-color-outline-variant)]/25 shadow-sm">
        <button type="button" x-on:click="goto(0)" title="خانه"
                :class="slide === 0
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                        : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                class="w-7 h-7 rounded-full flex items-center justify-center transition-all">
            <span class="material-symbols-rounded text-[15px]" aria-hidden="true">home</span>
        </button>
        @foreach($panes as [$pane, $slideIdx])
            <button type="button" x-on:click="goto({{ $slideIdx }})"
                    :title="modules.{{ $pane }} ? modules.{{ $pane }}.title : 'میز کار جدید'"
                    :class="slide === {{ $slideIdx }}
                            ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                            : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                    class="w-7 h-7 rounded-full flex items-center justify-center transition-all">
                <span class="material-symbols-rounded text-[15px]"
                      x-text="modules.{{ $pane }} ? modules.{{ $pane }}.icon : 'add_circle'"></span>
            </button>
        @endforeach
    </div>

    <x-ui.modals.max-backdrop state="maximizedPane" close="toggleMaximize(null)"/>

    <div wire:key="hero-gadget-picker" x-show="pickerFor" x-cloak x-transition.opacity.duration.200ms
         class="fixed inset-0 z-[9999] flex items-center justify-center p-3 sm:p-4 md:p-8
                    bg-[var(--md-sys-color-primary)]/60"
         x-on:click.self="pickerFor = null"
         role="dialog" aria-label="افزودن ماژول به صفحه خانه">
        <div
            class="w-full max-w-2xl max-h-[85dvh] flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/30 shadow-2xl overflow-hidden">
            <div
                class="flex items-center justify-between gap-3 px-4 py-3 border-b border-[var(--md-sys-color-outline-variant)]/20">
                <p class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">افزودن ماژول به صفحه خانه</p>
                <button type="button" x-on:click="pickerFor = null" title="بستن"
                        class="w-7 h-7 rounded-lg flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] transition-colors">
                    <span class="material-symbols-rounded text-[16px]">close</span>
                </button>
            </div>
            <div class="p-3 grid grid-cols-3 sm:grid-cols-4 gap-2 flex-1 min-h-0 overflow-y-auto custom-scrollbar">
                @foreach($gadgetCatalog as $item)
                    <button type="button"
                            data-title="{{ $item['title'] }}"
                            data-icon="{{ $item['icon'] }}"
                            data-src="{{ $item['src'] }}"
                            x-on:click="pick({ title: $el.dataset.title, icon: $el.dataset.icon, src: $el.dataset.src })"
                            class="flex flex-col items-center justify-center gap-1 px-2 py-2 rounded-xl border border-[var(--md-sys-color-outline-variant)]/25
                                       hover:border-[var(--md-sys-color-primary)]/50 hover:bg-[var(--md-sys-color-primary-container)]/30 transition-all group">
                            <span
                                class="flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-primary)] group-hover:scale-110 transition-transform">
                                <span class="material-symbols-rounded text-xl"
                                      aria-hidden="true">{{ $item['icon'] }}</span>
                            </span>
                        <span
                            class="text-[11px] font-bold text-[var(--md-sys-color-on-surface)] text-center leading-4">{{ $item['title'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════ TEAM PULSE ═══════════════════════ --}}
@if($teamPulse->isNotEmpty())
    @php($visiblePulse = $teamPulse->take(6))
    @php($remainingPulse = max(0, $teamPulse->count() - $visiblePulse->count()))
    <div wire:key="home-team-pulse" class="relative mt-2 mb-8">
        <button type="button" wire:click='$dispatch("switch-tab", { tab: "status" })'
                aria-label="مشاهده وضعیت همکاران"
                class="w-full flex items-center gap-3 rounded-2xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/20
                           shadow-sm px-4 py-3
                           hover:-translate-y-0.5 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/40">
            <span class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] shrink-0 hidden sm:inline">همکاران حاضر</span>
            <div class="flex items-center -space-x-1.5 rtl:space-x-reverse">
                @foreach($visiblePulse as $member)
                    @php($mp = presence($member->presence))
                    <div wire:key="home-team-pulse-{{ $member->id }}"
                         class="relative"
                         title="{{ $member->casual_name }}">
                        <img src="{{ $member->getProfileImageUrl() ?? $member->getInitialsAvatarUrl() }}"
                             alt="{{ $member->name }}"
                             loading="lazy"
                             class="w-9 h-9 rounded-md object-cover ring-2 ring-[var(--md-sys-color-surface)]">
                        <span
                            class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-{{ $mp->color() }}-500 border-2 border-[var(--md-sys-color-surface)]"></span>
                    </div>
                @endforeach
                @if($remainingPulse > 0)
                    <div wire:key="home-team-pulse-remainder"
                         class="relative w-9 h-9 rounded-md bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] ring-2 ring-[var(--md-sys-color-surface)] flex items-center justify-center">
                        <span class="text-[10px] font-bold">+{{ $remainingPulse }}</span>
                    </div>
                @endif
            </div>
            <span class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]/40"></span>
            <span class="text-[11px] font-bold text-[var(--md-sys-color-primary)] shrink-0">مشاهده همه</span>
        </button>
    </div>
@endif

{{-- ═══════════════════════ QUICK ACCESS ═══════════════════════ --}}
<div x-data="shortcut(@js($shortcuts))" x-cloak class="mb-8">

    <div class="group relative rounded-2xl overflow-hidden
                bg-[var(--md-sys-color-surface)]
                border border-[var(--md-sys-color-primary)]/25
                shadow-[0_2px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)]">

        <div
            class="hidden group-hover:block relative h-[2px] overflow-hidden bg-[var(--md-sys-color-primary-container)]">
            <div
                class="absolute inset-y-0 w-1/3 bg-[var(--md-sys-color-primary)]/60 animate-shimmer rounded-full"></div>
        </div>

        <div class="p-4">
            <div class="flex items-center gap-3 mb-4">
                <div class="relative flex items-center justify-center">
                    <span
                        class="absolute w-8 h-8 rounded-xl bg-[var(--md-sys-color-primary)]/20 animate-pulse-ring"></span>
                    <div
                        class="relative w-8 h-8 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center z-10">
                        <span class="material-symbols-rounded text-base font-fill">bolt</span>
                    </div>
                </div>

                <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)]">دسترسی سریع</h2>
                <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]/50"></div>

                <span x-show="!manual && shortcuts.length"
                      class="hidden sm:inline-flex items-center gap-1 text-[10px] font-bold text-[var(--md-sys-color-on-surface-variant)]/70">
                    <span
                        class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-primary)] animate-occasion-bounce">auto_awesome</span>
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
                            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]"
                                  x-text="item.icon"></span>
                            <span class="text-[13px] font-bold text-[var(--md-sys-color-on-surface)] whitespace-nowrap"
                                  x-text="item.title"></span>
                        </button>
                    </template>
                </div>

                <div x-show="showEmpty"
                     class="flex flex-col sm:flex-row items-center justify-between gap-4 p-5 rounded-2xl text-center sm:text-right
                            bg-[var(--md-sys-color-surface)] border border-dashed border-[var(--md-sys-color-outline-variant)]">
                    <div class="flex items-center gap-3">
                        <span
                            class="material-symbols-rounded text-[28px] text-[var(--md-sys-color-primary)]">touch_app</span>
                        <p class="text-sm text-[var(--md-sys-color-on-surface-variant)]">هنوز میان‌بری ندارید —
                            ماژول‌های پرکاربردتان را انتخاب کنید تا اینجا بیایند.</p>
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
                    <p class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wider mb-2">
                        میان‌برهای شما</p>

                    <template x-if="manual">
                        <div class="flex flex-wrap gap-2">
                            <template x-for="(key, idx) in pinned" :key="key">
                                <div
                                    class="inline-flex items-center gap-1 ps-3 pe-1 h-10 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] border border-[var(--md-sys-color-primary)]/15">
                                    <span class="material-symbols-rounded text-[18px]"
                                          x-text="itemByKey(key)?.icon"></span>
                                    <span class="text-[12px] font-bold whitespace-nowrap"
                                          x-text="itemByKey(key)?.title"></span>
                                    <button type="button" @click="move(key,-1)" :disabled="idx===0"
                                            class="w-6 h-6 rounded-lg flex items-center justify-center disabled:opacity-30 hover:bg-black/5">
                                        <span class="material-symbols-rounded text-[16px]">chevron_right</span>
                                    </button>
                                    <button type="button" @click="move(key,1)" :disabled="idx===pinned.length-1"
                                            class="w-6 h-6 rounded-lg flex items-center justify-center disabled:opacity-30 hover:bg-black/5">
                                        <span class="material-symbols-rounded text-[16px]">chevron_left</span>
                                    </button>
                                    <button type="button" @click="unpin(key)"
                                            class="w-6 h-6 rounded-lg flex items-center justify-center text-[var(--md-sys-color-error)] hover:bg-[var(--md-sys-color-error)]/10">
                                        <span class="material-symbols-rounded text-[16px]">close</span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="!manual">
                        <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]/70 leading-relaxed">
                            در حال حاضر میان‌برها به‌صورت <b>هوشمند</b> بر اساس میزان استفادهٔ شما نمایش داده می‌شوند.
                            برای سفارشی‌سازی دستی، از فهرست زیر انتخاب کنید.
                        </p>
                    </template>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wider">
                            همهٔ ماژول‌ها</p>
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
                                <span
                                    class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)] shrink-0"
                                    x-text="item.icon"></span>
                                <span class="text-[12px] font-bold leading-tight flex-1 truncate"
                                      x-text="item.title"></span>
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
    <div
        class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center">
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
        <div
            class="flex items-start justify-center p-6 md:p-8 md:border-l border-[var(--md-sys-color-outline-variant)]/50">
            <div
                class="w-14 h-14 rounded-2xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center shadow-sm">
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
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-semibold bg-[var(--md-sys-color-{{ $nav['color'] }}-container)] text-[var(--md-sys-color-on-{{ $nav['color'] }}-container)] border border-[var(--md-sys-color-{{ $nav['color'] }})]/15">
                            <span
                                class="material-symbols-rounded text-[13px] {{ $nav['class'] ?? '' }}">{{ $nav['icon'] }}</span>
                            {{ $nav['label'] }}
                        </span>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════ MODULES ACCORDION ═══════════════════════ --}}
<div class="flex items-center gap-3 mb-4">
    <div
        class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] flex items-center justify-center">
        <span class="material-symbols-rounded text-base font-fill">layers</span>
    </div>
    <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)]">ماژول‌های سیستم</h2>
    <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]/50"></div>
    <span
        class="text-[11px] font-bold px-2.5 py-1 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
            {{ count($this->modules) }} ماژول
        </span>
</div>

<div class="space-y-2 mb-8" x-data="{ active: null }">
    @foreach($this->modules as $index => $module)
        <div
            class="rounded-2xl overflow-hidden border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] transition-all duration-300"
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
                <div
                    class="mx-4 mb-4 p-5 rounded-xl bg-[var(--md-sys-color-surface-variant)]/30 border border-[var(--md-sys-color-primary)]/10 border-r-2 border-r-[var(--md-sys-color-primary)]">
                    <p class="text-sm leading-[2] text-[var(--md-sys-color-on-surface-variant)] text-justify">{{ $module['content'] }}</p>
                </div>
            </div>
        </div>
    @endforeach
</div>
</div>
