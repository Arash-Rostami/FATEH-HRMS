<div class="flex flex-col min-h-screen overflow-x-hidden transition-colors duration-500 relative isolate" style="--dock-w: 5.25rem">
    @unless($embed)
        <x-dashboard.background/>

        <x-dashboard.navbars.left/>
    @endunless

    <x-dashboard.tab
        :activeTab="$activeTab"
        :direction="$direction"
        :currentTab="$currentTab"
    />

    @unless($embed)
        <x-dashboard.navbars.right
            :activeTab="$activeTab"
            :tabs="$this->tabs"
        />

        <x-dashboard.navbars.bottom
            :activeTab="$activeTab"
            :tabs="$this->tabs"
        />
    @endunless

</div>
