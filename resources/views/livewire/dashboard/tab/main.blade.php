<div
    class="flex flex-col h-screen overflow-hidden transition-colors duration-500 relative isolate"
    x-data="backgroundManager"
    :class="backgroundEnabled ? 'bg-transparent' : 'bg-[var(--md-sys-color-background)]'"
>
    <!-- Dynamic Background Layer -->
    <div
        class="absolute inset-0 -z-10 pointer-events-none overflow-hidden"
        x-show="backgroundEnabled"
        x-transition:enter="transition ease-out duration-1000"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-500"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        style="display: none;"
    >
        <template x-for="(src, index) in images" :key="index">
            <div class="absolute inset-0 bg-cover bg-center transition-transform duration-700 ease-[cubic-bezier(0.4,0.0,0.2,1)]"
                 :class="getClasses(index)"
                 :style="`background-image: url('${src}')`">
            </div>
        </template>

        <!-- Glass/Overlay Effect -->
        <div class="absolute inset-0 bg-[var(--md-sys-color-background)]/85 backdrop-blur-[1px]"></div>
    </div>

    <!-- Main Content -->
    <x-dashboard.header.main/>
    <x-dashboard.navbar.top/>
    <x-dashboard.navbar.left/>
    <x-dashboard.tab.main :activeTab="$activeTab" :direction="$direction" :currentTab="$currentTab"/>
    <x-dashboard.navbar.right :activeTab="$activeTab" :tabs="$tabs"/>
    <x-dashboard.navbar.mobile :activeTab="$activeTab" :tabs="$tabs"/>
</div>
