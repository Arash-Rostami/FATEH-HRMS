<div class="flex flex-col min-h-screen overflow-x-hidden transition-colors duration-500 relative isolate">
    <x-dashboard.background/>

    <x-dashboard.navbars.left/>

    <x-dashboard.tab
        :activeTab="$activeTab"
        :direction="$direction"
        :currentTab="$currentTab"
    />

    <x-dashboard.navbars.right
        :activeTab="$activeTab"
        :tabs="$this->tabs"
    />

    <x-dashboard.navbars.bottom
        :activeTab="$activeTab"
        :tabs="$this->tabs"
    />

</div>
