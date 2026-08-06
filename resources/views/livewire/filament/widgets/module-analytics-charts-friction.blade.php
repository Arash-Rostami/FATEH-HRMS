<x-filament-widgets::widget>
    <x-filament::card>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 font-['Yekan_Bakh',_'Tahoma']">
                {{ __('شاخص اصطکاک و تاخیر بین واحدها') }}
            </h2>
            @if ($filter)
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="filter" class="font-['Yekan_Bakh',_'Tahoma']">
                        <option value="module_a">شاخص پیش‌بینی فرسودگی سرمایه انسانی</option>
                        <option value="module_b">شاخص اصطکاک و تاخیر بین واحدها</option>
                        <option value="module_c">قیف پیشرفت نوآوری و پیشنهادات</option>
                        <option value="module_d">پراکندگی و تراکم استفاده از منابع</option>
                        <option value="module_e">توزیع بار کاری (وظایف و تیکت‌ها)</option>
                        <option value="module_f">ترکیب جمعیتی و وضعیت اشتغال</option>
                        <option value="module_g">روند تعاملات و تولید محتوا</option>
                        <option value="module_h">تراکم گزارشات و نظارت پذیری</option>
                        <option value="module_i">قیف جذب و آنبوردینگ نیروها</option>
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            @endif
        </div>

        @if(!empty($description))
            <div class="mb-6 px-4 py-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-600 dark:text-gray-300 font-['Yekan_Bakh',_'Tahoma'] leading-relaxed">
                    {{ $description }}
                </p>
            </div>
        @endif

        @if(empty($frictionData))
            <div class="flex items-center justify-center p-6 text-gray-500 dark:text-gray-400">
                <div class="text-center">
                    <x-filament::icon
                        icon="heroicon-o-x-circle"
                        class="mx-auto h-12 w-12 text-gray-400"
                    />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100 font-['Yekan_Bakh',_'Tahoma']">داده‌ای برای نمایش وجود ندارد</h3>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" dir="rtl">
                @foreach ($frictionData as $data)
                    @php
                        $avgTime = floatval($data->avg_resolution_time);
                        $opacity = min(max($avgTime / 48, 0.1), 1);
                        $textColor = $opacity > 0.5 ? 'text-white' : 'text-gray-900 dark:text-gray-100';
                    @endphp
                    <div
                        class="p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 transition-all"
                        style="background-color: rgba(239, 68, 68, {{ $opacity }});"
                    >
                        <div class="flex flex-col space-y-2">
                            <div class="flex justify-between items-center {{ $textColor }}">
                                <span class="text-xs font-semibold tracking-wider opacity-80 font-['Yekan_Bakh',_'Tahoma']">مبدا (ایجادکننده)</span>
                                <span class="text-sm font-bold font-['Yekan_Bakh',_'Tahoma']">{{ $data->origin }}</span>
                            </div>
                            <div class="flex justify-between items-center {{ $textColor }}">
                                <span class="text-xs font-semibold tracking-wider opacity-80 font-['Yekan_Bakh',_'Tahoma']">مقصد (پاسخگو)</span>
                                <span class="text-sm font-bold font-['Yekan_Bakh',_'Tahoma']">{{ $data->destination }}</span>
                            </div>
                            <div class="mt-4 pt-4 border-t border-white/20 flex justify-between items-center {{ $textColor }}">
                                <span class="text-xs font-medium font-['Yekan_Bakh',_'Tahoma']">میانگین زمان حل</span>
                                <span class="text-lg font-bold font-['Yekan_Bakh',_'Tahoma']" dir="ltr">{{ number_format($avgTime, 1) }} ساعت</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::card>
</x-filament-widgets::widget>
