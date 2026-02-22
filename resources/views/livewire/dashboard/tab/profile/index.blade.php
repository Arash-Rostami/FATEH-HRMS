<div class="space-y-6">
    <!-- Header: Completion & Stats -->
    <x-ui.card class="bg-gradient-to-br from-blue-600 to-indigo-700 text-white border-0 overflow-hidden relative">
        <!-- Decorative bg elements -->
        <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -ml-16 -mb-16 w-64 h-64 rounded-full bg-blue-400/20 blur-3xl"></div>

        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-6 w-full md:w-auto">
                <div class="relative h-20 w-20 flex-shrink-0">
                     <!-- Circular Progress -->
                     <svg class="h-full w-full -rotate-90 transform transition-all duration-1000 ease-out" viewBox="0 0 36 36">
                        <path class="text-white/10" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="2.5" />
                        <path class="text-white drop-shadow-[0_0_15px_rgba(255,255,255,0.6)] transition-all duration-1000 ease-out" stroke-dasharray="{{ $completion }}, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
                     </svg>
                     <div class="absolute inset-0 flex items-center justify-center font-bold text-lg">{{ $completion }}%</div>
                </div>
                <div class="text-left">
                    <h2 class="text-3xl font-bold tracking-tight">{{ $user->name }}</h2>
                    <div class="flex items-center gap-2 text-blue-100 mt-1">
                        <i class="fas fa-briefcase text-sm opacity-75"></i>
                        <span class="text-sm font-medium">{{ $user->profile?->position ?? 'Position Not Set' }}</span>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-2 gap-4 w-full md:w-auto">
                <div class="px-5 py-3 bg-white/10 rounded-2xl backdrop-blur-md border border-white/10 hover:bg-white/15 transition-colors">
                    <div class="text-[10px] text-blue-200 uppercase tracking-widest font-semibold mb-1">Tenure</div>
                    <div class="text-xl font-bold tracking-tight">{{ $user->created_at->diffForHumans(null, true) }}</div>
                </div>
                <div class="px-5 py-3 bg-white/10 rounded-2xl backdrop-blur-md border border-white/10 hover:bg-white/15 transition-colors">
                    <div class="text-[10px] text-blue-200 uppercase tracking-widest font-semibold mb-1">Department</div>
                    <div class="text-xl font-bold tracking-tight truncate max-w-[120px]" title="{{ $user->profile?->department?->name }}">{{ $user->profile?->department?->name ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
    </x-ui.card>

    <!-- Tabs Navigation -->
    <div class="flex p-1 space-x-1 bg-gray-100/50 hover:bg-gray-100 dark:bg-gray-800/50 dark:hover:bg-gray-800 rounded-xl backdrop-blur-sm transition-colors">
        @foreach(['info' => ['label' => 'Profile Info', 'icon' => 'fas fa-user-circle'], 'documents' => ['label' => 'Documents', 'icon' => 'fas fa-folder-open'], 'credentials' => ['label' => 'Credentials', 'icon' => 'fas fa-key']] as $key => $tab)
            <button
                wire:click="setTab('{{ $key }}')"
                class="w-full flex items-center justify-center gap-2 rounded-lg py-3 text-sm font-medium leading-5 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50
                       {{ $activeTab === $key
                          ? 'bg-white shadow-sm text-blue-600 dark:bg-gray-700 dark:text-white dark:shadow-none ring-1 ring-black/5 dark:ring-white/10 scale-[1.02]'
                          : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-white/50 dark:hover:bg-white/5' }}"
            >
                <i class="{{ $tab['icon'] }} text-lg mb-0.5"></i>
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
