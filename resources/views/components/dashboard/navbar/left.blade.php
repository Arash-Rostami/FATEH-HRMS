<div x-data="{
    isExpanded: false,
    activeTab: window.location.pathname,
    touchStartX: 0,
    touchStartY: 0,
    isDragging: false
}"
     @touchstart="touchStartX = $event.changedTouches[0].screenX; touchStartY = $event.changedTouches[0].screenY; isDragging = true"
     @touchmove="if (isDragging && Math.abs($event.changedTouches[0].screenY - touchStartY) < 30) $event.preventDefault()"
     @touchend="
    isDragging = false;
    const deltaX = $event.changedTouches[0].screenX - touchStartX;
    const deltaY = Math.abs($event.changedTouches[0].screenY - touchStartY);
    if (deltaY < 50) {
        if (deltaX > 80) isExpanded = true;
        else if (deltaX < -80) isExpanded = false;
    }
"
     class="relative">

    <aside
        :class="{
            'w-[84px]': isExpanded,
            'w-[8px] md:w-[12px] lg:w-[52px]': !isExpanded
        }"
        class="flex flex-col gap-2 md:gap-3 pt-8 md:pt-10 shrink-0 z-50 fixed top-1/2 -translate-y-1/2 left-0 transition-all duration-700 ease-[cubic-bezier(0.22,1,0.36,1)]"
        @mouseenter="!isExpanded && window.innerWidth >= 1024 && (isExpanded = true)"
        @mouseleave="isExpanded && window.innerWidth >= 1024 && (isExpanded = false)">

        <div
            @click="isExpanded = !isExpanded"
            x-show="!isExpanded"
            x-transition:enter="transition-all duration-500 delay-200"
            x-transition:enter-start="opacity-0 scale-0"
            x-transition:enter-end="opacity-100 scale-100"
            class="lg:hidden absolute left-0 top-1/2 -translate-y-1/2 w-8 h-16 rounded-r-2xl flex items-center justify-end pr-1.5 cursor-pointer shadow-[4px_0_20px_var(--md-sys-color-primary)]/10 border-y border-r border-[var(--md-sys-color-outline-variant)]/30 active:scale-95 transition-all duration-200 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]/80 hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)]">
            <span class="material-symbols-rounded text-base animate-pulse" style="animation-duration: 2s;">chevron_right</span>
        </div>

        <a href="{{ url('/tasks') }}"
           x-data="{ hover: false }"
           @mouseenter="hover = true"
           @mouseleave="hover = false"
           class="relative flex flex-col items-center justify-center py-5 md:py-6 px-2
                  rounded-r-2xl rounded-l-none
                  shadow-[4px_0_16px_var(--md-sys-color-primary)]/15
                  transition-all duration-500 ease-[cubic-bezier(0.22,1,0.36,1)]
                  active:scale-[0.94] active:shadow-none
                  cursor-pointer overflow-hidden
                  lg:translate-x-0 touch-manipulation
                  min-h-[80px] md:min-h-[100px]
                  bg-[var(--md-sys-color-primary)]
                  text-[var(--md-sys-color-on-primary)]
                  hover:bg-[var(--md-sys-color-primary-container)]
                  hover:text-[var(--md-sys-color-on-primary-container)]
                  hover:shadow-[6px_0_24px_var(--md-sys-color-primary)]/20"
           :class="{
               '-translate-x-full lg:translate-x-0': !isExpanded,
               'translate-x-0': isExpanded,
               'hover:translate-x-2 lg:hover:translate-x-1': true
           }"
           :style="`transition-delay: ${isExpanded ? '100ms' : '0ms'}`">

            <span class="material-symbols-rounded text-[24px] md:text-[26px] mb-2 transition-all duration-500"
                  :class="{
                      'scale-110 rotate-[8deg]': hover,
                      'scale-100': !hover
                  }"
                  :style="activeTab.includes('tasks') ? 'color: var(--md-sys-color-on-primary); filter: drop-shadow(0 0 8px var(--md-sys-color-on-primary));' : ''">dashboard</span>

            <span class="[writing-mode:vertical-rl] rotate-180 text-[10px] md:text-[11px] font-bold tracking-[0.15em] leading-tight text-center uppercase opacity-95">
                برد وظایف
            </span>

            <div x-show="activeTab.includes('tasks')"
                 x-transition:enter="transition-all duration-500 ease-out"
                 x-transition:enter-start="opacity-0 scale-y-0 translate-x-2"
                 x-transition:enter-end="opacity-100 scale-y-100 translate-x-0"
                 class="absolute left-0 top-1/2 -translate-y-1/2 w-1.5 h-12 rounded-r-full bg-[var(--md-sys-color-on-primary)] shadow-[2px_0_12px_var(--md-sys-color-on-primary)]/60"></div>

            <div x-show="hover && !activeTab.includes('tasks')"
                 x-transition:enter="transition-opacity duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="absolute inset-0 bg-[var(--md-sys-color-primary-container)]/10 pointer-events-none"></div>
        </a>

        <a href="{{ url('/documents') }}"
           x-data="{ hover: false }"
           @mouseenter="hover = true"
           @mouseleave="hover = false"
           class="relative flex flex-col items-center justify-center py-5 md:py-6 px-2
                  rounded-r-2xl rounded-l-none
                  shadow-[4px_0_16px_var(--md-sys-color-primary)]/15
                  transition-all duration-500 ease-[cubic-bezier(0.22,1,0.36,1)]
                  active:scale-[0.94] active:shadow-none
                  cursor-pointer overflow-hidden
                  lg:translate-x-0 touch-manipulation
                  min-h-[80px] md:min-h-[100px]
                  bg-[var(--md-sys-color-primary)]
                  text-[var(--md-sys-color-on-primary)]
                  hover:bg-[var(--md-sys-color-primary-container)]
                  hover:text-[var(--md-sys-color-on-primary-container)]
                  hover:shadow-[6px_0_24px_var(--md-sys-color-primary)]/20"
           :class="{
               '-translate-x-full lg:translate-x-0': !isExpanded,
               'translate-x-0': isExpanded,
               'hover:translate-x-2 lg:hover:translate-x-1': true
           }"
           :style="`transition-delay: ${isExpanded ? '150ms' : '0ms'}`">

            <span class="material-symbols-rounded text-[24px] md:text-[26px] mb-2 transition-all duration-500"
                  :class="{
                      'scale-110 rotate-[8deg]': hover,
                      'scale-100': !hover
                  }"
                  :style="activeTab.includes('documents') ? 'color: var(--md-sys-color-on-primary); filter: drop-shadow(0 0 8px var(--md-sys-color-on-primary));' : ''">folder_managed</span>

            <span class="[writing-mode:vertical-rl] rotate-180 text-[10px] md:text-[11px] font-bold tracking-[0.15em] leading-tight text-center uppercase opacity-95">
                مدیریت مستندات
            </span>

            <div x-show="activeTab.includes('documents')"
                 x-transition:enter="transition-all duration-500 ease-out"
                 x-transition:enter-start="opacity-0 scale-y-0 translate-x-2"
                 x-transition:enter-end="opacity-100 scale-y-100 translate-x-0"
                 class="absolute left-0 top-1/2 -translate-y-1/2 w-1.5 h-12 rounded-r-full bg-[var(--md-sys-color-on-primary)] shadow-[2px_0_12px_var(--md-sys-color-on-primary)]/60"></div>

            <div x-show="hover && !activeTab.includes('documents')"
                 x-transition:enter="transition-opacity duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="absolute inset-0 bg-[var(--md-sys-color-primary-container)]/10 pointer-events-none"></div>
        </a>

        <a href="{{ url('/suggestions') }}"
           x-data="{ hover: false }"
           @mouseenter="hover = true"
           @mouseleave="hover = false"
           class="relative flex flex-col items-center justify-center py-5 md:py-6 px-2
                  rounded-r-2xl rounded-l-none
                  shadow-[4px_0_16px_var(--md-sys-color-primary)]/15
                  transition-all duration-500 ease-[cubic-bezier(0.22,1,0.36,1)]
                  active:scale-[0.94] active:shadow-none
                  cursor-pointer overflow-hidden
                  lg:translate-x-0 touch-manipulation
                  min-h-[80px] md:min-h-[100px]
                  bg-[var(--md-sys-color-primary)]
                  text-[var(--md-sys-color-on-primary)]
                  hover:bg-[var(--md-sys-color-primary-container)]
                  hover:text-[var(--md-sys-color-on-primary-container)]
                  hover:shadow-[6px_0_24px_var(--md-sys-color-primary)]/20"
           :class="{
               '-translate-x-full lg:translate-x-0': !isExpanded,
               'translate-x-0': isExpanded,
               'hover:translate-x-2 lg:hover:translate-x-1': true
           }"
           :style="`transition-delay: ${isExpanded ? '200ms' : '0ms'}`">

            <span class="material-symbols-rounded text-[24px] md:text-[26px] mb-2 transition-all duration-500"
                  :class="{
                      'scale-110 rotate-[8deg]': hover,
                      'scale-100': !hover
                  }"
                  :style="activeTab.includes('suggestions') ? 'color: var(--md-sys-color-on-primary); filter: drop-shadow(0 0 8px var(--md-sys-color-on-primary));' : ''">lightbulb</span>

            <span class="[writing-mode:vertical-rl] rotate-180 text-[10px] md:text-[11px] font-bold tracking-[0.15em] leading-tight text-center uppercase opacity-95">
                پیشنهادات
            </span>

            <div x-show="activeTab.includes('suggestions')"
                 x-transition:enter="transition-all duration-500 ease-out"
                 x-transition:enter-start="opacity-0 scale-y-0 translate-x-2"
                 x-transition:enter-end="opacity-100 scale-y-100 translate-x-0"
                 class="absolute left-0 top-1/2 -translate-y-1/2 w-1.5 h-12 rounded-r-full bg-[var(--md-sys-color-on-primary)] shadow-[2px_0_12px_var(--md-sys-color-on-primary)]/60"></div>

            <div x-show="hover && !activeTab.includes('suggestions')"
                 x-transition:enter="transition-opacity duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="absolute inset-0 bg-[var(--md-sys-color-primary-container)]/10 pointer-events-none"></div>
        </a>
    </aside>

    <div
        x-show="isExpanded"
        @click="isExpanded = false"
        x-transition:enter="transition-all duration-400"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-all duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="lg:hidden fixed inset-0 bg-gradient-to-r from-black/30 via-black/20 to-transparent backdrop-blur-[2px] z-40 touch-manipulation"></div>

</div>
