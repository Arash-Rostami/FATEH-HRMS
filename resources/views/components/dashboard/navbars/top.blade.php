@props([
    'activeTab' => null,
    'direction' => 'up'
])

@php($navbarIsHome = request()->routeIs('dashboard') && request('tab', 'home') === 'home')

<div x-data='menu({ canAdmin: @json(canAdmin()), disabledReservationTypes: @json(disabledReservationTypes()), menuState: @json($menuState)@if(config('app.off')), off: @json(tenantOffKeys())@endif })'
     id="navbar"
     @resize.window="updatePerPage"
     :class="isVisible ? 'translate-y-0' : '-translate-y-[60px] lg:-translate-y-[80px]'"
     class="sticky top-[60px] lg:top-[80px] z-[51] w-full will-change-transform transition-transform duration-300 ease-in-out">

    <nav x-cloak
         dir="rtl"
         :class="$store.focus.active ? 'focus-dim' : ''"
         class="bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] px-3 lg:px-6 flex justify-between items-center h-16 border-b border-[var(--md-sys-color-on-primary)]/10 shrink-0">

        {{-- Menu Modal --}}
        <x-dashboard.modal.menu/>

        <div class="flex items-center gap-2 lg:gap-3">
            <x-dashboard.navbars.top.hamburger />
            <div x-data="{ isHome: @json($navbarIsHome) }" x-on:tab-switched.window="isHome = $event.detail.tab === 'home'" :class="isHome ? '' : 'max-md:hidden'" data-declutter-hide>
                <x-dashboard.navbars.top.ai-assistant />
            </div>
            <x-dashboard.navbars.top.home />
        </div>
        <div data-declutter-hide class="w-px h-6 bg-[var(--md-sys-color-on-primary)]/15 mx-1"></div>

        <div class="w-3/5 md:w-full flex justify-center max-w-xs md:max-w-md mx-2 md:mx-4">
            <div class="relative w-full">
                <div data-declutter-hide class="flex items-center justify-center h-[40px] w-full gap-2 md:gap-3 px-3 md:px-4 rounded-[12px] bg-[var(--md-sys-color-on-primary)]/5 border border-[var(--md-sys-color-on-primary)]/10 transition-all duration-300 hover:bg-[var(--md-sys-color-on-primary)]/10 shadow-sm cursor-pointer group relative"
                     @click="$dispatch('open-command-palette')">
                    <span class="material-symbols-rounded text-[20px] opacity-80 group-hover:opacity-100 transition-opacity">search</span>
                    <span class="hidden md:inline text-xs opacity-60 group-hover:opacity-80 transition-opacity font-medium">جستجو...</span>
                    <div class="hidden md:flex items-center gap-1 mr-auto opacity-40 group-hover:opacity-100 transition-opacity">
                        <span class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-on-primary)]/10 border border-[var(--md-sys-color-on-primary)]/10 text-[9px] font-mono">Ctrl</span>
                        <span class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-on-primary)]/10 border border-[var(--md-sys-color-on-primary)]/10 text-[9px] font-mono">K</span>
                    </div>
                    <x-ui.modals.tooltip text="جستجو (Ctrl+K)" position="bottom" />
                </div>
                <livewire:dashboard.navbar.command-palette/>
            </div>
        </div>

        <div data-declutter-hide class="hidden xl:flex justify-start items-center gap-1.5 lg:gap-2 mr-auto">
            <div class="hidden 2xl:flex items-center gap-2">
                <div class="flex items-center justify-center h-[40px] min-w-[140px] gap-2 px-3 rounded-[12px] bg-[var(--md-sys-color-on-primary)]/5 border border-[var(--md-sys-color-on-primary)]/10 transition-all duration-300 hover:bg-[var(--md-sys-color-on-primary)]/10 shadow-sm">
                    <livewire:dashboard.navbar.weather/>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <div x-data="timer" x-cloak
                     class="flex items-center justify-center h-[40px] min-w-[140px] gap-2 px-3 rounded-[12px] bg-[var(--md-sys-color-on-primary)]/5 border border-[var(--md-sys-color-on-primary)]/10  transition-all duration-300 hover:bg-[var(--md-sys-color-on-primary)]/10 shadow-sm">
                    <x-dashboard.navbars.top.clock/>
                </div>

                <div class="flex items-center justify-center h-[40px] min-w-[140px] gap-2 px-3 rounded-[12px] bg-[var(--md-sys-color-on-primary)]/5 border border-[var(--md-sys-color-on-primary)]/10  transition-all duration-300 hover:bg-[var(--md-sys-color-on-primary)]/10 shadow-sm">
                    <livewire:dashboard.navbar.status-switcher/>
                </div>
            </div>
            <div class="w-px h-6 bg-[var(--md-sys-color-on-primary)]/15 mx-1"></div>

            <x-dashboard.navbars.top.fullscreen/>

            @auth
                <x-dashboard.navbars.top.pills :user="auth()->user()"/>
            @endauth

            <x-dashboard.navbars.top.palette/>

            <livewire:dashboard.navbar.quick-settings/>
        </div>

        @php($navHasUnread = auth()->check() && auth()->user()->unreadNotifications()->exists())
        <div data-declutter-hide class="xl:hidden flex shrink-0 items-center gap-1.5"
             x-data="{
                 cur: window.innerWidth < 640 ? 'bell' : 'settings',
                 unread: @js($navHasUnread),
                 due: 0,
                 order() { const tools = ['settings', 'fullscreen', 'palette', 'presence']; return window.innerWidth < 640 ? ['bell', @unless(tenantOff('reminder'))'reminder', @endunless...tools] : tools; },
                 next() { const o = this.order(); this.cur = o[(o.indexOf(this.cur) + 1) % o.length]; },
                 cls(name) {
                     const d = this.order().indexOf(name) - this.order().indexOf(this.cur);
                     return d === 0 ? 'translate-y-0 opacity-100' : (d < 0 ? '-translate-y-full' : 'translate-y-full') + ' opacity-0 pointer-events-none';
                 }
             }"
             x-on:bell-unread.window="unread = $event.detail.lit"
             x-on:reminder-due.window="due = $event.detail.count"
             x-on:resize.window="if (! order().includes(cur)) cur = order()[0]">

            <button type="button"
                    @click="next()"
                    class="group scale-90 relative w-8 h-6 shrink-0 flex items-center justify-center rounded-lg bg-[var(--md-sys-color-on-primary)] text-[var(--md-sys-color-primary)] shadow-md shadow-black/20 border-[2px] border-[var(--md-sys-color-primary)] transition-all duration-300 hover:scale-110 active:scale-95 z-20">

                <span class="material-symbols-rounded !text-[16px] transition-transform duration-500"
                      :class="order().indexOf(cur) % 2 ? 'rotate-180' : 'rotate-0'">
                    swap_vert
                </span>
                <span x-show="(unread && cur !== 'bell') || (due > 0 && cur !== 'reminder')" x-cloak class="sm:hidden absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-[var(--md-sys-color-error)] border border-[var(--md-sys-color-primary)] animate-pulse"></span>

                <div class="absolute inset-0 rounded-full bg-[var(--md-sys-color-on-primary)] opacity-0 group-hover:animate-ping pointer-events-none"></div>
                <x-ui.modals.tooltip text="تغییر ابزارها" position="bottom" />
            </button>

            <div class="relative h-10 w-10 shrink-0">
                @auth
                    <div class="sm:hidden absolute inset-0 flex items-center justify-center transition-all duration-500" :class="cls('bell')">
                        <x-dashboard.navbars.top.notification :lit="$navHasUnread"/>
                    </div>
                    @unless(tenantOff('reminder'))
                    <div class="sm:hidden absolute inset-0 flex items-center justify-center transition-all duration-500" :class="cls('reminder')">
                        <button type="button" x-on:click="$dispatch('open-reminders')" aria-label="یادآوری‌ها"
                                class="group relative w-10 h-10 active:scale-95 transition-all duration-200 flex items-center justify-center">
                            <span class="material-symbols-rounded text-[22px] opacity-70 group-hover:opacity-100 transition-opacity">alarm</span>
                            <span x-show="due > 0" x-cloak class="absolute top-2 right-2 w-2 h-2 rounded-full bg-[var(--md-sys-color-error)] animate-pulse"></span>
                        </button>
                    </div>
                    @endunless
                @endauth
                <div class="absolute inset-0 flex items-center justify-center transition-all duration-500" :class="cls('settings')">
                    <livewire:dashboard.navbar.quick-settings/>
                </div>
                <div class="absolute inset-0 flex items-center justify-center transition-all duration-500" :class="cls('fullscreen')">
                    <x-dashboard.navbars.top.fullscreen/>
                </div>
                <div class="absolute inset-0 flex items-center justify-center transition-all duration-500" :class="cls('palette')">
                    <x-dashboard.navbars.top.palette/>
                </div>
                <div class="absolute inset-0 flex items-center justify-center transition-all duration-500" :class="cls('presence')">
                    <div class="w-10 h-10"><livewire:dashboard.navbar.status-switcher/></div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-1 sm:gap-1.5 mr-1 xl:mr-0">
            <div data-declutter-hide class="w-px h-6 bg-[var(--md-sys-color-on-primary)]/15 mx-1 hidden sm:block"></div>
            @auth
                @unless(tenantOff('reminder'))
                <div data-declutter-hide class="hidden sm:block">
                    <livewire:dashboard.reminder.main wire:key="reminder-navbar"/>
                </div>
                @endunless
            @endauth
            <div class="max-sm:hidden"><x-dashboard.navbars.top.notification :lit="$navHasUnread"/></div>
            <x-dashboard.navbars.top.account/>
        </div>
        <x-ui.modals.confirmation />
    </nav>
</div>
