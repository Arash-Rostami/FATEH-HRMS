@php
    $navItems = [
        [
            'href' => url('/tasks'),
            'module' => 'taskboard',
            'id' => 'tasks',
            'icon' => 'dashboard',
            'label' => 'برد وظایف',
            'delay' => 'animate-delay-150',
        ],
        [
            'href' => url('/projects'),
            'module' => 'project',
            'id' => 'projects',
            'icon' => 'workspaces',
            'label' => 'پروژه‌ها',
            'delay' => 'animate-delay-200',
        ],
        [
            'href' => url('/dms'),
            'module' => 'dms',
            'id' => 'dms',
            'icon' => 'folder_open',
            'label' => 'مدیریت مستندات',
            'delay' => 'animate-delay-300',
        ],
        [
            'href' => url('/ths'),
            'module' => 'ths',
            'id' => 'ths',
            'icon' => 'support_agent',
            'label' => 'تیکتینگ',
            'delay' => 'animate-delay-500',
        ],
    ];
@endphp

<div x-data="sidebar"
     @touchstart="handleTouchStart"
     @touchmove="handleTouchMove"
     @touchend="handleTouchEnd"
     class="relative">

    <aside @mouseenter="expand"
           @mouseleave="collapse"
           :class="{'w-[84px]': isExpanded, 'w-[8px] md:w-[12px] lg:w-[52px]': !isExpanded}"
           class="fixed left-0 z-50 flex flex-col shrink-0 gap-0.5 pt-8 md:gap-1 md:pt-10 top-[128px] lg:top-[148px] transition-all duration-700 ease-[cubic-bezier(0.22,1,0.36,1)]">

        <div @click="toggleExpand"
             x-show="!isExpanded"
             x-transition:enter="transition-all duration-500 delay-200"
             x-transition:enter-start="opacity-0 scale-0"
             x-transition:enter-end="opacity-100 scale-100"
             class="absolute left-0 flex items-center justify-end w-8 h-16 pr-1.5 transition-all duration-200 border-y border-r cursor-pointer lg:hidden top-1/2 -translate-y-1/2 rounded-r-xl active:scale-95 shadow-[4px_0_20px_var(--md-sys-color-primary)]/10 border-[var(--md-sys-color-outline-variant)]/30 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]/80 hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)]">
            <span class="text-base animate-pulse material-symbols-rounded" style="animation-duration: 2s;">chevron_right</span>
        </div>

        @foreach($navItems as $item)
            <a href="{{ $item['href'] }}"
               target="_blank"
               data-module="{{ $item['module'] }}"
               @mouseenter="setHover('{{ $item['id'] }}')"
               @mouseleave="clearHover()"
               :class="{
                   '-translate-x-full lg:translate-x-0': !isExpanded,
                   'translate-x-0': isExpanded
               }"
               class="relative flex flex-col items-center justify-center min-h-[84px] md:min-h-[100px] px-2 py-5 md:py-6 overflow-hidden cursor-pointer touch-manipulation rounded-r-2xl rounded-l-none lg:translate-x-0 transition-all duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] animate-slide-in-left {{ $item['delay'] }} bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[4px_0_16px_var(--md-sys-color-primary)]/15 active:scale-[0.94] active:shadow-none hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:shadow-[6px_0_24px_var(--md-sys-color-primary)]/20">

                <span class="mb-2 md:mb-2.5 transition-all duration-500 text-[24px] md:text-[28px] material-symbols-rounded"
                      :class="{
                          'scale-110 rotate-[8deg]': isHovered('{{ $item['id'] }}'),
                          'scale-100': !isHovered('{{ $item['id'] }}')
                      }"
                      :style="isActive('{{ $item['id'] }}') ? 'color: var(--md-sys-color-on-primary); filter: drop-shadow(0 0 8px rgba(255,255,255,0.5));' : ''">
                    {{ $item['icon'] }}
                </span>

                <span class="text-[10px] md:text-[14px] leading-tight text-center uppercase rotate-180 [writing-mode:vertical-rl] transition-all duration-300"
                      :class="isHovered('{{ $item['id'] }}') || isActive('{{ $item['id'] }}') ? 'font-bold opacity-100 !tracking-[5px]' : 'font-normal opacity-95 !tracking-[4px]'">
                    {{ $item['label'] }}
                </span>

                <div x-show="isActive('{{ $item['id'] }}')"
                     x-transition:enter="transition-all duration-500 ease-out"
                     x-transition:enter-start="opacity-0 scale-y-0 translate-x-2"
                     x-transition:enter-end="opacity-100 scale-y-100 translate-x-0"
                     class="absolute left-0 w-1.5 h-12 top-1/2 -translate-y-1/2 rounded-r-full bg-[var(--md-sys-color-on-primary)] shadow-[2px_0_12px_var(--md-sys-color-on-primary)]/60"></div>

                <div x-show="isHovered('{{ $item['id'] }}') && !isActive('{{ $item['id'] }}')"
                     x-transition:enter="transition-opacity duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="absolute inset-0 pointer-events-none bg-[var(--md-sys-color-primary-container)]/15"></div>
            </a>
        @endforeach
    </aside>

    <div x-show="isExpanded"
         @click="toggleExpand"
         x-transition:enter="transition-all duration-400"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-all duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 lg:hidden touch-manipulation bg-gradient-to-r from-black/40 via-black/20 to-transparent backdrop-blur-[1px]"></div>
</div>
