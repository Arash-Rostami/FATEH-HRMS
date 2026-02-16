<nav x-cloak
     x-data="menu()"
     dir="rtl"
     @resize.window="updatePerPage"
     class="bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] px-4 lg:px-6 flex justify-between items-center h-16 border-b border-[var(--md-sys-color-on-primary)]/10 shrink-0 relative z-50 transition-colors duration-300">

    {{--    Hamburger btn--}}
    <div class="flex items-center gap-3 ">
        <x-dashboard.navbar.hamburger/>
    </div>
    <div class="hidden xl:block w-px h-6 bg-[var(--md-sys-color-on-primary)]/15 mx-2"></div>

    {{--    1st section--}}
    <div class="h-[64px] grid grid-cols-4 items-center w-full px-6">
        <!-- Command Palette (left) -->
        <div class="flex justify-start">
            <div class="mr-2 relative">
                <div
                    class="flex items-center justify-center h-[40px] min-w-[176px] gap-3 px-4
                           rounded-[12px]
                           bg-[var(--md-sys-color-on-primary)]/5
                           border border-[var(--md-sys-color-on-primary)]/10
                           backdrop-blur-sm transition-all duration-300
                           hover:bg-[var(--md-sys-color-on-primary)]/10
                           shadow-sm cursor-pointer group"
                    @click="$dispatch('open-command-palette')">

                    <span class="material-symbols-rounded text-[20px] opacity-80 group-hover:opacity-100 transition-opacity">search</span>
                    <span class="text-xs opacity-60 group-hover:opacity-80 transition-opacity font-medium">جستجو...</span>
                    <div class="flex items-center gap-1 mr-auto">
                        <span class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-on-primary)]/10 border border-[var(--md-sys-color-on-primary)]/10 text-[10px] opacity-60 font-mono">Ctrl</span>
                        <span class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-on-primary)]/10 border border-[var(--md-sys-color-on-primary)]/10 text-[10px] opacity-60 font-mono">K</span>
                    </div>
                </div>
                <!-- Logic component handles the modal -->
                <livewire:dashboard.navbar.command-palette/>
            </div>
        </div>

        <!-- Weather (2nd column) -->
        <div class="flex justify-end">
            <div class="hidden xl:block">
                <div
                    class="flex items-center justify-center h-[40px] min-w-[176px] gap-3 px-4
               rounded-[12px]
               bg-[var(--md-sys-color-on-primary)]/5
               border border-[var(--md-sys-color-on-primary)]/10
               backdrop-blur-sm transition-all duration-300
               hover:bg-[var(--md-sys-color-on-primary)]/10
               shadow-sm">
                    <livewire:dashboard.header.weather/>
                </div>
            </div>
        </div>

        <!-- Time (center column) -->
        <div class="flex justify-center">
            <div class="hidden lg:block">
                <div
                    x-data="timer" x-cloak
                    class="flex items-center justify-center h-[40px] min-w-[176px] gap-3 px-4
               rounded-[12px]
               bg-[var(--md-sys-color-on-primary)]/5
               border border-[var(--md-sys-color-on-primary)]/10
               backdrop-blur-sm transition-all duration-300
               hover:bg-[var(--md-sys-color-on-primary)]/10
               shadow-sm">
                    <!-- keep your timer markup/component here -->
                    <x-dashboard.navbar.timer/>
                </div>
            </div>
        </div>

        <!-- Status (right-most column) -->
        <div class="flex justify-end" title="وضعیت حضور">
            <div class="hidden xl:block">
                <div
                    class="flex items-center justify-center h-[40px] min-w-[176px] gap-3 px-4
               rounded-[12px]
               bg-[var(--md-sys-color-on-primary)]/5
               border border-[var(--md-sys-color-on-primary)]/10
               backdrop-blur-sm transition-all duration-300
               hover:bg-[var(--md-sys-color-on-primary)]/10
               shadow-sm">
                    <livewire:dashboard.navbar.status-switcher/>
                </div>
            </div>
        </div>
    </div>
    <div class="hidden xl:block w-px h-6 bg-[var(--md-sys-color-on-primary)]/15 mx-2"></div>

    {{--    2nd section--}}
    <div class="flex items-center gap-2">
        <!-- fullscreen  -->
        <div title="حالت تمام صفحه">
            <x-dashboard.navbar.fullscreen/>
        </div>

        <!-- birthday and anniversary pills  -->
        @auth
            <x-dashboard.navbar.pills :user="auth()->user()"/>
        @endauth

        <!-- appearance palette + menu etc remain unchanged -->
        <div title="شخصی‌سازی ظاهر">
            <x-dashboard.navbar.palette/>
        </div>

        <!-- settings -->
        <div title="تنظیمات سریع">
            <livewire:dashboard.navbar.quick-settings/>
        </div>

        <!-- release note -->
        <div title="یادداشت‌های انتشار">
            <x-dashboard.navbar.release-note/>
        </div>

        <!-- notification -->
        <div title="اعلان‌ها">
            <x-dashboard.navbar.notification/>
        </div>
        <div class="w-px h-6 bg-[var(--md-sys-color-on-primary)]/15 mx-1"></div>

        <!-- account menu unchanged -->
        <div title="حساب کاربری">
            <x-dashboard.navbar.account/>
        </div>
    </div>
    <!-- release note modal -->
    <x-dashboard.release-modal/>
</nav>
