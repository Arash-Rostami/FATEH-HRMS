<div class="flex flex-col min-h-screen overflow-x-hidden transition-colors duration-500 relative isolate">

    <!-- Backdrop Shades -->
    <x-dashboard.tab.background/>

    <!-- Main Content -->
    <x-dashboard.header.main/>
    <x-dashboard.navbar.top/>
    <x-dashboard.navbar.left/>

    <!-- Right Navbar (Passes tabs from Trait) -->
    <x-dashboard.navbar.right :activeTab="$activeTab" :tabs="$this->getTabsProperty()"/>
    <x-dashboard.navbar.mobile :activeTab="$activeTab" :tabs="$this->getTabsProperty()"/>

    <!-- Profile Content Area -->
    <main class="flex-1 flex flex-col lg:flex-row relative overflow-hidden pt-[80px] lg:pt-[100px] lg:pr-[100px] lg:pl-[100px]">
        <!-- Adjusted padding to account for navbars -->

        <div class="w-full max-w-7xl mx-auto p-4 md:p-6 lg:p-8 pb-24 md:pb-12 flex flex-col gap-6">

            <!-- Header & Tabs -->
            <div class="flex flex-col md:flex-row items-center justify-between gap-4 glass-panel p-4 rounded-2xl">
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                            {{ substr($user->name ?? 'User', 0, 1) }}
                        </div>
                        <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-500 border-2 border-white dark:border-gray-800 rounded-full"></div>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-[var(--md-sys-color-on-surface)]">{{ $user->name }}</h1>
                        <p class="text-sm text-[var(--md-sys-color-on-surface-variant)]">{{ $user->profile->position ?? 'کارمند' }}</p>
                    </div>
                </div>

                <!-- Sub Tabs -->
                <div class="flex items-center gap-2 bg-[var(--md-sys-color-surface-variant)]/50 p-1.5 rounded-xl">
                    <button wire:click="setSubTab('info')"
                            class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-300 {{ $activeSubTab === 'info' ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]' }}">
                        <span class="flex items-center gap-2">
                            <span class="material-symbols-rounded text-lg">person</span>
                            <span>اطلاعات فردی</span>
                        </span>
                    </button>
                    <button wire:click="setSubTab('documents')"
                            class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-300 {{ $activeSubTab === 'documents' ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]' }}">
                        <span class="flex items-center gap-2">
                            <span class="material-symbols-rounded text-lg">folder</span>
                            <span>مدارک</span>
                        </span>
                    </button>
                    <button wire:click="setSubTab('credentials')"
                            class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-300 {{ $activeSubTab === 'credentials' ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]' }}">
                        <span class="flex items-center gap-2">
                            <span class="material-symbols-rounded text-lg">password</span>
                            <span>نام‌های کاربری</span>
                        </span>
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="relative min-h-[500px]">
                @if($activeSubTab === 'info')
                    @include('livewire.dashboard.tab.profile.info')
                @elseif($activeSubTab === 'documents')
                    @include('livewire.dashboard.tab.profile.documents')
                @elseif($activeSubTab === 'credentials')
                    @include('livewire.dashboard.tab.profile.credentials')
                @endif
            </div>

        </div>
    </main>

</div>
