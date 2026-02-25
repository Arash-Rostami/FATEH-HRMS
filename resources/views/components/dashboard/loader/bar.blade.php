<div
    {{ $attributes->merge(['class' => 'fixed inset-0 z-[9999] flex items-center justify-center p-4 overflow-hidden']) }}
    style="background-color: color-mix(in srgb, var(--md-sys-color-surface), transparent 15%); backdrop-filter: blur(12px);"
    dir="rtl">

    <div class="relative flex flex-col items-center w-full max-w-[280px] p-6 md:p-8 rounded-3xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-surface)] shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_85%,transparent)]">

        <div class="flex items-center justify-center w-12 h-12 mb-4 rounded-2xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
            <span class="material-symbols-rounded text-2xl animate-spin font-fill">
                progress_activity
            </span>
        </div>

        <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)] text-center">
            در حال پردازش
        </h2>

        <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] mt-1 mb-6 text-center">
            لطفاً چند لحظه صبر کنید
        </p>

        <div class="relative w-full h-[5px] overflow-hidden rounded-full bg-[var(--md-sys-color-surface-variant)]">
            <div class="absolute right-0 top-0 h-full rounded-full bg-[var(--md-sys-color-primary)]"
                 style="animation: indeterminate-fill 1s cubic-bezier(0.4, 0, 0.2, 1) infinite;"></div>
        </div>

    </div>
</div>

<style>
    @keyframes indeterminate-fill {
        0% { width: 0%; opacity: 1; }
        70% { width: 100%; opacity: 1; }
        90% { width: 100%; opacity: 0; }
        100% { width: 0%; opacity: 0; }
    }
</style>
