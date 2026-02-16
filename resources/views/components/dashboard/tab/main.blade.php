@props(['activeTab', 'direction' => 'up', 'currentTab'])

<main class="flex-1 flex flex-col lg:flex-row relative overflow-hidden"
      x-data="{
          show: false,
          activeTab: @js($activeTab),
          direction: @js($direction),
          init() {
              this.show = true;
              $watch('activeTab', (value) => {
                  this.show = false;
                  setTimeout(() => {
                      this.show = true;
                  }, 50);
              });
          }
      }">

    {{-- The Container for the Dynamic Content --}}
    <div class="relative w-full h-full flex flex-col overflow-y-auto custom-scrollbar p-4 md:p-6 lg:p-8"
         x-show="show"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-4">

        {{-- Dynamic Component Loading --}}
        {{-- We use 'key' to force re-render when tab changes --}}
        {{-- 'lazy' attribute enables Livewire's lazy loading feature --}}
        <livewire:dynamic-component
            :component="$currentTab['component']"
            :key="$activeTab"
            lazy
        />

    </div>

    {{-- Optional: Global Loading Indicator for Tab Switch --}}
    <div wire:loading
         class="absolute inset-0 z-50 flex items-center justify-center bg-[var(--md-sys-color-surface)]/50 backdrop-blur-sm transition-opacity duration-300">
        <div class="flex flex-col items-center gap-3">
            <div class="w-10 h-10 border-4 border-[var(--md-sys-color-primary)]/30 border-t-[var(--md-sys-color-primary)] rounded-full animate-spin"></div>
            <span class="text-sm font-medium text-[var(--md-sys-color-primary)] animate-pulse">Loading...</span>
        </div>
    </div>

</main>
