@php( $isProfileTab = $activeTab !== 'onboarding')
<div
    class="relative w-full h-full p-4 md:p-8 overflow-y-auto scrollbar-hide animate-fade"
    x-data="settings()"
    x-init="initPattern()"
    dir="rtl"
>
    <div class="max-w-[88rem] mx-auto">
        <x-dashboard.placeholder/>

        <x-dashboard.tab.title
            :icon="$isProfileTab ? 'person' : 'apartment'"
            :title="$isProfileTab ? 'پروفایل کاربری' : 'آنبوردینگ'"
        />

        <div class="w-full flex flex-col gap-5">
            @if($isProfileTab)
                <div
                    class="relative w-full overflow-hidden rounded-2xl bg-[var(--md-sys-color-surface)] shadow-sm border border-[var(--md-sys-color-outline-variant)]/50">
                    <div
                        class="absolute inset-0 opacity-[0.03]"
                        style="background-image: radial-gradient(circle at 2px 2px, var(--md-sys-color-on-surface) 1px, transparent 0); background-size: 24px 24px;"
                    ></div>

                    <div
                        class="relative p-6 sm:p-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                        <div class="flex items-center gap-6">
                            <div class="relative group">
                                <div
                                    class="absolute inset-0 bg-gradient-to-tr from-[var(--md-sys-color-primary)] to-[var(--md-sys-color-tertiary)] rounded-2xl opacity-40 group-hover:opacity-60 transition-opacity duration-500"></div>

                                <x-dashboard.avatar
                                    title="تصویر پروفایل"
                                    :existingImage="$avatarImage"
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
                                    <span
                                        class="inline-flex items-center gap-1.5 text-xs text-[var(--md-sys-color-on-surface-variant)]">
                                        <span class="material-symbols-rounded text-[16px] opacity-70">schedule</span>
                                        عضویت: {{ $memberSince }}
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
            @endif

            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <aside
                    class="w-full lg:w-64 flex-shrink-0 flex flex-col gap-1 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] rounded-2xl p-3 shadow-sm sticky top-4 z-10">
                    <div
                        class="flex items-center gap-3 px-3 pt-2 pb-4 border-b border-[var(--md-sys-color-outline-variant)]/40 mb-2">
                        <div
                            class="w-8 h-8 rounded-lg bg-[var(--md-sys-color-secondary-container)] flex items-center justify-center text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-lg">settings_account_box</span>
                        </div>
                        <span
                            class="text-xs font-bold uppercase tracking-wider text-[var(--md-sys-color-on-surface-variant)]">
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

                <div
                    class="flex-1 w-full min-w-0 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] rounded-2xl p-1 shadow-sm min-h-[600px]">
                    <div
                        class="border-b border-[var(--md-sys-color-outline-variant)]/40 px-4 py-3 flex items-center gap-2 mb-2">
                        <span class="material-symbols-rounded text-[var(--md-sys-color-primary)]">
                            {{ $tabs[$activeTab]['icon'] ?? 'person' }}
                        </span>
                        <h2 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">
                            {{ $tabs[$activeTab]['title'] ?? '' }}
                        </h2>
                    </div>

                    <div class="p-2 sm:p-4">
                        @if(isset($tabs[$activeTab]))
                            <div class="animate-fade-in">
                                <livewire:dynamic-component
                                    :component="$tabs[$activeTab]['component']"
                                    :wire:key="$tabs[$activeTab]['key']"
                                    :lazy="$tabs[$activeTab]['lazy']"
                                />
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <x-dashboard.modal.confirmation/>
        </div>
    </div>
</div>
