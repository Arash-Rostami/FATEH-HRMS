<button x-data="fullscreen()" ax-load="idle"
        @click="toggle()"
        class="sm:flex w-10 h-10 rounded-full hover:bg-[var(--md-sys-color-on-primary)]/10 active:scale-95 transition-all duration-200 items-center justify-center">
            <span class="material-symbols-rounded text-[22px]"
                  x-text="isFullscreen ? 'close_fullscreen' : 'fullscreen'"></span>
</button>
