<div class="flex flex-col min-h-screen overflow-x-hidden transition-colors duration-500 relative isolate">

    <x-dashboard.header.main/>
    <x-dashboard.navbar.top/>

    <div dir="rtl" class="flex flex-col gap-6 p-4 md:p-8">
        <div class="relative overflow-hidden rounded-xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] shadow-sm px-8 py-6">
            <div class="absolute top-0 right-0 -mr-20 -mt-20 w-80 h-80 rounded-xl bg-[var(--md-sys-color-primary)]/10 blur-3xl pointer-events-none"></div>
            <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 rounded-xl bg-[var(--md-sys-color-tertiary)]/10 blur-3xl pointer-events-none"></div>
            <div class="absolute inset-0 opacity-[0.03] pointer-events-none"
                 style="background-image: repeating-linear-gradient(0deg, var(--md-sys-color-on-surface) 0px, transparent 1px, transparent 28px); background-size: 100% 28px;"></div>

            <div class="relative z-10 flex flex-col sm:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-5">
                    <div class="relative h-24 w-24 flex-shrink-0">
                        <svg class="h-full w-full -rotate-90 transform" viewBox="0 0 36 36">
                            <path class="text-[var(--md-sys-color-surface-variant)]"
                                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                  fill="none" stroke="currentColor" stroke-width="2"/>
                            <path class="text-[var(--md-sys-color-primary)] transition-all duration-1000 ease-out"
                                  stroke-dasharray="{{ $completion }}, 100"
                                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                  fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                  style="filter: drop-shadow(0 0 6px color-mix(in srgb, var(--md-sys-color-primary) 50%, transparent))"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-lg font-bold text-[var(--md-sys-color-on-surface)] leading-none">{{ $completion }}</span>
                            <span class="text-[9px] text-[var(--md-sys-color-on-surface-variant)] font-medium tracking-widest mt-0.5">%</span>
                        </div>
                    </div>

                    <div class="text-right">
                        <h1 class="text-2xl font-bold tracking-tight text-[var(--md-sys-color-on-surface)]">{{ $user->name }}</h1>
                        <div class="flex items-center gap-1.5 text-[var(--md-sys-color-primary)] mt-1 justify-end">
                            <span class="text-sm font-medium">{{ $user->profile?->position ?? 'تعیین نشده' }}</span>
                            <span class="material-symbols-rounded text-sm opacity-70">work</span>
                        </div>
                        <div class="flex items-center gap-1.5 mt-1.5 justify-end">
                            <span class="inline-flex items-center gap-1 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] bg-[var(--md-sys-color-surface-variant)]/60 px-2.5 py-0.5 rounded-xl border border-[var(--md-sys-color-outline-variant)]/30">
                                <span class="material-symbols-rounded text-[11px]">domain</span>
                                {{ $user->profile?->department?->name ?? 'نامشخص' }}
                            </span>
                            <span class="inline-flex items-center gap-1 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] bg-[var(--md-sys-color-surface-variant)]/60 px-2.5 py-0.5 rounded-xl border border-[var(--md-sys-color-outline-variant)]/30">
                                <span class="material-symbols-rounded text-[11px]">schedule</span>
                                {{ $user->created_at->diffForHumans(null, true) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="w-full sm:w-56">
                    <div class="flex justify-between text-[10px] font-semibold uppercase tracking-widest text-[var(--md-sys-color-on-surface-variant)] mb-2">
                        <span>تکمیل پروفایل</span>
                        <span class="text-[var(--md-sys-color-primary)]">{{ $completion }}%</span>
                    </div>
                    <div class="h-1.5 w-full rounded-xl bg-[var(--md-sys-color-surface-variant)]">
                        <div class="h-full rounded-xl bg-[var(--md-sys-color-primary)] transition-all duration-1000 ease-out"
                             style="width: {{ $completion }}%; box-shadow: 0 0 8px color-mix(in srgb, var(--md-sys-color-primary) 60%, transparent)">
                        </div>
                    </div>
                    <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] mt-2 text-left leading-relaxed">
                        برای تکمیل پروفایل بخش‌های ناقص را پر کنید
                    </p>
                </div>
            </div>
        </div>

        <div class="flex gap-5 items-start">

            <aside class="flex-shrink-0 w-16 sm:w-52 flex flex-col gap-1
                          bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]
                          rounded-xl p-2 shadow-sm sticky top-4">

                <div class="hidden sm:flex items-center gap-2 px-3 pt-2 pb-3 border-b border-[var(--md-sys-color-outline-variant)]/40 mb-1">
                    <span class="material-symbols-rounded text-base text-[var(--md-sys-color-primary)]">manage_accounts</span>
                    <span class="text-[10px] font-bold uppercase tracking-[0.18em] text-[var(--md-sys-color-on-surface-variant)]">بخش‌ها</span>
                </div>

                @foreach([
                    'info'        => ['label' => 'اطلاعات پروفایل', 'icon' => 'person',  'sub' => 'مشخصات فردی'],
                    'documents'   => ['label' => 'مدارک و اسناد',   'icon' => 'folder',   'sub' => 'فایل‌ها'],
                    'credentials' => ['label' => 'دسترسی‌ها',       'icon' => 'vpn_key',  'sub' => 'نقش و مجوز'],
                ] as $key => $tab)
                    <button
                        wire:click="setTab('{{ $key }}')"
                        title="{{ $tab['label'] }}"
                        class="group relative flex items-center gap-3 rounded-xl px-3 py-3 text-right transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/40
                            {{ $activeTab === $key
                                ? 'bg-[var(--md-sys-color-primary)]/10 text-[var(--md-sys-color-primary)] shadow-inner'
                                : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60 hover:text-[var(--md-sys-color-on-surface)]' }}"
                    >
                        @if($activeTab === $key)
                            <span class="absolute right-0 top-1/2 -translate-y-1/2 h-6 w-[3px] rounded-xl bg-[var(--md-sys-color-primary)]"
                                  style="box-shadow: 0 0 8px color-mix(in srgb, var(--md-sys-color-primary) 70%, transparent)"></span>
                        @endif
                        <span class="material-symbols-rounded text-xl flex-shrink-0 {{ $activeTab === $key ? 'text-[var(--md-sys-color-primary)]' : 'opacity-60 group-hover:opacity-100' }}">
                            {{ $tab['icon'] }}
                        </span>
                        <span class="hidden sm:flex flex-col text-right leading-tight">
                            <span class="text-sm font-semibold">{{ $tab['label'] }}</span>
                            <span class="text-[10px] font-normal opacity-60 mt-0.5">{{ $tab['sub'] }}</span>
                        </span>
                    </button>
                @endforeach

                {{-- Home button — primary-container fills so it stands apart from neutral tab items --}}
                <div class="border-t border-[var(--md-sys-color-outline-variant)]/40 mt-2 pt-2 pb-1">
                    <a href="{{ route('dashboard') }}"
                       title="بازگشت به داشبورد"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5
                              bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]
                              hover:brightness-95 active:scale-[0.98] transition-all duration-200">
                        <span class="material-symbols-rounded text-xl flex-shrink-0">home</span>
                        <span class="hidden sm:flex flex-col text-right leading-tight">
                            <span class="text-sm font-bold">داشبورد</span>
                            <span class="text-[10px] font-normal opacity-70 mt-0.5">بازگشت به خانه</span>
                        </span>
                    </a>
                    <p class="hidden sm:block text-[9px] uppercase tracking-[0.15em] font-bold text-[var(--md-sys-color-on-surface-variant)]/50 leading-relaxed px-3 pt-3">
                        آخرین ورود<br>
                        <span class="normal-case tracking-normal font-normal text-[var(--md-sys-color-on-surface-variant)]/70">{{ $user->last_seen?->diffForHumans() ?? '—' }}</span>
                    </p>
                </div>
            </aside>

            <div class="flex-1 min-w-0 relative min-h-[500px] transition-all duration-300 ease-in-out">
                <div class="flex items-center gap-2 mb-4 px-1">
                    <span class="material-symbols-rounded text-base text-[var(--md-sys-color-primary)]">
                        {{ ['info' => 'person', 'documents' => 'folder', 'credentials' => 'vpn_key'][$activeTab] }}
                    </span>
                    <span class="text-xs font-semibold text-[var(--md-sys-color-on-surface-variant)] tracking-wide">
                        {{ ['info' => 'اطلاعات پروفایل', 'documents' => 'مدارک و اسناد', 'credentials' => 'دسترسی‌ها'][$activeTab] }}
                    </span>
                    <span class="text-[var(--md-sys-color-outline-variant)] text-xs mx-1">/</span>
                    <span class="text-xs text-[var(--md-sys-color-on-surface-variant)]/50">{{ $user->name }}</span>
                </div>

                @if($activeTab === 'info')
                    <div class="animate-fade-in-up">
                        <livewire:dashboard.tab.info wire:key="tab-info" lazy="true"/>
                    </div>
                @elseif($activeTab === 'documents')
                    <div class="animate-fade-in-up">
                        <livewire:dashboard.tab.documents wire:key="tab-docs" lazy="true"/>
                    </div>
                @elseif($activeTab === 'credentials')
                    <div class="animate-fade-in-up">
                        <livewire:dashboard.tab.credentials wire:key="tab-creds" lazy="true"/>
                    </div>
                @endif
            </div>

        </div>

        <x-dashboard.modal.toast/>
    </div>
</div>
