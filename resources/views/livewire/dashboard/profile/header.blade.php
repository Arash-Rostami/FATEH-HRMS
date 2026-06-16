<div
    class="relative w-full overflow-hidden rounded-2xl bg-[var(--md-sys-color-surface)] shadow-sm border border-[var(--md-sys-color-outline-variant)]/50">
    <div class="absolute inset-0 opacity-[0.03]"
         style="background-image: radial-gradient(circle at 2px 2px, var(--md-sys-color-on-surface) 1px, transparent 0); background-size: 24px 24px;"></div>

    <div
        class="relative p-6 sm:p-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
        <div class="flex items-center gap-6">
            <div class="relative group">
                <div
                    class="absolute inset-0 bg-gradient-to-tr from-[var(--md-sys-color-primary)] to-[var(--md-sys-color-tertiary)] rounded-2xl opacity-40 group-hover:opacity-60 transition-opacity duration-500"></div>

                <x-ui.avatar
                    title="تصویر پروفایل"
                    :existingImage="$avatarImage ?? null"
                    :alt="$user->name"
                    class="relative !w-20 !h-20 rounded-2xl border-2 border-[var(--md-sys-color-surface)] shadow-md group-hover:scale-105 transition-all hover:grayscale duration-500"
                />

                <div
                    class="absolute bottom-0 right-0 w-6 h-6 bg-[var(--md-sys-color-primary)] border-2 border-[var(--md-sys-color-surface)] rounded-full flex items-center justify-center shadow-sm"
                    title="وضعیت: فعال"
                >
                    <span
                        class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-on-primary)]">check</span>
                </div>
            </div>

            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold tracking-tight text-[var(--md-sys-color-on-surface)]">
                        {{ $user->name }}
                    </h1>
                    <span
                        class="px-2 py-0.5 rounded-md bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] text-[10px] font-bold tracking-wide uppercase">
                                        {{ $position }}
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-3 mt-1">
                                    <span
                                        class="inline-flex items-center gap-1.5 text-xs text-[var(--md-sys-color-on-surface-variant)]">
                                        <span class="material-symbols-rounded text-[16px] opacity-70">domain</span>
                                        {{ $departmentName }}
                                    </span>
                    |
                    <span class="inline-flex items-center gap-1.5 text-xs text-[var(--md-sys-color-on-surface-variant)]">
                                        <span class="material-symbols-rounded text-[16px] opacity-70">schedule</span>
                        مدت همکاری:
                        {{ $memberSince }}
                    </span>
                </div>
            </div>
        </div>

        <div
            class="w-full sm:w-64 bg-[var(--md-sys-color-primary-container)]/50 rounded-xl p-4 border border-[var(--md-sys-color-outline-variant)]/30">
            <div class="flex justify-between items-end mb-2">
                                <span
                                    class="text-xs font-semibold text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wider">
                                    تکمیل پروفایل
                                </span>
                <span class="text-lg font-bold text-[var(--md-sys-color-primary)]">
                                    {{ $completion }}%
                                </span>
            </div>

            <div
                class="h-2 w-full rounded-full bg-[var(--md-sys-color-surface-variant)] overflow-hidden">
                <div
                    class="h-full rounded-full bg-gradient-to-l from-[var(--md-sys-color-primary)] to-[var(--md-sys-color-tertiary)] transition-all duration-1000 ease-out"
                    style="width: {{ $completion }}%; box-shadow: 0 0 10px color-mix(in srgb, var(--md-sys-color-primary) 40%, transparent)"
                ></div>
            </div>

            <p class="text-[11px] sm:text-xs text-[var(--md-sys-color-on-surface-variant)] mt-2.5 opacity-80 leading-relaxed">
                لطفاً برای دسترسی کامل به امکانات سیستم،

                <a href="?activeTab=info"
                   class="inline-flex items-center gap-1 text-[var(--md-sys-color-primary)] font-medium
                                hover:underline focus:outline-none focus-visible:ring-2
                                focus-visible:ring-[var(--md-sys-color-primary)] rounded"
                >
                    اطلاعات پروفایل
                    <span class="material-symbols-rounded text-[14px]">arrow_right</span>
                </a>
                را تکمیل کنید.
            </p>
        </div>
    </div>
</div>
