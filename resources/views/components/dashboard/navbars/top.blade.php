@props([
    'activeTab' => 'home',
    'direction' => 'up'
])

<div x-data='menu({ canAdmin: @json(canAdmin()), disabledReservationTypes: @json(disabledReservationTypes()) })'
     id="navbar"
     @resize.window="updatePerPage"
     :class="isVisible ? 'translate-y-0' : '-translate-y-[60px] lg:-translate-y-[80px]'"
     class="sticky top-[60px] lg:top-[80px] z-[51] w-full will-change-transform transition-transform duration-300 ease-in-out">

    <nav x-cloak
         dir="rtl"
         class="bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] px-3 lg:px-6 flex justify-between items-center h-16 border-b border-[var(--md-sys-color-on-primary)]/10 shrink-0">

        {{-- Menu Modal --}}
        <x-dashboard.modal.menu/>

        <div class="flex items-center gap-2 lg:gap-3">
            <x-dashboard.navbars.top.hamburger />
        </div>
        <div class="w-px h-6 bg-[var(--md-sys-color-on-primary)]/15 mx-1"></div>

        <div class="w-3/5 md:w-full flex justify-center max-w-xs md:max-w-md mx-2 md:mx-4">
            <div class="relative w-full">
                <div class="flex items-center justify-center h-[40px] w-full gap-2 md:gap-3 px-3 md:px-4 rounded-[12px] bg-[var(--md-sys-color-on-primary)]/5 border border-[var(--md-sys-color-on-primary)]/10 transition-all duration-300 hover:bg-[var(--md-sys-color-on-primary)]/10 shadow-sm cursor-pointer group relative"
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

        <div class="hidden xl:flex justify-start items-center gap-1.5 lg:gap-2 mr-auto">
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
            <x-dashboard.modal.release/>
            @auth
                <livewire:dashboard.release-request.main/>
            @endauth
            <livewire:dashboard.navbar.quick-settings/>
        </div>

        <div class="xl:hidden flex items-center justify-start gap-1 max-w-1/4" x-data="{ showSecondary: false }">

            <button type="button"
                    @click="showSecondary = !showSecondary"
                    class="group scale-90 relative w-8 h-6 flex items-center justify-center rounded-lg bg-[var(--md-sys-color-on-primary)] text-[var(--md-sys-color-primary)] shadow-md shadow-black/20 border-[2px] border-[var(--md-sys-color-primary)] transition-all duration-300 hover:scale-110 active:scale-95 z-20">

                <span class="material-symbols-rounded !text-[16px] transition-transform duration-500"
                      :class="showSecondary ? 'rotate-180' : 'rotate-0'">
                    swap_vert
                </span>

                <div class="absolute inset-0 rounded-full bg-[var(--md-sys-color-on-primary)] opacity-0 group-hover:animate-ping pointer-events-none"></div>
                <x-ui.modals.tooltip text="تغییر ابزارها" position="bottom" />
            </button>

            <div class="relative right-6 h-[40px] w-32 sm:w-48 !float-left">
                <div class="absolute inset-0 flex items-center justify-center gap-1.5 transition-all duration-500 mx-auto"
                     :class="!showSecondary ? 'translate-y-0 opacity-100' : '-translate-y-full opacity-0 pointer-events-none'">
                    <div class="scale-75 origin-right flex items-center gap-1.5">
                        <livewire:dashboard.navbar.quick-settings/>
                        <div class="top-1 relative">
                            <x-dashboard.navbars.top.fullscreen/>
                        </div>
                    </div>
                </div>
                <div class="absolute inset-0 flex items-center justify-center gap-1.5 transition-all duration-500 mx-auto"
                     :class="showSecondary ? 'translate-y-0 opacity-100' : 'translate-y-full opacity-0 pointer-events-none'">
                    <div class="scale-75 origin-right flex items-center gap-1.5">
                        <x-dashboard.navbars.top.palette/>
                        <livewire:dashboard.navbar.status-switcher/>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-1 sm:gap-1.5 mr-1 xl:mr-0">
            <div class="w-px h-6 bg-[var(--md-sys-color-on-primary)]/15 mx-1 hidden sm:block"></div>
            <x-dashboard.navbars.top.notification/>
            <x-dashboard.navbars.top.account/>
        </div>
        <x-ui.modals.confirmation />
    </nav>
</div>
