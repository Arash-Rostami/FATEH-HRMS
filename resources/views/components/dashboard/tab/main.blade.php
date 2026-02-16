@props(['activeTab', 'direction', 'currentTab'])

<main class="flex-1 flex flex-col lg:flex-row relative overflow-hidden {{ $currentTab['bg'] ?? '' }} transition-colors duration-700 ease-in-out">

    <!-- Dynamic Content Container -->
    <div class="relative w-full h-full overflow-hidden" wire:key="tab-container-{{ $activeTab }}">
        <div
            class="absolute inset-0 w-full h-full p-4 overflow-y-auto custom-scrollbar {{ $direction === 'up' ? 'animate-slide-up' : 'animate-slide-down' }}"
        >
            <!-- Lazy Load the Component -->
            <livewire:dynamic-component
                :component="$currentTab['component']"
                :key="$activeTab"
                lazy
            />
        </div>
    </div>

    <!-- Loader displayed during lazy loading -->
    <div wire:loading class="absolute inset-0 flex items-center justify-center bg-black/10 backdrop-blur-sm z-50 transition-opacity duration-300">
        <x-loader />
    </div>

</main>
