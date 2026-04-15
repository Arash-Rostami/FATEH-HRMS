<aside
    class="w-full lg:w-64 flex-shrink-0 flex flex-col gap-1 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] rounded-2xl p-3 shadow-sm sticky top-4 z-10">
    <div
        class="flex items-center gap-3 px-3 pt-2 pb-4 border-b border-[var(--md-sys-color-outline-variant)]/40 mb-2">
        <div
            class="w-8 h-8 rounded-lg bg-[var(--md-sys-color-secondary-container)] flex items-center justify-center text-[var(--md-sys-color-on-secondary-container)]">
            <span class="material-symbols-rounded text-lg">settings_account_box</span>
        </div>
        <span class="text-xs font-bold uppercase tracking-wider text-[var(--md-sys-color-on-surface-variant)]">
            مدیریت حساب
        </span>
    </div>

    @foreach($tabs as $key => $tab)
        @if($key === 'onboarding')
            <div class="border-t border-[var(--md-sys-color-outline-variant)]/40 mt-2 pt-2"></div>
        @endif

        <button
            wire:click="setTab('{{ $key }}')"
            class="group relative flex items-center gap-3 rounded-xl px-3 py-3 text-right transition-all duration-200 outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)]
                            {{ $activeTab === $key
                                ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md shadow-[var(--md-sys-color-primary)]/20'
                                : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)]' }}"
        >
                            <span
                                class="material-symbols-rounded text-xl flex-shrink-0 {{ $activeTab === $key ? '' : 'opacity-70 group-hover:opacity-100' }}">
                                {{ $tab['icon'] }}
                            </span>

            <div class="flex flex-col leading-none flex-1">
                <span class="text-sm font-semibold">{{ $tab['label'] }}</span>
                <span
                    class="text-[10px] font-normal mt-1 {{ $activeTab === $key ? 'text-[var(--md-sys-color-on-primary)]/80' : 'opacity-60' }}">
                                    {{ $tab['sub'] }}
                                </span>
            </div>

            @if($key === 'info' && $completion < 100)
                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full
                                    {{ $activeTab === $key
                                        ? 'bg-[var(--md-sys-color-on-primary)]/20 text-[var(--md-sys-color-on-primary)]'
                                        : 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]' }}">
                                    {{ $completion }}%
                                </span>
            @elseif($activeTab === $key)
                <span class="material-symbols-rounded text-lg opacity-80">chevron_left</span>
            @endif
        </button>
    @endforeach

    <div class="border-t border-[var(--md-sys-color-outline-variant)]/40 mt-2 pt-2 pb-1">

        <a href="{{ route('dashboard') }}"
           title="بازگشت به داشبورد"
           class="flex items-center gap-3 rounded-xl px-3 py-2.5 bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] hover:brightness-95 active:scale-[0.98] transition-all duration-200 mb-1"
        >
            <span class="material-symbols-rounded text-xl flex-shrink-0">home</span>
            <span class="hidden sm:flex flex-col text-right leading-tight">
                                <span class="text-sm font-bold">داشبورد</span>
                                <span class="text-[10px] font-normal opacity-70 mt-0.5">بازگشت به خانه</span>
                            </span>
        </a>

        <div
            class="rounded-xl bg-[var(--md-sys-color-surface-variant)]/40 p-3 border border-[var(--md-sys-color-outline-variant)]/20">
            <p class="text-[10px] font-bold text-[var(--md-sys-color-on-surface-variant)]/70 uppercase tracking-wider mb-1">
                آخرین فعالیت:
            </p>
            <span class="text-xs text-[var(--md-sys-color-on-surface)] font-medium dir-ltr">
                                {{ $lastSeen }}
                            </span>
        </div>
    </div>
</aside>
