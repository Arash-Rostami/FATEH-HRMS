@php( $isProfileTab = $activeTab !== 'onboarding')
<div
    class="relative w-full h-full p-4 md:p-8 overflow-y-auto scrollbar-hide animate-fade"
    x-data="settings()"
    x-init="initPattern()"
    dir="rtl"
>
    <div class="max-w-[88rem] mx-auto">
        <x-ui.placeholder/>

        <x-ui.title
            :icon="$isProfileTab ? 'person' : 'apartment'"
            :title="$isProfileTab ? 'پروفایل کاربری' : 'آنبوردینگ'"
        />

        <div class="w-full flex flex-col gap-5">
            @if($isProfileTab)

                @include('livewire.dashboard.profile.header')

            @endif

            <div class="flex flex-col lg:flex-row gap-6 items-start">

                @include('livewire.dashboard.profile.tabs')

                <div
                    class="flex-1 w-full min-w-0 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] rounded-2xl p-1 shadow-sm min-h-[600px]">
                    <div
                        class="border-b border-[var(--md-sys-color-outline-variant)]/40 px-4 py-3 flex items-center gap-2 mb-2">
                        <span class="material-symbols-rounded text-[var(--md-sys-color-primary)]">
                            {{ $tabs[$activeTab]['icon'] ?? 'person' }}
                        </span>
                        <h2 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">
                            {{ $tabs[$activeTab]['title'] ?? '' }}
                        </h2>
                    </div>

                    <div class="p-2 sm:p-4">
                        @if(isset($tabs[$activeTab]))
                            <div class="animate-fade-in">
                                <livewire:dynamic-component
                                    :component="$tabs[$activeTab]['component']"
                                    :wire:key="$tabs[$activeTab]['key']"
                                    :lazy="$tabs[$activeTab]['lazy']"
                                />
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <x-ui.modals.confirmation/>
        </div>
    </div>
</div>
