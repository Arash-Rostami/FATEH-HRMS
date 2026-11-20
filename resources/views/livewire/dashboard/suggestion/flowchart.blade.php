@php($lastIndex = count($workFlow) - 1)

<div class="rounded-2xl p-4 mb-2 shadow-sm">
    <div class="flex items-center gap-2 mb-4 px-1">
        <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)]">account_tree</span>
        <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">گردش کار پیشنهاد</h3>
    </div>

    <div class="flex flex-col md:flex-row md:flex-wrap md:justify-center items-stretch gap-2">
        @foreach ($workFlow as $index => $step)
            <div
                    class="flex flex-col md:flex-row items-stretch md:items-center gap-2 md:gap-3 md:flex-1 md:min-w-[160px] md:max-w-[220px]">
                <div class="flex-1 flex flex-col items-center justify-between text-center
                            rounded-xl shadow-md p-3 min-h-[120px]
                            bg-[var(--md-sys-color-primary-container)]
                            border border-[var(--md-sys-color-outline-variant)]
                            transition hover:shadow-lg">
                    <div class="flex items-center justify-center gap-2 mb-1">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full
                                     bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]
                                     text-[11px] font-bold">
                            {{ convertToPersian($step['step']) }}
                        </span>
                        <span
                                class="material-symbols-rounded text-[22px] text-[var(--md-sys-color-on-primary-container)]">
                            {{ $step['icon'] }}
                        </span>
                    </div>
                    <div class="text-[12px] font-bold leading-5 text-[var(--md-sys-color-on-primary-container)]">
                        {{ $step['title'] }}
                    </div>
                    <p class="text-[10px] leading-[1.6] text-[var(--md-sys-color-on-surface-variant)] mt-1">
                        {{ $step['description'] }}
                    </p>
                </div>

                @if ($index !== $lastIndex)
                    <div class="flex justify-center items-center md:w-6 shrink-0">
                        <span class="material-symbols-rounded text-neutral-500 md:!hidden text-[24px] leading-none">
                            keyboard_arrow_down
                        </span>
                        <span
                                class="material-symbols-rounded !hidden md:!block text-neutral-500 text-[22px] leading-none">
                            arrow_back
                        </span>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>

<div class="flex flex-col md:flex-row justify-center items-center space-y-4 md:space-y-0 md:space-x-4 p-4 relative mb-2 text-justify text-sm text-neutral-900 dark:text-[var(--md-sys-color-on-surface)] rounded">
    <ul class="list-disc pr-5 space-y-1">
        <li>
            برای پیگیری نظر هر واحد، با مدیر همان واحد هماهنگ کنید. سکوت واحد به معنی موافقت نیست.
        </li>
        <li>
            پیشنهاد را از فهرست باز کنید تا نظر واحدها و مرحلهٔ فعلی را ببینید.
        </li>
        <li>
            اگر نظر واحدها را از قبل گرفته‌اید، هنگام ثبت گزینهٔ «ثبت نظر واحدها توسط من» را روشن کنید و همان نظرها را وارد کنید.
        </li>
        <li>
            پس از پذیرش با ارجاع برای اجرا، مدیر هر واحد انجام کارش را ثبت می‌کند. با تکمیل همهٔ واحدها، پیشنهاد «پایان‌یافته» می‌شود.
        </li>
        <li>
            اگر پیشنهاد «نیازمند تکمیل» شد، مدیر واحد پیشنهاددهنده در همین پیشنهاد توضیح و نظر تازه ثبت می‌کند. سپس مدیریت ارشد دوباره تصمیم می‌گیرد؛ لازم نیست پیشنهاد تازه‌ای بسازید.
        </li>
    </ul>
</div>
