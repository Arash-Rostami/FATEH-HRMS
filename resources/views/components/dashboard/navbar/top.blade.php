<nav x-cloak
     x-data="menu()"
     dir="rtl"
     @resize.window="updatePerPage"
     class="bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] px-4 lg:px-6 flex justify-between items-center h-16 border-b border-[var(--md-sys-color-on-primary)]/10 shrink-0 relative z-50 transition-colors duration-300">

    {{--    Right Section: Navigation & Search --}}
    <div class="flex items-center gap-3 shrink-0">
        <x-dashboard.navbar.hamburger/>

        <div class="hidden xl:block w-px h-6 bg-[var(--md-sys-color-on-primary)]/15 mx-1"></div>

        <div class="relative">
            <div
                class="flex items-center justify-center h-[40px] md:min-w-[176px] w-10 md:w-auto gap-3 px-0 md:px-4
                       rounded-[12px]
                       bg-[var(--md-sys-color-on-primary)]/5
                       border border-[var(--md-sys-color-on-primary)]/10
                       backdrop-blur-sm transition-all duration-300
                       hover:bg-[var(--md-sys-color-on-primary)]/10
                       shadow-sm cursor-pointer group"
                @click="$dispatch('open-command-palette')">

                <span class="material-symbols-rounded text-[20px] opacity-80 group-hover:opacity-100 transition-opacity">search</span>
                <span class="hidden md:inline text-xs opacity-60 group-hover:opacity-80 transition-opacity font-medium">جستجو...</span>
                <div class="hidden md:flex items-center gap-1 mr-auto">
                    <span class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-on-primary)]/10 border border-[var(--md-sys-color-on-primary)]/10 text-[10px] opacity-60 font-mono">Ctrl</span>
                    <span class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-on-primary)]/10 border border-[var(--md-sys-color-on-primary)]/10 text-[10px] opacity-60 font-mono">K</span>
                </div>
            </div>
            <!-- Logic component handles the modal -->
            <livewire:dashboard.navbar.command-palette/>
        </div>
    </div>

    {{--    Center Section: Time & Weather (Desktop Only) --}}
    <div class="hidden xl:flex items-center gap-4 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 pointer-events-none">
        <!-- Weather -->
        <div
            class="flex items-center justify-center h-[40px] min-w-[176px] gap-3 px-4
               rounded-[12px]
               bg-[var(--md-sys-color-on-primary)]/5
               border border-[var(--md-sys-color-on-primary)]/10
               backdrop-blur-sm transition-all duration-300
               hover:bg-[var(--md-sys-color-on-primary)]/10
               shadow-sm pointer-events-auto">
            <livewire:dashboard.header.weather/>
        </div>

        <!-- Timer -->
        <div
            x-data="timer" x-cloak
            class="flex items-center justify-center h-[40px] min-w-[176px] gap-3 px-4
               rounded-[12px]
               bg-[var(--md-sys-color-on-primary)]/5
               border border-[var(--md-sys-color-on-primary)]/10
               backdrop-blur-sm transition-all duration-300
               hover:bg-[var(--md-sys-color-on-primary)]/10
               shadow-sm pointer-events-auto">
            <x-dashboard.navbar.timer/>
        </div>
    </div>

    {{--    Left Section: User Tools, Status, Notifications --}}
    <div class="flex items-center gap-2 md:gap-3 shrink-0 justify-end overflow-x-auto no-scrollbar pl-1 max-w-[calc(100vw-120px)] xl:max-w-none">

        <!-- Status Switcher -->
        <div class="shrink-0" title="وضعیت حضور">
            <div
                class="flex items-center justify-center h-[40px] md:min-w-[140px] w-auto gap-0 md:gap-3 px-2 md:px-4
                   rounded-[12px]
                   bg-[var(--md-sys-color-on-primary)]/5
                   border border-[var(--md-sys-color-on-primary)]/10
                   backdrop-blur-sm transition-all duration-300
                   hover:bg-[var(--md-sys-color-on-primary)]/10
                   shadow-sm overflow-hidden">
                <livewire:dashboard.navbar.status-switcher/>
            </div>
        </div>

        <div class="hidden xl:block w-px h-6 bg-[var(--md-sys-color-on-primary)]/15 mx-1"></div>

        <!-- Utility Icons Group -->
        <div class="flex items-center gap-1 md:gap-2">
            <!-- Fullscreen -->
            <div title="حالت تمام صفحه" class="shrink-0">
                <x-dashboard.navbar.fullscreen/>
            </div>

            <!-- Pills -->
            @auth
                <x-dashboard.navbar.pills :user="auth()->user()"/>
            @endauth

            <!-- Palette -->
            <div title="شخصی‌سازی ظاهر" class="shrink-0">
                <x-dashboard.navbar.palette/>
            </div>

            <!-- Settings -->
            <div title="تنظیمات سریع" class="shrink-0">
                <livewire:dashboard.navbar.quick-settings/>
            </div>

            <!-- Release Note -->
            <div title="یادداشت‌های انتشار" class="shrink-0">
                <x-dashboard.navbar.release-note/>
            </div>

            <!-- Notification -->
            <div title="اعلان‌ها" class="shrink-0">
                <x-dashboard.navbar.notification/>
            </div>
        </div>

        <div class="hidden md:block w-px h-6 bg-[var(--md-sys-color-on-primary)]/15 mx-1"></div>

        <!-- Account -->
        <div title="حساب کاربری" class="shrink-0">
            <x-dashboard.navbar.account/>
        </div>
    </div>

    <!-- Modals -->
    <x-dashboard.release-modal/>
</nav>
