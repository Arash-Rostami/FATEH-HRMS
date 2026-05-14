@php
    $data       = $getState() ?? ['records_count' => 0, 'overall' => 0, 'categories' => []];
    $records    = $data['records_count'];
    $categories = $data['categories'];
    $overall    = $data['overall'];

    $meta = [
        'physique' => ['emoji' => '🏋️‍♂️', 'label' => 'جسم'],
        'emotion'  => ['emoji' => '❤️',  'label' => 'احساس'],
        'mind'     => ['emoji' => '🧠',  'label' => 'ذهن'],
        'soul'     => ['emoji' => '✨',  'label' => 'روح'],
    ];
@endphp

@if($records === 0)
    <span class="text-sm text-gray-500 dark:text-gray-400">—</span>
@else
    <div class="space-y-4" dir="rtl">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            میانگین کلی نمرات این کاربر در
            <span class="font-medium text-gray-700 dark:text-gray-300 tabular-nums" dir="ltr">{{ number_format($records) }}</span>
            آزمون انجام‌شده:
            <span class="font-medium text-gray-700 dark:text-gray-300 tabular-nums" dir="ltr">{{ number_format($overall, 1) }} / 100</span>
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
            @foreach($categories as $category => $avg)
                @php
                    $width = max(0, min(100, $avg));
                    $emoji = $meta[$category]['emoji'] ?? '•';
                    $label = $meta[$category]['label'] ?? $category;
                @endphp

                <section class="overflow-hidden rounded-2xl ring-1 ring-gray-950/5 dark:ring-white/10 transition-all duration-300 hover:shadow-sm px-6 py-5 bg-gray-50/30 dark:bg-white/5">
                    <div class="flex items-start justify-between gap-4">
                        <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                            <span class="ml-1.5 inline-block">{{ $emoji }}</span> {{ $label }}
                        </h3>

                        <span class="inline-flex items-center rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700 ring-1 ring-inset ring-primary-600/20 dark:bg-primary-500/10 dark:text-primary-400 dark:ring-primary-500/20"
                              dir="ltr">
                            {{ number_format($avg, 1) }} / 100
                        </span>
                    </div>

                    <div class="mt-5 flex w-full h-1.5 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800">
                        <div class="flex flex-col justify-center overflow-hidden bg-primary-600 dark:bg-primary-500 transition-all duration-500 ease-out"
                             style="width: {{ $width }}%"></div>
                    </div>
                </section>
            @endforeach
        </div>
    </div>
@endif
