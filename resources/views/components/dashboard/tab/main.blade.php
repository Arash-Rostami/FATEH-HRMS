{{--@props(['activeTab', 'direction' => 'up', 'currentTab'])--}}

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

    <div class="relative w-full h-full flex flex-col overflow-y-auto custom-scrollbar p-4 md:p-6 lg:p-8"
         x-show="show"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-4">


        <livewire:dynamic-component
            :component="$currentTab['component']"
            :key="$activeTab"
            lazy
        />

    </div>

    <x-dashboard.loader.bar wire:loading/>


</main>
