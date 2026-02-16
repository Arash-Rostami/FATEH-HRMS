<div class="flex flex-col h-screen overflow-hidden bg-[var(--md-sys-color-background)] transition-colors duration-500">
    <x-dashboard.header.main/>
    <x-dashboard.navbar.top/>
    <x-dashboard.navbar.left/>
    <x-dashboard.tab.main :activeTab="$activeTab" :direction="$direction" :currentTab="$currentTab"/>
    <x-dashboard.navbar.right :activeTab="$activeTab" :tabs="$tabs"/>
    <x-dashboard.navbar.mobile :activeTab="$activeTab" :tabs="$tabs"/>
</div>
