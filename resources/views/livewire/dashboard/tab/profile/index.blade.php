<div class="space-y-6" dir="rtl">
    <!-- Header: Completion & Stats -->
    <div class="glass-panel overflow-hidden relative rounded-2xl p-6 border border-[var(--md-sys-color-outline-variant)]">
        <!-- Decorative bg elements -->
        <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 rounded-full bg-[var(--md-sys-color-primary)]/20 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -ml-16 -mb-16 w-64 h-64 rounded-full bg-[var(--md-sys-color-tertiary)]/20 blur-3xl"></div>

        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-6 w-full md:w-auto">
                <div class="relative h-20 w-20 flex-shrink-0">
                     <!-- Circular Progress -->
                     <svg class="h-full w-full -rotate-90 transform transition-all duration-1000 ease-out" viewBox="0 0 36 36">
                        <path class="text-[var(--md-sys-color-surface-variant)]" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="2.5" />
                        <path class="text-[var(--md-sys-color-primary)] drop-shadow-[0_0_15px_rgba(var(--md-sys-color-primary),0.6)] transition-all duration-1000 ease-out" stroke-dasharray="{{ $completion }}, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
                     </svg>
                     <div class="absolute inset-0 flex items-center justify-center font-bold text-lg text-[var(--md-sys-color-on-surface)]">{{ $completion }}%</div>
                </div>
                <div class="text-right">
                    <h2 class="text-3xl font-bold tracking-tight text-[var(--md-sys-color-on-surface)]">{{ $user->name }}</h2>
                    <div class="flex items-center gap-2 text-[var(--md-sys-color-primary)] mt-1">
                        <span class="material-symbols-rounded text-sm opacity-75">work</span>
                        <span class="text-sm font-medium">{{ $user->profile?->position ?? 'تعیین نشده' }}</span>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-2 gap-4 w-full md:w-auto">
                <div class="px-5 py-3 bg-[var(--md-sys-color-surface-variant)]/50 rounded-2xl backdrop-blur-md border border-[var(--md-sys-color-outline-variant)]/20 hover:bg-[var(--md-sys-color-surface-variant)] transition-colors">
                    <div class="text-[10px] text-[var(--md-sys-color-primary)] uppercase tracking-widest font-semibold mb-1">سابقه کاری</div>
                    <div class="text-xl font-bold tracking-tight text-[var(--md-sys-color-on-surface)]">{{ $user->created_at->diffForHumans(null, true) }}</div>
                </div>
                <div class="px-5 py-3 bg-[var(--md-sys-color-surface-variant)]/50 rounded-2xl backdrop-blur-md border border-[var(--md-sys-color-outline-variant)]/20 hover:bg-[var(--md-sys-color-surface-variant)] transition-colors">
                    <div class="text-[10px] text-[var(--md-sys-color-primary)] uppercase tracking-widest font-semibold mb-1">واحد سازمانی</div>
                    <div class="text-xl font-bold tracking-tight truncate max-w-[120px] text-[var(--md-sys-color-on-surface)]" title="{{ $user->profile?->department?->name }}">{{ $user->profile?->department?->name ?? 'نامشخص' }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="flex p-1 space-x-1 space-x-reverse bg-[var(--md-sys-color-surface-variant)]/30 rounded-xl backdrop-blur-sm transition-colors border border-[var(--md-sys-color-outline-variant)]/20">
        @foreach(['info' => ['label' => 'اطلاعات پروفایل', 'icon' => 'person'], 'documents' => ['label' => 'مدارک و اسناد', 'icon' => 'folder'], 'credentials' => ['label' => 'دسترسی‌ها', 'icon' => 'vpn_key']] as $key => $tab)
            <button
                wire:click="setTab('{{ $key }}')"
                class="w-full flex items-center justify-center gap-2 rounded-lg py-3 text-sm font-medium leading-5 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/50
                       {{ $activeTab === $key
                          ? 'bg-[var(--md-sys-color-surface)] shadow-sm text-[var(--md-sys-color-primary)] ring-1 ring-[var(--md-sys-color-outline-variant)]/20 scale-[1.02] active-tab-glow'
                          : 'text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] hover:bg-[var(--md-sys-color-on-surface)]/5' }}"
            >
                <span class="material-symbols-rounded text-lg mb-0.5">{{ $tab['icon'] }}</span>
                <span class="hidden sm:inline">{{ $tab['label'] }}</span>
            </button>
        @endforeach
    </div>

    <!-- Content Area with Transition -->
    <div class="relative min-h-[500px] transition-all duration-300 ease-in-out">
         @if($activeTab === 'info')
             <div class="animate-fade-in-up">
                 <livewire:dashboard.tab.profile.info wire:key="tab-info" />
             </div>
         @elseif($activeTab === 'documents')
             <div class="animate-fade-in-up">
                 <livewire:dashboard.tab.profile.documents wire:key="tab-docs" />
             </div>
         @elseif($activeTab === 'credentials')
             <div class="animate-fade-in-up">
                 <livewire:dashboard.tab.profile.credentials wire:key="tab-creds" />
             </div>
         @endif
    </div>
</div>
