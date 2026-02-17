<div class="flex flex-col min-h-screen overflow-x-hidden transition-colors duration-500 relative isolate">

    <!-- Backdrop Shades -->
    <x-dashboard.tab.background/>

    <!-- Main Content -->
    <x-dashboard.header.main/>
    <x-dashboard.navbar.top/>
    <x-dashboard.navbar.left/>
    <x-dashboard.tab.main :activeTab="$activeTab" :direction="$direction" :currentTab="$currentTab"/>
    <x-dashboard.navbar.right :activeTab="$activeTab" :tabs="$tabs"/>
    <x-dashboard.navbar.mobile :activeTab="$activeTab" :tabs="$tabs"/>

</div>
