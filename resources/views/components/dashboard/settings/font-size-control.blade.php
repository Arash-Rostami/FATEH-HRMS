<div>
    <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-medium opacity-70">اندازه فونت</span>
        <button @click="resetFontSize()"
                x-show="fontSizeLevel !== 0"
                x-transition
                class="text-[9px] px-2 py-0.5 rounded-full bg-red-500/10 text-red-500 hover:bg-red-500/20 transition-colors cursor-pointer">
            بازنشانی
        </button>
        <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-500 font-medium"
              x-show="fontSizeLevel === 0"
              x-text="getScaleLabel()"></span>
    </div>
    <div class="flex items-center gap-1.5 p-1.5 bg-[var(--md-sys-color-surface-container)]/50 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/5">
        <button @click="decreaseFontSize()"
                class="w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-200 active:scale-90 disabled:opacity-20 disabled:cursor-not-allowed group relative overflow-hidden"
                :disabled="fontSizeLevel <= minScale"
                :class="fontSizeLevel <= minScale ? 'bg-transparent' : 'bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-800 shadow-sm hover:shadow-md'">
            <span class="material-symbols-rounded text-[20px] transition-transform group-hover:scale-110">remove</span>
        </button>
        <div class="flex-1 flex items-center justify-center gap-1.5">
            <span class="text-[11px] font-bold opacity-30 scale-90 origin-bottom">A</span>
            <div class="w-px h-4 bg-gradient-to-b from-transparent via-[var(--md-sys-color-outline-variant)]/20 to-transparent"></div>
            <span class="text-lg font-bold opacity-80" style="transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);"
                  :style="`transform: scale(${1 + (fontSizeLevel * 0.1)})`">A</span>
        </div>
        <button @click="increaseFontSize()"
                class="w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-200 active:scale-90 disabled:opacity-20 disabled:cursor-not-allowed group relative overflow-hidden"
                :disabled="fontSizeLevel >= maxScale"
                :class="fontSizeLevel >= maxScale ? 'bg-transparent' : 'bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 shadow-sm hover:shadow-md hover:from-blue-100 hover:to-blue-200 dark:hover:from-blue-900/50 dark:hover:to-blue-800/50'">
            <span class="material-symbols-rounded text-[20px] transition-transform group-hover:scale-110">add</span>
        </button>
    </div>
</div>
