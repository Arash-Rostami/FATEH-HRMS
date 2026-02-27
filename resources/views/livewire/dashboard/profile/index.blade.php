<div class="flex flex-col min-h-screen overflow-x-hidden transition-colors duration-500 relative isolate" dir="rtl">
    <div wire:loading wire:target="setTab" class="absolute inset-0 z-50 flex items-center justify-center bg-[var(--md-sys-color-surface)]/50 backdrop-blur-sm">
        <x-dashboard.loader.bar/>
    </div>
    <div class="flex flex-col gap-5 p-4 md:px-6 md:py-8 w-full max-w-7xl mx-auto animate-slideDownFade">
        <div class="relative overflow-hidden rounded-2xl bg-[var(--md-sys-color-primary-container)] border border-[var(--md-sys-color-outline-variant)]/50 shadow-sm px-6 py-6 md:px-8">
            <div class="absolute w-64 h-64 rounded-full blur-3xl opacity-[0.2] bg-[var(--md-sys-color-primary)] -top-20 -right-20 pointer-events-none"></div>
            <div class="absolute w-64 h-64 rounded-full blur-3xl opacity-[0.2] bg-[var(--md-sys-color-tertiary)] -bottom-20 -left-20 pointer-events-none"></div>
            <div class="absolute inset-0 opacity-[0.05] pointer-events-none" style="background-image: repeating-linear-gradient(0deg, var(--md-sys-color-on-primary-container) 0px, transparent 1px, transparent 28px); background-size: 100% 28px;"></div>
            <span class="material-symbols-rounded absolute text-[160px] opacity-[0.04] pointer-events-none top-1/2 -translate-y-1/2 left-10">manage_accounts</span>

            <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex flex-col sm:flex-row items-center gap-5 w-full md:w-auto text-center sm:text-right">
                    <div class="relative h-24 w-24 flex-shrink-0">
                        <svg class="h-full w-full -rotate-90 transform" viewBox="0 0 36 36">
                            <path class="text-[var(--md-sys-color-surface-variant)]" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="2"/>
                            <path class="text-[var(--md-sys-color-primary)] transition-all duration-1000 ease-out" stroke-dasharray="{{ $completion }}, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="filter: drop-shadow(0 0 6px color-mix(in srgb, var(--md-sys-color-primary) 50%, transparent))"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-lg font-bold text-[var(--md-sys-color-on-primary-container)] leading-none">{{ $completion }}</span>
                            <span class="text-[9px] text-[var(--md-sys-color-on-primary-container)]/80 font-medium tracking-widest mt-0.5">%</span>
                        </div>
                    </div>
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold tracking-tight text-[var(--md-sys-color-on-primary-container)]">{{ $user->name }}</h1>
                        <div class="flex items-center justify-center sm:justify-start gap-1.5 text-[var(--md-sys-color-primary)] mt-1.5">
                            <span class="material-symbols-rounded text-sm">work</span>
                            <span class="text-sm font-medium">{{ $user->profile?->position ?? 'تعیین نشده' }}</span>
                        </div>
                        <div class="flex flex-wrap justify-center sm:justify-start items-center gap-2 mt-2">
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-[var(--md-sys-color-on-primary-container)] bg-[var(--md-sys-color-on-primary-container)]/5 px-2.5 py-1 rounded-lg border border-[var(--md-sys-color-on-primary-container)]/10">
                                <span class="material-symbols-rounded text-[14px]">domain</span>
                                {{ $user->profile?->department?->name ?? 'نامشخص' }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-[var(--md-sys-color-on-primary-container)] bg-[var(--md-sys-color-on-primary-container)]/5 px-2.5 py-1 rounded-lg border border-[var(--md-sys-color-on-primary-container)]/10">
                                <span class="material-symbols-rounded text-[14px]">schedule</span>
                                عضویت: {{ $user->created_at->diffForHumans(null, true) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="w-full md:w-64 bg-[var(--md-sys-color-surface)]/60 backdrop-blur-sm p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
                    <div class="flex justify-between text-[10px] font-bold uppercase tracking-widest text-[var(--md-sys-color-on-surface-variant)] mb-2">
                        <span>تکمیل پروفایل</span>
                        <span class="text-[var(--md-sys-color-primary)]">{{ $completion }}%</span>
                    </div>
                    <div class="h-2 w-full rounded-full bg-[var(--md-sys-color-surface-variant)] overflow-hidden">
                        <div class="h-full rounded-full bg-[var(--md-sys-color-primary)] transition-all duration-1000 ease-out relative" style="width: {{ $completion }}%;">
                            <div class="absolute inset-0 bg-white/20 animate-[shimmer_2s_infinite]"></div>
                        </div>
                    </div>
                    <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] mt-2 leading-relaxed font-medium">برای استفاده کامل از امکانات سامانه، اطلاعات خود را تکمیل نمایید.</p>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-5 items-start">
            <aside class="w-full md:w-64 flex flex-col gap-1 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/50 rounded-2xl p-3 shadow-sm md:sticky md:top-24 flex-shrink-0 overflow-x-auto md:overflow-visible">
                <div class="hidden md:flex items-center gap-3 px-3 pt-2 pb-4 mb-2">
                    <div class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center">
                        <span class="material-symbols-rounded text-base">manage_accounts</span>
                    </div>
                    <span class="text-base font-bold text-[var(--md-sys-color-on-surface)]">بخش‌ها</span>
                </div>

                <div class="flex md:flex-col gap-2 min-w-max md:min-w-0">
                    @foreach([
                        'info'        => ['label' => 'اطلاعات پروفایل', 'icon' => 'person',  'sub' => 'مشخصات فردی'],
                        'documents'   => ['label' => 'مدارک و اسناد',   'icon' => 'folder',   'sub' => 'فایل‌ها'],
                        'credentials' => ['label' => 'دسترسی‌ها',       'icon' => 'vpn_key',  'sub' => 'نقش و مجوز'],
                    ] as $key => $tab)
                        <button wire:click="setTab('{{ $key }}')" title="{{ $tab['label'] }}"
                            class="group relative flex items-center gap-3 rounded-xl px-3 py-3 text-right transition-all duration-200 focus:outline-none w-48 md:w-full
                            {{ $activeTab === $key
                                ? 'bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)]'
                                : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60 hover:text-[var(--md-sys-color-on-surface)]' }}">
                            @if($activeTab === $key)
                                <span class="absolute right-0 top-1/2 -translate-y-1/2 h-6 w-[3px] rounded-xl bg-[var(--md-sys-color-primary)] shadow-[0_0_8px_color-mix(in_srgb,var(--md-sys-color-primary)_70%,transparent)]"></span>
                            @endif
                            <span class="material-symbols-rounded text-xl flex-shrink-0 {{ $activeTab === $key ? 'text-[var(--md-sys-color-primary)]' : 'opacity-60 group-hover:opacity-100' }}">
                                {{ $tab['icon'] }}
                            </span>
                            <span class="flex flex-col text-right leading-tight">
                                <span class="text-sm font-bold">{{ $tab['label'] }}</span>
                                <span class="text-[10px] font-medium opacity-70 mt-0.5">{{ $tab['sub'] }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>

                <div class="hidden md:block border-t border-[var(--md-sys-color-outline-variant)]/40 mt-4 pt-4">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] hover:brightness-95 active:scale-[0.98] transition-all duration-200">
                        <span class="material-symbols-rounded text-xl flex-shrink-0">home</span>
                        <span class="flex flex-col text-right leading-tight">
                            <span class="text-sm font-bold">داشبورد</span>
                            <span class="text-[10px] font-medium opacity-80 mt-0.5">بازگشت به خانه</span>
                        </span>
                    </a>
                    <p class="text-[10px] uppercase tracking-widest font-bold text-[var(--md-sys-color-on-surface-variant)]/50 leading-relaxed px-3 pt-4 text-center">
                        آخرین ورود<br>
                        <span class="normal-case tracking-normal font-medium text-[var(--md-sys-color-on-surface-variant)]">{{ $user->last_seen?->diffForHumans() ?? '—' }}</span>
                    </p>
                </div>
            </aside>

            <div class="flex-1 min-w-0 relative min-h-[500px] transition-all duration-300 ease-in-out">
                <div class="flex items-center gap-3 mb-5 px-1">
                    <div class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] flex items-center justify-center">
                        <span class="material-symbols-rounded text-base">
                            {{ ['info' => 'person', 'documents' => 'folder', 'credentials' => 'vpn_key'][$activeTab] }}
                        </span>
                    </div>
                    <h2 class="text-base font-bold text-[var(--md-sys-color-on-surface)]">
                        {{ ['info' => 'اطلاعات پروفایل', 'documents' => 'مدارک و اسناد', 'credentials' => 'دسترسی‌ها'][$activeTab] }}
                    </h2>
                    <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]/50"></div>
                </div>

                @if($activeTab === 'info')
                    <div class="animate-slideDownFade">
                        <livewire:dashboard.profile.info wire:key="tab-info"/>
                    </div>
                @elseif($activeTab === 'documents')
                    <div class="animate-slideDownFade">
                        <livewire:dashboard.profile.documents wire:key="tab-docs" lazy="true"/>
                    </div>
                @elseif($activeTab === 'credentials')
                    <div class="animate-slideDownFade">
                        <livewire:dashboard.profile.credentials wire:key="tab-creds" lazy="true"/>
                    </div>
                @endif
            </div>
        </div>

        <x-dashboard.modal.toast/>
        <x-dashboard.modal.confirmation/>
    </div>
</div>
