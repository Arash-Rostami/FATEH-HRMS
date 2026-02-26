<div class="flex flex-col min-h-screen overflow-x-hidden transition-colors duration-500 relative isolate">
    <x-dashboard.tab.background/>

    <x-dashboard.navbar.left/>
    <x-dashboard.tab.main :activeTab="$activeTab" :direction="$direction" :currentTab="$currentTab"/>
    <x-dashboard.navbar.right :activeTab="$activeTab" :tabs="$this->tabs"/>
    <x-dashboard.navbar.mobile :activeTab="$activeTab" :tabs="$this->tabs"/>
</div>
