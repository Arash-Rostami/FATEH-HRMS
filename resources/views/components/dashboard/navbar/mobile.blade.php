<aside class="lg:hidden fixed bottom-6 left-1/2 -translate-x-1/2 w-[90%] max-w-[400px] z-50">
    <div class="bg-[var(--md-sys-color-surface-container-high)] backdrop-blur-2xl border border-[var(--md-sys-color-outline-variant)]/20 rounded-2xl shadow-2xl shadow-[var(--md-sys-color-primary)]/10 p-2 flex justify-between items-center px-6">
        <a href="{{ url('/') }}"
           class="p-2 text-[var(--md-sys-color-primary)] bg-transparent hover:text-[var(--md-sys-color-on-primary-container)] hover:bg-[var(--md-sys-color-primary-container)] rounded-xl transition-all duration-200 active:scale-95">
            <span class="material-symbols-rounded text-[24px]">home</span>
        </a>

        <a href="{{ url('/tasks') }}"
           class="p-2 text-[var(--md-sys-color-primary)] bg-transparent hover:text-[var(--md-sys-color-on-primary-container)] hover:bg-[var(--md-sys-color-primary-container)] rounded-xl transition-all duration-200 active:scale-95">
            <span class="material-symbols-rounded text-[24px]">dashboard</span>
        </a>

        <div class="relative -top-6">
            <button
                class="w-14 h-14 bg-[var(--md-sys-color-primary)] rounded-full flex items-center justify-center shadow-lg shadow-[var(--md-sys-color-primary)]/40 text-[var(--md-sys-color-on-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:scale-105 active:scale-95 transition-all duration-200">
                <span class="material-symbols-rounded text-[28px]">add</span>
            </button>
        </div>

        <a href="{{ url('/documents') }}"
           class="p-2 text-[var(--md-sys-color-primary)] bg-transparent hover:text-[var(--md-sys-color-on-primary-container)] hover:bg-[var(--md-sys-color-primary-container)] rounded-xl transition-all duration-200 active:scale-95">
            <span class="material-symbols-rounded text-[24px]">folder</span>
        </a>

        <a href="{{ url('/profile') }}"
           class="p-2 text-[var(--md-sys-color-primary)] bg-transparent hover:text-[var(--md-sys-color-on-primary-container)] hover:bg-[var(--md-sys-color-primary-container)] rounded-xl transition-all duration-200 active:scale-95">
            <span class="material-symbols-rounded text-[24px]">person</span>
        </a>
    </div>
</aside>
