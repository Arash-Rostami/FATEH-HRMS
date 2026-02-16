<div class="flex flex-col h-screen overflow-hidden bg-[var(--md-sys-color-background)] transition-colors duration-500">
    <x-dashboard.header.main/>
    <x-dashboard.navbar.top/>
    <x-dashboard.navbar.left/>
hi
    <!-- Main Content Area with Dynamic Component -->
    <x-dashboard.tab.main :activeTab="$activeTab" :direction="$direction" :currentTab="$currentTab" />

    <!-- Right Navbar with active tab prop -->
    <x-dashboard.navbar.right :activeTab="$activeTab" :tabs="$tabs" />
    <x-dashboard.navbar.mobile/>

    <!-- Animation Styles -->
    <style>
        .animate-slide-up {
            animation: slideUp 0.6s cubic-bezier(0.2, 0.0, 0, 1.0) forwards;
        }
        .animate-slide-down {
            animation: slideDown 0.6s cubic-bezier(0.2, 0.0, 0, 1.0) forwards;
        }

        @keyframes slideUp {
            0% { transform: translateY(100%); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }

        @keyframes slideDown {
            0% { transform: translateY(-100%); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }
    </style>
</div>
