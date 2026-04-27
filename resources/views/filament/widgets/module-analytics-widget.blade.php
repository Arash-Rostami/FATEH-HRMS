<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('آمار سیستم') }}
        </x-slot>

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
            @if($activeTab === 'users' && !empty($this->usersData))
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-4">
                    <div class="relative p-6 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">کل کاربران</p>
                                <p class="text-3xl font-semibold text-gray-950 dark:text-white mt-2">{{ number_format($this->usersData['total']) }}</p>
                            </div>
                            <div class="p-3 bg-primary-50 dark:bg-primary-500/10 rounded-lg">
                                <x-filament::icon icon="heroicon-o-users" class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                            </div>
                        </div>
                    </div>

                    <div class="relative p-6 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">کاربران فعال</p>
                                <p class="text-3xl font-semibold text-gray-950 dark:text-white mt-2">{{ number_format($this->usersData['active']) }}</p>
                            </div>
                            <div class="p-3 bg-success-50 dark:bg-success-500/10 rounded-lg">
                                <x-filament::icon icon="heroicon-o-check-circle" class="w-6 h-6 text-success-600 dark:text-success-400" />
                            </div>
                        </div>
                    </div>

                    <div class="relative p-6 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">ادمین‌ها</p>
                                <p class="text-3xl font-semibold text-gray-950 dark:text-white mt-2">{{ number_format($this->usersData['admins']) }}</p>
                            </div>
                            <div class="p-3 bg-danger-50 dark:bg-danger-500/10 rounded-lg">
                                <x-filament::icon icon="heroicon-o-shield-check" class="w-6 h-6 text-danger-600 dark:text-danger-400" />
                            </div>
                        </div>
                    </div>

                    <div class="relative p-6 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">کاربران آنلاین</p>
                                <p class="text-3xl font-semibold text-gray-950 dark:text-white mt-2">{{ number_format($this->usersData['online']) }}</p>
                            </div>
                            <div class="p-3 bg-info-50 dark:bg-info-500/10 rounded-lg">
                                <x-filament::icon icon="heroicon-o-signal" class="w-6 h-6 text-info-600 dark:text-info-400" />
                            </div>
                        </div>
                    </div>
                </div>
            @elseif($activeTab === 'departments' && !empty($this->departmentsData))
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3">
                    <div class="relative p-6 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">کل دپارتمان‌ها</p>
                                <p class="text-3xl font-semibold text-gray-950 dark:text-white mt-2">{{ number_format($this->departmentsData['total']) }}</p>
                            </div>
                            <div class="p-3 bg-primary-50 dark:bg-primary-500/10 rounded-lg">
                                <x-filament::icon icon="heroicon-o-building-office" class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                            </div>
                        </div>
                    </div>

                    <div class="relative p-6 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">دارای کاربر</p>
                                <p class="text-3xl font-semibold text-gray-950 dark:text-white mt-2">{{ number_format($this->departmentsData['with_users']) }}</p>
                            </div>
                            <div class="p-3 bg-success-50 dark:bg-success-500/10 rounded-lg">
                                <x-filament::icon icon="heroicon-o-user-group" class="w-6 h-6 text-success-600 dark:text-success-400" />
                            </div>
                        </div>
                    </div>

                    <div class="relative p-6 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">پرتراکم‌ترین دپارتمان</p>
                                <p class="text-xl font-semibold text-gray-950 dark:text-white mt-2 truncate">{{ $this->departmentsData['most_populated'] }}</p>
                            </div>
                            <div class="p-3 bg-warning-50 dark:bg-warning-500/10 rounded-lg">
                                <x-filament::icon icon="heroicon-o-chart-bar" class="w-6 h-6 text-warning-600 dark:text-warning-400" />
                            </div>
                        </div>
                    </div>
                </div>
            @elseif($activeTab === 'ads' && !empty($this->adsData))
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-4">
                    <div class="relative p-6 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">کل آگهی‌ها</p>
                                <p class="text-3xl font-semibold text-gray-950 dark:text-white mt-2">{{ number_format($this->adsData['total']) }}</p>
                            </div>
                            <div class="p-3 bg-primary-50 dark:bg-primary-500/10 rounded-lg">
                                <x-filament::icon icon="heroicon-o-megaphone" class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                            </div>
                        </div>
                    </div>

                    <div class="relative p-6 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">آگهی‌های فعال</p>
                                <p class="text-3xl font-semibold text-gray-950 dark:text-white mt-2">{{ number_format($this->adsData['active']) }}</p>
                            </div>
                            <div class="p-3 bg-success-50 dark:bg-success-500/10 rounded-lg">
                                <x-filament::icon icon="heroicon-o-check" class="w-6 h-6 text-success-600 dark:text-success-400" />
                            </div>
                        </div>
                    </div>

                    <div class="relative p-6 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">آقایان / خانم‌ها</p>
                                <p class="text-3xl font-semibold text-gray-950 dark:text-white mt-2">{{ number_format($this->adsData['males']) }} / {{ number_format($this->adsData['females']) }}</p>
                            </div>
                            <div class="p-3 bg-info-50 dark:bg-info-500/10 rounded-lg flex gap-1">
                                <x-filament::icon icon="heroicon-o-user" class="w-5 h-5 text-info-600 dark:text-info-400" />
                            </div>
                        </div>
                    </div>

                    <div class="relative p-6 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">بدون محدودیت جنسیت</p>
                                <p class="text-3xl font-semibold text-gray-950 dark:text-white mt-2">{{ number_format($this->adsData['any']) }}</p>
                            </div>
                            <div class="p-3 bg-gray-50 dark:bg-gray-500/10 rounded-lg">
                                <x-filament::icon icon="heroicon-o-users" class="w-6 h-6 text-gray-600 dark:text-gray-400" />
                            </div>
                        </div>
                    </div>
                </div>
            @elseif($activeTab === 'reports' && !empty($this->reportsData))
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3">
                    <div class="relative p-6 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">کل گزارش‌ها</p>
                                <p class="text-3xl font-semibold text-gray-950 dark:text-white mt-2">{{ number_format($this->reportsData['total']) }}</p>
                            </div>
                            <div class="p-3 bg-primary-50 dark:bg-primary-500/10 rounded-lg">
                                <x-filament::icon icon="heroicon-o-document-text" class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                            </div>
                        </div>
                    </div>

                    <div class="relative p-6 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">گزارش‌های فعال</p>
                                <p class="text-3xl font-semibold text-gray-950 dark:text-white mt-2">{{ number_format($this->reportsData['active']) }}</p>
                            </div>
                            <div class="p-3 bg-success-50 dark:bg-success-500/10 rounded-lg">
                                <x-filament::icon icon="heroicon-o-document-check" class="w-6 h-6 text-success-600 dark:text-success-400" />
                            </div>
                        </div>
                    </div>

                    <div class="relative p-6 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">دپارتمان با بیشترین گزارش</p>
                                <p class="text-xl font-semibold text-gray-950 dark:text-white mt-2 truncate">{{ $this->reportsData['by_department'] }}</p>
                            </div>
                            <div class="p-3 bg-warning-50 dark:bg-warning-500/10 rounded-lg">
                                <x-filament::icon icon="heroicon-o-chart-bar" class="w-6 h-6 text-warning-600 dark:text-warning-400" />
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
