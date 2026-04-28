<x-filament-widgets::widget>
    <x-filament::section>
        <x-ui.title
            icon="analytics"
            title="{{ __('آمار و اطلاعات سیستم') }}"
            :count="$this->getActiveStatsCount()"
            countLabel="آیتم آماری"
        />

        <x-filament::tabs label="Content tabs">
            <x-filament::tabs.item
                :active="$activeTab === 'users'"
                wire:click="setTab('users')"
                icon="heroicon-o-users"
            >
                {{ __('کاربران') }}
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$activeTab === 'departments'"
                wire:click="setTab('departments')"
                icon="heroicon-o-building-office"
            >
                {{ __('دپارتمان‌ها') }}
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$activeTab === 'ads'"
                wire:click="setTab('ads')"
                icon="heroicon-o-megaphone"
            >
                {{ __('آگهی‌ها') }}
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$activeTab === 'reports'"
                wire:click="setTab('reports')"
                icon="heroicon-o-document-text"
            >
                {{ __('گزارش‌ها') }}
            </x-filament::tabs.item>
        </x-filament::tabs>

        <div class="mt-6">
            {{ $this->getSchema('stats') }}
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
