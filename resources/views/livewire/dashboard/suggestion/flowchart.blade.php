@php($lastIndex = count($workFlow) - 1)

<div class="rounded-2xl bg-main-mode p-4 mb-2 shadow-sm">
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
                            {{ $step['step'] }}
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

<div class="flex flex-col md:flex-row justify-center items-center space-y-4 md:space-y-0 md:space-x-4 p-4 bg-main-mode relative mb-2 text-justify text-sm text-neutral-900 rounded">
    <ul class="list-disc pr-5 space-y-1">
        <li>
            برای حصول اطمینان از دریافت نظرات و متعاقباً رسیدن پیشنهاد به مدیریت، لطفاً از طریق مدیر یا سرپرست واحد
            ذی نفع پیگیری کنید تا تأیید کنند که بازخورد خود را ارائه داده‌اند.
        </li>
        <li>
            بازخوردهای دریافتی را میتوانید به راحتی در جدول پیشنهادات در لیست مشاهده کنید. کافی است روی آن
            کلیک کرده تا جزییات و بازخوردهای مربوطه در این جعبه ظاهر شده و نمایش داده شوند.
        </li>
        <li>
            در صورت اطلاع از بازخورد مدیران یا سرپرستان واحدهای ذی نفع و در راستای تسریع فرایند حاضر، می‌توانید با
            فعال‌سازی بخش انتهایی قرار داده شده در این فرم، نظرات آن‌ها را نیز ثبت (خوداظهاری) کنید.
        </li>
        <li>
            پس از پذیرش پیشنهاد، در صورت ارجاع برای اقدام، واحدهای موظف می‌توانند تکمیل اقدام خود را ثبت کنند تا
            پیشنهاد به‌صورت رسمی پایان‌یافته و بایگانی شود.
        </li>
        <li>
            در صورت نیاز به تکمیل پیشنهاد (به درخواست مدیریت)، یک پیشنهاد جدید ثبت کنید، شماره سریال (...-SN) پیشنهاد قبلی را در آن قید
            نمایید، و در صورت عدم ضرورت به دریافت مجدد نظرات سایر ذی‌نفعان، آنها را نیز به‌صورت خوداظهاری وارد کنید.
        </li>
    </ul>
</div>
