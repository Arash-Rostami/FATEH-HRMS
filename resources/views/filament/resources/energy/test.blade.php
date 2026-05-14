@php
    [$questions, $sections, $prompts, $answers] = [
        $getState()['questions'] ?? [],
        $getState()['sections']  ?? [],
        $getState()['prompts']   ?? [],
        $getState()['answers']   ?? [],
    ];
@endphp

<div class="space-y-6 md:space-y-8" dir="rtl">
    @foreach($questions as $category => $options)
        @php
            $checkedCount = collect($answers[$category] ?? [])->filter()->count();
            $totalCount   = count($options);
            $progress     = $totalCount > 0 ? round(($checkedCount / $totalCount) * 100) : 0;
        @endphp

        <section class="overflow-hidden rounded-2xl ring-1 ring-gray-950/5 dark:ring-white/10 transition-all duration-300 hover:shadow-sm">
            <header class="border-b border-gray-100 dark:border-white/10 px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                            {{ $sections[$category] ?? $category }}
                        </h3>
                        @if(!empty($prompts[$category]))
                            <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                                {{ $prompts[$category] }}
                            </p>
                        @endif
                    </div>

                    <div class="flex flex-col items-end shrink-0 gap-2">
                        <span class="inline-flex items-center rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700 ring-1 ring-inset ring-primary-600/20 dark:bg-primary-500/10 dark:text-primary-400 dark:ring-primary-500/20" dir="ltr">
                            {{ $checkedCount }} / {{ $totalCount }}
                        </span>
                    </div>
                </div>

                <div class="mt-5 flex w-full h-1.5 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800" dir="rtl">
                    <div class="flex flex-col justify-center overflow-hidden bg-primary-600 dark:bg-primary-500 transition-all duration-500 ease-out" style="width: {{ $progress }}%"></div>
                </div>
            </header>

            <div class="p-6">
                <ul class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($options as $index => $optionText)
                        @php $answered = (bool) ($answers[$category][$index] ?? false); @endphp

                        <li @class([
                            'relative flex items-start gap-4 rounded-xl p-4 transition-all duration-200',
                            'bg-primary-50/50 ring-1 ring-primary-500/30 dark:bg-primary-400/10 dark:ring-primary-400/30' => $answered,
                            'bg-transparent ring-1 ring-gray-950/5 dark:ring-white/10 hover:bg-gray-50/50 dark:hover:bg-white/5' => ! $answered,
                        ])>
                            <div class="flex h-6 shrink-0 items-center">
                                @if($answered)
                                    <x-heroicon-s-check-circle class="h-5 w-5 text-primary-600 dark:text-primary-400 drop-shadow-sm"/>
                                @else
                                    <div class="h-4 w-4 m-0.5 rounded-full border-2 border-gray-300 dark:border-gray-600"></div>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1 text-sm leading-6">
                                <span @class([
                                    'block font-medium',
                                    'text-primary-900 dark:text-primary-200' => $answered,
                                    'text-gray-700 dark:text-gray-300' => ! $answered,
                                ])>
                                    {{ $optionText }}
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endforeach
</div>
