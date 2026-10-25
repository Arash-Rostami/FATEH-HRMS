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
            'label' => 'مدیریت اسناد',
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

    $ease = 'ease-[cubic-bezier(0.22,1,0.36,1)]';
@endphp

<div x-data="sidebar"
     data-declutter-hide
     @touchstart.passive="handleTouchStart"
     @touchmove="handleTouchMove"
     @touchend.passive="handleTouchEnd"
     class="relative">

    <aside @mouseenter="expand"
           @mouseleave="collapse"
           x-data="{
               ty: 0, raf: 0, until: 0,
               init() { this.upd(); this.$nextTick(() => this.upd()); },
               destroy() { cancelAnimationFrame(this.raf); },
               upd() {
                   const el = this.$el;
                   const f = document.getElementById('footer');
                   const r = el.getBoundingClientRect();
                   const base = r.top - this.ty;
                   const dpr = window.devicePixelRatio || 1;
                   let y;
                   if (window.innerWidth >= 1024) {
                       const nb = document.getElementById('navbar');
                       const vh = document.documentElement.clientHeight;
                       const lo = Math.max(0, nb ? nb.getBoundingClientRect().bottom : 0);
                       const hi = Math.min(vh, f ? f.getBoundingClientRect().top : vh);
                       y = lo + Math.max(0, (hi - lo - r.height) / 2) - base;
                   } else {
                       y = f ? -Math.max(0, base + r.height - f.getBoundingClientRect().top + 8) : 0;
                   }
                   y = Math.round(y * dpr) / dpr;
                   if (y === this.ty) return;
                   this.ty = y;
                   el.style.transform = `translate3d(0,${y}px,0)`;
               },
               kick() {
                   this.until = performance.now() + 450;
                   if (this.raf) return;
                   const step = () => {
                       this.upd();
                       this.raf = performance.now() < this.until ? requestAnimationFrame(step) : 0;
                   };
                   this.raf = requestAnimationFrame(step);
               }
           }"
           @scroll.window.passive="kick()"
           @resize.window.passive="kick()"
           @load.window="upd()"
           :class="{'w-[84px]': isExpanded, 'w-[8px] md:w-[12px] lg:w-[58px]': !isExpanded, 'focus-dim': $store.focus.active}"
           class="fixed left-0 z-50 flex flex-col shrink-0 gap-0.5 pt-8 md:gap-1 md:pt-10 lg:pt-0 top-[128px] lg:top-[148px] will-change-transform transition-[width,opacity] duration-700 {{ $ease }}">

        <div @click="toggleExpand"
             x-show="!isExpanded"
             x-transition:enter="transition-[opacity,transform,scale] duration-500 delay-200 {{ $ease }}"
             x-transition:enter-start="opacity-0 scale-0"
             x-transition:enter-end="opacity-100 scale-100"
             class="group absolute left-0 flex items-center justify-end w-8 h-16 pr-1.5 transition-[transform,translate,scale,background-color,color,border-color,box-shadow] duration-200 {{ $ease }} border-y border-r cursor-pointer lg:hidden top-1/2 -translate-y-1/2 rounded-r-xl active:scale-95 shadow-[4px_0_20px_var(--md-sys-color-primary)]/10 border-[var(--md-sys-color-outline-variant)]/30 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]/80 hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)]">
            <span class="text-base material-symbols-rounded transition-[transform,translate] duration-200 {{ $ease }} group-hover:translate-x-0.5">chevron_right</span>
        </div>

        @foreach($navItems as $item)
            @continue(tenantOff($item['id']))
            <a href="{{ $item['href'] }}"
               target="_blank"
               data-module="{{ $item['module'] }}"
               @mouseenter="setHover('{{ $item['id'] }}')"
               @mouseleave="clearHover()"
               :class="isExpanded ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
               class="relative flex flex-col items-center justify-center min-h-[84px] md:min-h-[100px] lg:min-h-[80px] px-2 py-5 md:py-6 lg:py-[18px] overflow-hidden cursor-pointer touch-manipulation rounded-r-2xl rounded-l-none lg:translate-x-0 will-change-[transform,translate,scale] transition-[transform,translate,scale,background-color,color,box-shadow] duration-500 {{ $ease }} animate-slide-in-left {{ $item['delay'] }} bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[4px_0_16px_var(--md-sys-color-primary)]/15 active:scale-[0.94] active:shadow-none hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:shadow-[6px_0_24px_var(--md-sys-color-primary)]/20">

                <span class="mb-2 md:mb-2.5 lg:mb-1.5 transition-[transform,scale,rotate,color,filter] duration-500 {{ $ease }} text-[24px] md:text-[28px] lg:text-[25px] material-symbols-rounded"
                      :class="isHovered('{{ $item['id'] }}') ? 'scale-110 rotate-[8deg]' : 'scale-100'"
                      :style="isActive('{{ $item['id'] }}') ? 'color: var(--md-sys-color-on-primary); filter: drop-shadow(0 0 8px rgba(255,255,255,0.5));' : ''">
                    {{ $item['icon'] }}
                </span>

                <span class="text-[10px] md:text-[14px] lg:text-[13.5px] leading-tight text-center uppercase rotate-180 [writing-mode:vertical-rl] transition-[opacity,letter-spacing,font-weight] duration-300 {{ $ease }}"
                      :class="isHovered('{{ $item['id'] }}') || isActive('{{ $item['id'] }}') ? 'font-bold opacity-100 !tracking-[5px] lg:!tracking-[3px]' : 'font-normal opacity-95 !tracking-[4px] lg:!tracking-[2px]'">
                    {{ $item['label'] }}
                </span>

                <div x-show="isActive('{{ $item['id'] }}')"
                     x-transition:enter="transition-[opacity,transform,translate,scale] duration-500 {{ $ease }}"
                     x-transition:enter-start="opacity-0 scale-y-0 translate-x-2"
                     x-transition:enter-end="opacity-100 scale-y-100 translate-x-0"
                     x-transition:leave="transition-[opacity,transform,translate,scale] duration-300 {{ $ease }}"
                     x-transition:leave-start="opacity-100 scale-y-100 translate-x-0"
                     x-transition:leave-end="opacity-0 scale-y-0 translate-x-2"
                     class="absolute left-0 w-1.5 h-12 top-1/2 -translate-y-1/2 rounded-r-full bg-[var(--md-sys-color-on-primary)] shadow-[2px_0_12px_var(--md-sys-color-on-primary)]/60"></div>

                <div x-show="isHovered('{{ $item['id'] }}') && !isActive('{{ $item['id'] }}')"
                     x-transition:enter="transition-opacity duration-300 {{ $ease }}"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition-opacity duration-300 {{ $ease }}"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="absolute inset-0 pointer-events-none bg-[var(--md-sys-color-primary-container)]/15"></div>
            </a>
        @endforeach
    </aside>

    <div x-show="isExpanded"
         @click="toggleExpand"
         x-transition:enter="transition-opacity duration-[400ms] {{ $ease }}"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-300 {{ $ease }}"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 lg:hidden touch-manipulation bg-gradient-to-r from-black/40 via-black/20 to-transparent"></div>
</div>
