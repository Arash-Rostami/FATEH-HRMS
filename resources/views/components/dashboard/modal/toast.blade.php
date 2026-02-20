@props(['timeout' => 3000])

<div
    x-data="{
        show: false,
        message: '',
        type: 'info',
        timeout: {{ $timeout }},
        init() {
            window.addEventListener('toast', event => {
                const detail = event.detail;
                // Support both event.detail.message and direct string
                this.message = detail.message || (typeof detail === 'string' ? detail : '');
                this.type = detail.type || 'info';
                this.show = true;

                // Clear existing timeout if any to reset duration
                if (this._timer) clearTimeout(this._timer);
                this._timer = setTimeout(() => this.show = false, this.timeout);
            });
        }
    }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4 scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
    x-transition:leave-end="opacity-0 translate-y-4 scale-95"
    class="fixed bottom-6 left-6 z-[100] max-w-sm w-full flex items-center gap-4 px-6 py-4 rounded-2xl shadow-xl border backdrop-blur-xl"
    :class="{
        'bg-[var(--md-sys-color-surface-container)] border-[var(--md-sys-color-outline-variant)] text-[var(--md-sys-color-on-surface)]': type === 'info',
        'bg-[var(--md-sys-color-error-container)] border-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error-container)]': type === 'error',
        'bg-[var(--md-sys-color-primary-container)] border-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary-container)]': type === 'success',
        'bg-[var(--md-sys-color-tertiary-container)] border-[var(--md-sys-color-tertiary)] text-[var(--md-sys-color-on-tertiary-container)]': type === 'warning'
    }"
    style="display: none;"
    dir="rtl"
>
    <!-- Icon -->
    <div class="shrink-0 flex items-center justify-center">
        <template x-if="type === 'info'">
            <span class="material-symbols-rounded text-2xl">info</span>
        </template>
        <template x-if="type === 'success'">
            <span class="material-symbols-rounded text-2xl">check_circle</span>
        </template>
        <template x-if="type === 'error'">
            <span class="material-symbols-rounded text-2xl">error</span>
        </template>
        <template x-if="type === 'warning'">
            <span class="material-symbols-rounded text-2xl">warning</span>
        </template>
    </div>

    <!-- Message -->
    <div class="flex-1">
        <p class="text-sm font-bold" x-text="message"></p>
    </div>

    <!-- Close Button -->
    <button @click="show = false" class="shrink-0 p-1.5 rounded-full hover:bg-black/5 dark:hover:bg-white/10 transition-colors">
        <span class="material-symbols-rounded text-lg">close</span>
    </button>
</div>
