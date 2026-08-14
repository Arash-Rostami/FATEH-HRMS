@php
    $types = [
        'seat'    => ['label' => 'میز کار', 'icon' => 'desk',           'color' => 'primary'],
        'spot'    => ['label' => 'پارکینگ', 'icon' => 'local_parking',  'color' => 'success'],
        'car'     => ['label' => 'خودرو',  'icon' => 'directions_car', 'color' => 'warning'],
        'meeting' => ['label' => 'ملاقات', 'icon' => 'person',          'color' => 'info'],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-start gap-4 rounded-2xl bg-[var(--md-sys-color-surface)] p-5 shadow-md shadow-[var(--md-sys-color-shadow)]/5">
        <span class="material-symbols-rounded text-[28px] text-[var(--md-sys-color-primary)] mt-0.5">verified_user</span>
        <div class="flex-1 flex flex-col gap-2">
            <h3 class="text-[15px] font-black text-[var(--md-sys-color-on-surface)]">سیاست رزرو چیست؟</h3>
            <p class="text-[13px] leading-relaxed font-semibold text-[var(--md-sys-color-on-surface-variant)]">
                هر «سیاست» یک قانون کلید/مقدار است که روی یک نوع منبع اعمال می‌شود. مدیریت اینجا کلیدها را ویرایش می‌کند و فرم رزرو کاربر دقیقاً همان کلیدها را اطاعت می‌کند. هیچ کاربری نمی‌تواند مقدار قانونی را دور بزند؛ حتی ادمین در زمان ثبت رزرو از همین قوانین عبور می‌کند (مگر قانونی که برای ادمین عبور دارد).
            </p>
        </div>
    </div>

    <div class="flex items-start gap-4 rounded-2xl bg-[var(--md-sys-color-surface)] p-5 shadow-md shadow-[var(--md-sys-color-shadow)]/5">
        <span class="material-symbols-rounded text-[28px] text-[var(--md-sys-color-primary)] mt-0.5">database</span>
        <div class="flex-1 flex flex-col gap-2">
            <h3 class="text-[15px] font-black text-[var(--md-sys-color-on-surface)]">نحوه ذخیره</h3>
            <p class="text-[13px] leading-relaxed font-semibold text-[var(--md-sys-color-on-surface-variant)]">
                هر نوع منبع یک ردیف در جدول دارد و همه کلیدها به‌صورت یکجا در فرم ویرایش ذخیره می‌شوند. مقدار هر کلید به‌صورت آرایه (JSON) نگهداری می‌شود؛ بنابراین فیلدهای ساعت شروع/پایان با هم در یک کلید <code class="px-2 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">allowed_hours</code> قرار می‌گیرند و فیلد روزهای مجاز به‌صورت لیست در <code class="px-2 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">allowed_days</code> ذخیره می‌شود.
            </p>
        </div>
    </div>

    <div class="flex items-start gap-4 rounded-2xl bg-[var(--md-sys-color-surface)] p-5 shadow-md shadow-[var(--md-sys-color-shadow)]/5">
        <span class="material-symbols-rounded text-[28px] text-[var(--md-sys-color-primary)] mt-0.5">cached</span>
        <div class="flex-1 flex flex-col gap-2">
            <h3 class="text-[15px] font-black text-[var(--md-sys-color-on-surface)]">حافظه پنهان و نوسازی خودکار</h3>
            <p class="text-[13px] leading-relaxed font-semibold text-[var(--md-sys-color-on-surface-variant)]">
                قوانین سمت سرور در حافظه پنهان (Cache) نگهداری می‌شوند تا رزرو سریع باشد. به‌محض ذخیره یا حذف یک قانون، حافظه آن نوع منبع به‌صورت خودکار پاک می‌شود؛ نیازی به پاک‌سازی دستی cache نیست. کاربران بلافاصله بعد از ذخیره‌ی شما قانون جدید را می‌بینند.
            </p>
        </div>
    </div>

    <div class="rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden">
        <div class="px-5 py-4 bg-[var(--md-sys-color-surface-container-low)] border-b border-[var(--md-sys-color-outline-variant)]">
            <h3 class="text-[14px] font-black text-[var(--md-sys-color-on-surface)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[22px] text-[var(--md-sys-color-primary)]">apps</span>
                چهار نوع منبع
            </h3>
            <p class="text-[12px] font-semibold text-[var(--md-sys-color-on-surface-variant)] mt-1">هر نوع یک مجموعه قانون مستقل دارد؛ یک قانون روی نوع دیگر اثر نمی‌گذارد.</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($types as $value => $t)
                <div class="flex items-center gap-4 p-4 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <span class="shrink-0 material-symbols-rounded text-[24px] text-{{ $t['color'] }}">{{ $t['icon'] }}</span>
                    <div class="flex-1">
                        <p class="text-[13px] font-bold text-[var(--md-sys-color-on-surface)]">{{ $t['label'] }}</p>
                        <p class="text-[11px] font-semibold text-[var(--md-sys-color-on-surface-variant)] mt-0.5">کلید منبع: <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">{{ $value }}</code></p>
                    </div>
                    @if($value === 'meeting')
                        <span class="text-[10px] font-bold px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">غیر تمام‌روز</span>
                    @else
                        <span class="text-[10px] font-bold px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">تمام‌روز</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex items-start gap-4 rounded-2xl bg-[var(--md-sys-color-tertiary-container)] p-5">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-on-tertiary-container)] mt-0.5">info</span>
        <p class="text-[12px] leading-relaxed font-bold text-[var(--md-sys-color-on-tertiary-container)]">
            تب «کلیدها» معنای هر فیلد را توضیح می‌دهد، تب «قطع‌کننده» نحوه فعال/غیرفعال کردن یک نوع را نشان می‌دهد و تب «خطاها» کدهای ERR-XXX را که کاربر در پیام خطا می‌بیند، فهرست می‌کند.
        </p>
    </div>
</div>