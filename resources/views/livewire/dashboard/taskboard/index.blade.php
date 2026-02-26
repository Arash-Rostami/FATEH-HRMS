<div dir="rtl" x-data="taskboard"
     class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto"
     style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;">
    <div class="max-w-8xl mx-auto page-wrapper">
        <!-- Sections with staggered animation -->
        <div class="max-w-[85rem] mx-auto space-y-6">
            @include('livewire.dashboard.taskboard.partials.header')
            @include('livewire.dashboard.taskboard.partials.tabs')
            @include('livewire.dashboard.taskboard.partials.board')
        </div>
    </div>

    <!-- Modals -->
    @include('livewire.dashboard.taskboard.partials.create')
    @include('livewire.dashboard.taskboard.partials.edit')
    <x-dashboard.modal.confirmation/>
    <x-dashboard.modal.toast/>
    <style>
        /* Staggered entrance animation for page sections */
        @keyframes slide-up-fade {
            from {
                opacity: 0;
                transform: translateY(14px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-wrapper > div > *:nth-child(1) {
            animation: slide-up-fade 0.45s cubic-bezier(0.4, 0, 0.2, 1) both;
            animation-delay: 0.05s;
        }

        .page-wrapper > div > *:nth-child(2) {
            animation: slide-up-fade 0.45s cubic-bezier(0.4, 0, 0.2, 1) both;
            animation-delay: 0.12s;
        }

        .page-wrapper > div > *:nth-child(3) {
            animation: slide-up-fade 0.45s cubic-bezier(0.4, 0, 0.2, 1) both;
            animation-delay: 0.19s;
        }

        /* Card entrance animation (for board columns) */
        .page-wrapper > div > *:nth-child(3) > div > *:nth-child(1) {
            animation: slide-up-fade 0.45s cubic-bezier(0.4, 0, 0.2, 1) both;
            animation-delay: 0.26s;
        }

        .page-wrapper > div > *:nth-child(3) > div > *:nth-child(2) {
            animation: slide-up-fade 0.45s cubic-bezier(0.4, 0, 0.2, 1) both;
            animation-delay: 0.33s;
        }

        .page-wrapper > div > *:nth-child(3) > div > *:nth-child(3) {
            animation: slide-up-fade 0.45s cubic-bezier(0.4, 0, 0.2, 1) both;
            animation-delay: 0.40s;
        }
    </style>
</div>

