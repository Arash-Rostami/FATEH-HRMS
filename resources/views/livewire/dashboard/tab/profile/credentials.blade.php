<div class="space-y-8">
    <!-- Search Bar -->
    <div class="relative max-w-md mx-auto md:mx-0">
        <x-ui.input
            wire:model.live.debounce.300ms="search"
            label="Search Credentials"
            name="search"
            icon="fas fa-search"
            class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-lg"
        />
    </div>

    <!-- Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($credentials as $cred)
            <x-ui.card class="relative overflow-hidden group hover:scale-[1.02] hover:-translate-y-1 transition-all duration-300 border-t-4 border-t-blue-500 hover:shadow-2xl hover:shadow-blue-500/20">
                <!-- Background Decoration -->
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity duration-500">
                    <i class="fas fa-key text-8xl rotate-12 transform translate-x-4 -translate-y-4"></i>
                </div>

                <div class="flex items-center justify-between mb-6 relative z-10">
                    <div class="flex items-center gap-3">
                         <div class="p-2 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                             <i class="fas fa-shield-alt text-xl"></i>
                         </div>
                         <h3 class="text-xl font-bold text-gray-900 dark:text-white truncate max-w-[150px]" title="{{ $cred->app_name }}">{{ $cred->app_name }}</h3>
                    </div>
                </div>

                <div class="space-y-4 relative z-10">
                    <div class="group/field relative transition-all">
                        <label class="text-[10px] text-gray-400 uppercase tracking-widest font-bold mb-1 ml-1">Username</label>
                        <div class="flex items-center justify-between bg-gray-50/80 dark:bg-black/20 rounded-xl p-2.5 border border-gray-100 dark:border-white/5 hover:border-blue-200 dark:hover:border-blue-500/30 transition-colors">
                            <span class="font-mono text-sm truncate select-all text-gray-700 dark:text-gray-300">{{ $cred->username }}</span>
                            <x-ui.copy-button text="{{ $cred->username }}" class="bg-white dark:bg-white/5 shadow-sm" />
                        </div>
                    </div>

                    <div class="group/field relative transition-all">
                        <label class="text-[10px] text-gray-400 uppercase tracking-widest font-bold mb-1 ml-1">Password</label>
                        <div class="flex items-center justify-between bg-gray-50/80 dark:bg-black/20 rounded-xl p-2.5 border border-gray-100 dark:border-white/5 hover:border-blue-200 dark:hover:border-blue-500/30 transition-colors group-hover:bg-blue-50/30 dark:group-hover:bg-blue-900/10">
                            <span class="font-mono text-sm tracking-[0.2em] text-gray-400">••••••••</span>
                            <x-ui.copy-button text="{{ $cred->password }}" class="bg-white dark:bg-white/5 shadow-sm" />
                        </div>
                    </div>

                    @if($cred->link)
                        <div class="pt-2">
                            <a href="{{ $cred->link }}" target="_blank" class="flex items-center justify-center w-full text-sm font-bold text-white bg-gradient-to-r from-blue-600 to-indigo-600 py-2.5 rounded-xl hover:shadow-lg hover:shadow-blue-500/40 hover:scale-[1.02] transition-all duration-300 active:scale-95">
                                <i class="fas fa-external-link-alt mr-2 text-xs"></i> Launch Application
                            </a>
                        </div>
                    @endif

                    @if($cred->note)
                         <div class="mt-4 p-3 bg-yellow-50 text-yellow-800 rounded-xl text-xs dark:bg-yellow-500/10 dark:text-yellow-400 border border-yellow-100 dark:border-yellow-500/10 flex gap-2">
                            <i class="fas fa-info-circle mt-0.5 flex-shrink-0"></i>
                            <p class="leading-relaxed">{{ Str::limit($cred->note, 60) }}</p>
                         </div>
                    @endif
                </div>
            </x-ui.card>
        @empty
            <div class="col-span-full flex flex-col items-center justify-center py-20 text-center">
                <div class="bg-gray-100 dark:bg-white/5 rounded-full p-6 mb-4 animate-pulse">
                    <i class="fas fa-search text-4xl text-gray-400 dark:text-gray-500"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">No credentials found</h3>
                <p class="text-gray-500 dark:text-gray-400 max-w-sm mx-auto">We couldn't find any credentials matching "{{ $search }}". Try searching for a different app name.</p>
            </div>
        @endforelse
    </div>
</div>
