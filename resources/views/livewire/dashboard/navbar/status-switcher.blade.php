<div x-data="{ open: false }" class="relative w-full h-full flex items-center justify-center">
    <button @click="open = !open"
            class="flex items-center gap-2 w-full h-full justify-center outline-none group">

        <span class="material-symbols-rounded text-[20px] transition-colors duration-300"
              :class="{
                  'text-emerald-400': $wire.status === 'onsite',
                  'text-sky-400': $wire.status === 'remote',
                  'text-rose-400': $wire.status === 'busy',
                  'text-amber-400': $wire.status === 'mission'
              }"
              x-text="{
                  'onsite': 'apartment',
                  'remote': 'laptop_chromebook',
                  'busy': 'do_not_disturb_on',
                  'mission': 'flight_takeoff'
              }[$wire.status] ?? 'help'">
        </span>

        <div class="!hidden lg:!block text-xs font-medium tracking-wide uppercase opacity-90 transition-opacity group-hover:opacity-100">
            <span x-show="$wire.status === 'onsite'" x-cloak>در دفتر</span>
            <span x-show="$wire.status === 'remote'" x-cloak>دورکار</span>
            <span x-show="$wire.status === 'busy'" x-cloak>مشغول</span>
            <span x-show="$wire.status === 'mission'" x-cloak>ماموریت</span>
        </div>

        <span class="!hidden lg:!block material-symbols-rounded text-[18px] opacity-50 transition-transform duration-300 group-hover:opacity-80"
              :class="open ? 'rotate-180' : ''">expand_more</span>
    </button>

    <div x-show="open"
         @click.outside="open = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-2 scale-95"
         class="absolute top-[calc(100%+12px)] right-0 p-1.5 rounded-2xl bg-[var(--md-sys-color-surface)] backdrop-blur-xl border border-[var(--md-sys-color-outline-variant)]/10 shadow-2xl z-50 text-[var(--md-sys-color-on-surface)] min-w-[200px]"
         style="display: none;">

        <div class="text-[10px] uppercase tracking-wider opacity-40 font-bold px-3 py-2 mb-1">تغییر وضعیت</div>

        <button wire:click="changeStatus('onsite'); open = false"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors duration-200 hover:bg-[var(--md-sys-color-surface-container-high)]/50 group text-right">
            <div class="w-8 h-8 rounded-lg bg-emerald-500/20 flex items-center justify-center text-emerald-400 group-hover:bg-emerald-500 group-hover:text-white transition-colors duration-200">
                <span class="material-symbols-rounded text-[20px]">apartment</span>
            </div>
            <div class="flex flex-col items-start">
                <span class="text-sm font-medium group-hover:text-emerald-500 text-right">در دفتر</span>
                <span class="text-[10px] opacity-50 group-hover:text-emerald-400/70 text-right">حضور فیزیکی</span>
            </div>
        </button>

        <button wire:click="changeStatus('remote'); open = false"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors duration-200 hover:bg-[var(--md-sys-color-surface-container-high)]/50 group text-right mt-1">
            <div class="w-8 h-8 rounded-lg bg-sky-500/20 flex items-center justify-center text-sky-400 group-hover:bg-sky-500 group-hover:text-white transition-colors duration-200">
                <span class="material-symbols-rounded text-[20px]">laptop_chromebook</span>
            </div>
            <div class="flex flex-col items-start">
                <span class="text-sm font-medium group-hover:text-sky-500 text-right">دورکار</span>
                <span class="text-[10px] opacity-50 group-hover:text-sky-400/70 text-right">کار از منزل</span>
            </div>
        </button>

        <button wire:click="changeStatus('mission'); open = false"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors duration-200 hover:bg-[var(--md-sys-color-surface-container-high)]/50 group text-right mt-1">
            <div class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center text-amber-400 group-hover:bg-amber-500 group-hover:text-white transition-colors duration-200">
                <span class="material-symbols-rounded text-[20px]">flight_takeoff</span>
            </div>
            <div class="flex flex-col items-start">
                <span class="text-sm font-medium group-hover:text-amber-500 text-right">ماموریت</span>
                <span class="text-[10px] opacity-50 group-hover:text-amber-400/70 text-right">خارج از شرکت</span>
            </div>
        </button>

        <button wire:click="changeStatus('busy'); open = false"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors duration-200 hover:bg-[var(--md-sys-color-surface-container-high)]/50 group text-right mt-1">
            <div class="w-8 h-8 rounded-lg bg-rose-500/20 flex items-center justify-center text-rose-400 group-hover:bg-rose-500 group-hover:text-white transition-colors duration-200">
                <span class="material-symbols-rounded text-[20px]">do_not_disturb_on</span>
            </div>
            <div class="flex flex-col items-start">
                <span class="text-sm font-medium group-hover:text-rose-500 text-right">مشغول</span>
                <span class="text-[10px] opacity-50 group-hover:text-rose-400/70 text-right">عدم مزاحمت</span>
            </div>
        </button>
    </div>
</div>
