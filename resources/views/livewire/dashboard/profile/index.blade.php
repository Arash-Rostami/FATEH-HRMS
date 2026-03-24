<div class="relative w-full h-full p-4 md:p-8 overflow-y-auto scrollbar-hide"
     x-data="settings"
     x-init="initPattern()"
     dir="rtl">
    <div class="max-w-[88rem] mx-auto">

        <x-dashboard.tab.title icon="person" title="پروفایل کاربری"/>

        <div class="w-full flex flex-col gap-5">
            <!-- Header Card -->
            <div
                class="relative w-full overflow-hidden rounded-2xl bg-[var(--md-sys-color-surface)] shadow-sm border border-[var(--md-sys-color-outline-variant)]/50">
                <!-- Background Pattern -->
                <div class="absolute inset-0 opacity-[0.03]"
                     style="background-image: radial-gradient(circle at 2px 2px, var(--md-sys-color-on-surface) 1px, transparent 0); background-size: 24px 24px;"></div>

                <div
                    class="relative p-6 sm:p-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                    <div class="flex items-center gap-6">
                        <div class="relative group">
                            <div
                                class="absolute inset-0 bg-gradient-to-tr from-[var(--md-sys-color-primary)] to-[var(--md-sys-color-tertiary)] rounded-full blur-md opacity-40 group-hover:opacity-60 transition-opacity duration-500"></div>
                            <img
                                src="{{ $user->profile?->image ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=random' }}"
                                alt="{{ $user->name }}"
                                class="relative w-20 h-20 rounded-full object-cover border-2 border-[var(--md-sys-color-surface)] shadow-md group-hover:scale-105 transition-transform duration-300">
                            <div
                                class="absolute bottom-0 right-0 w-6 h-6 bg-[var(--md-sys-color-primary)] border-2 border-[var(--md-sys-color-surface)] rounded-full flex items-center justify-center shadow-sm"
                                title="وضعیت: فعال">
                                <span
                                    class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-on-primary)]">check</span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-2">
                                <h1 class="text-2xl font-bold tracking-tight text-[var(--md-sys-color-on-surface)]">{{ $user->name }}</h1>
                                <span
                                    class="px-2 py-0.5 rounded-md bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] text-[10px] font-bold tracking-wide uppercase">
                                    {{ $user->profile?->position ?? 'کارمند' }}
                                </span>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 mt-1">
                                <span
                                    class="inline-flex items-center gap-1.5 text-xs text-[var(--md-sys-color-on-surface-variant)]">
                                    <span class="material-symbols-rounded text-[16px] opacity-70">domain</span>
                                    {{ $user->profile?->department?->name ?? 'دپارتمان عمومی' }}
                                </span>
                                |
                                <span
                                    class="inline-flex items-center gap-1.5 text-xs text-[var(--md-sys-color-on-surface-variant)]">
                                    <span class="material-symbols-rounded text-[16px] opacity-70">schedule</span>
                                    عضویت: {{ $user->created_at->diffForHumans(null, true) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="w-full sm:w-64 bg-[var(--md-sys-color-surface-container-low)]/50 rounded-xl p-4 border border-[var(--md-sys-color-outline-variant)]/30">
                        <div class="flex justify-between items-end mb-2">
                            <span
                                class="text-xs font-semibold text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-wider">تکمیل پروفایل</span>
                            <span class="text-lg font-bold text-[var(--md-sys-color-primary)]">{{ $completion }}%</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-[var(--md-sys-color-surface-variant)] overflow-hidden">
                            <div
                                class="h-full rounded-full bg-gradient-to-l from-[var(--md-sys-color-primary)] to-[var(--md-sys-color-tertiary)] transition-all duration-1000 ease-out"
                                style="width: {{ $completion }}%; box-shadow: 0 0 10px color-mix(in srgb, var(--md-sys-color-primary) 40%, transparent)">
                            </div>
                        </div>
                        <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] mt-2 opacity-80 leading-relaxed">
                            لطفاً برای دسترسی کامل به امکانات سیستم، اطلاعات پروفایل خود را تکمیل کنید.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row gap-6 items-start">
                <!-- Sidebar Navigation -->
                <aside
                    class="w-full lg:w-64 flex-shrink-0 flex flex-col gap-1 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] rounded-2xl p-3 shadow-sm sticky top-4 z-10">
                    <div
                        class="flex items-center gap-3 px-3 pt-2 pb-4 border-b border-[var(--md-sys-color-outline-variant)]/40 mb-2">
                        <div
                            class="w-8 h-8 rounded-lg bg-[var(--md-sys-color-secondary-container)] flex items-center justify-center text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-lg">settings_account_box</span>
                        </div>
                        <span
                            class="text-xs font-bold uppercase tracking-wider text-[var(--md-sys-color-on-surface-variant)]">مدیریت حساب</span>
                    </div>

                    @foreach([
                        'info'        => ['label' => 'اطلاعات فردی', 'icon' => 'person',  'sub' => 'مشخصات و تماس'],
                        'documents'   => ['label' => 'مدارک و اسناد',   'icon' => 'folder_open',   'sub' => 'آپلود فایل‌ها'],
                        'credentials' => ['label' => 'دسترسی و امنیتی',       'icon' => 'verified_user',  'sub' => 'نقش‌ها و مجوزها'],
                    ] as $key => $tab)
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
                            <div class="flex flex-col leading-none">
                                <span class="text-sm font-semibold">{{ $tab['label'] }}</span>
                                <span
                                    class="text-[10px] font-normal mt-1 {{ $activeTab === $key ? 'text-[var(--md-sys-color-on-primary)]/80' : 'opacity-60' }}">{{ $tab['sub'] }}</span>
                            </div>
                            @if($activeTab === $key)
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-rounded text-lg opacity-80">chevron_left</span>
                            @endif
                        </button>
                    @endforeach


                    <div class="border-t border-[var(--md-sys-color-outline-variant)]/40 mt-2 pt-2 pb-1">
                        <a href="{{ route('dashboard') }}"
                           title="بازگشت به داشبورد"
                           class="flex items-center gap-3 rounded-xl px-3 py-2.5 bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] hover:brightness-95 active:scale-[0.98] transition-all duration-200 mb-1">
                            <span class="material-symbols-rounded text-xl flex-shrink-0">home</span>
                            <span class="hidden sm:flex flex-col text-right leading-tight">
                                <span class="text-sm font-bold">داشبورد</span>
                                <span class="text-[10px] font-normal opacity-70 mt-0.5">بازگشت به خانه</span>
                            </span>
                        </a>
                        <div
                            class="rounded-xl bg-[var(--md-sys-color-surface-variant)]/40 p-3 border border-[var(--md-sys-color-outline-variant)]/20">
                            <p class="text-[10px] font-bold text-[var(--md-sys-color-on-surface-variant)]/70 uppercase tracking-wider mb-1 inline-block">
                                :آخرین فعالیت:</p>
                            <span
                                class="text-xs mx-2 text-[var(--md-sys-color-on-surface)] font-medium dir-ltr inline-block">
                                {{ $user->last_seen?->diffForHumans() ?? 'هم‌اکنون' }}
                            </span>
                        </div>
                    </div>
                </aside>

                <!-- Main Content Area -->
                <div
                    class="flex-1 w-full min-w-0 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] rounded-2xl p-1 shadow-sm min-h-[600px]">
                    <div
                        class="border-b border-[var(--md-sys-color-outline-variant)]/40 px-4 py-3 flex items-center gap-2 mb-2">
                         <span class="material-symbols-rounded text-[var(--md-sys-color-primary)]">
                            {{ ['info' => 'person', 'documents' => 'folder_open', 'credentials' => 'verified_user'][$activeTab] }}
                         </span>
                        <h2 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">
                            {{ ['info' => 'ویرایش اطلاعات فردی', 'documents' => 'مدیریت مدارک و مستندات', 'credentials' => 'مشاهده دسترسی‌ها'][$activeTab] }}
                        </h2>
                    </div>

                    <div class="p-2 sm:p-4">
                        @if($activeTab === 'info')
                            <div class="animate-fade-in">
                                <livewire:dashboard.profile.info
                                    wire:key="tab-info"/>
                            </div>
                        @elseif($activeTab === 'documents')
                            <div class="animate-fade-in">
                                <livewire:dashboard.profile.documents
                                    wire:key="tab-docs"
                                    lazy="true"/>
                            </div>
                        @elseif($activeTab === 'credentials')
                            <div class="animate-fade-in">
                                <livewire:dashboard.profile.credentials
                                    wire:key="tab-creds"
                                    lazy="true"/>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
            <x-dashboard.modal.confirmation/>
        </div>
    </div>
</div>
