@php
    $tabs = [
        ['id' => 'playback', 'label' => 'پخش و پیشرفت', 'icon' => 'play_circle'],
        ['id' => 'materials', 'label' => 'رونوشت و یادداشت', 'icon' => 'edit_note'],
        ['id' => 'tips', 'label' => 'نکات', 'icon' => 'info'],
    ];
    $sections = [
        'playback' => [
            ['icon' => 'history', 'title' => 'ادامه از آخرین توقف', 'text' => 'جای توقف با بیشترین پیشرفت فرق دارد؛ اگر به عقب برگردید، دفعهٔ بعد همان جای توقف پیشنهاد می‌شود. پخش چند ثانیه عقب‌تر شروع می‌شود تا ادامهٔ مطلب را گم نکنید.'],
            ['icon' => 'tune', 'title' => 'سرعت، صدا و توقف شبانه', 'text' => 'سرعت و میزان صدا برای هر حساب یادآوری می‌شود. دکمهٔ «توقف پس از این جلسه» فقط برای همین بار است و جلسهٔ بعدی را خودکار شروع نمی‌کند.'],
            ['icon' => 'fiber_new', 'title' => 'نشان «جدید»', 'text' => 'سری یا رسانهٔ تازه تا وقتی هیچ جلسه‌ای از آن را پخش نکرده‌اید (حداکثر حدود ۳۰ روز) نشان «جدید» دارد؛ فقط باز کردن صفحه آن را برنمی‌دارد.'],
            ['icon' => 'cloud_sync', 'title' => 'ذخیرهٔ کم‌مصرف', 'text' => 'پیشرفت ابتدا در همین مرورگر ذخیره می‌شود و در توقف، پایان جلسه و خروج همگام می‌شود؛ جابه‌جایی و جستجو درخواست تازه‌ای نمی‌فرستند.'],
            ['icon' => 'lock', 'title' => 'انتشار تدریجی', 'text' => 'جلسه‌های قفل‌شده در تاریخ درج‌شده منتشر می‌شوند؛ اگر صفحه باز باشد کتابخانه یک بار تازه می‌شود.'],
        ],
        'materials' => [
            ['icon' => 'subtitles', 'title' => 'رونوشت زمان‌دار', 'text' => 'با لمس هر بخش رونوشت یا فصل به همان لحظه بروید؛ این بخش فقط برای جلسه‌های دارای فایل رونوشت نمایش داده می‌شود.'],
            ['icon' => 'bookmark_add', 'title' => 'یادداشت شخصی', 'text' => 'یادداشت به جلسه و لحظهٔ فعلی وصل است و فقط خودتان آن را می‌بینید. همهٔ یادداشت‌های یک جلسه را می‌توانید با زمانشان کپی کنید.'],
            ['icon' => 'done_all', 'title' => 'پایان‌یافته', 'text' => 'اگر جلسه‌ای را جای دیگر دیده‌اید، با دکمهٔ «پایان‌یافته» همان‌جا علامت تمام‌شده بزنید؛ این کار برگشت ندارد.'],
            ['icon' => 'link', 'title' => 'پیوند یک لحظه', 'text' => 'پیوند، جلسه و زمان را نگه می‌دارد؛ باز کردن آن پخش خودکار ندارد و دسترسی مخاطب دوباره بررسی می‌شود.'],
        ],
    ];
    $notes = ['نشان‌ها و سنجاق‌ها در همین مرورگر نگه‌داری می‌شوند؛ سنجاق مدیر مستقل از نشان شخصی است.', 'رسانه‌های منقضی یا خارج از مخاطبان شما در کتابخانه دیده نمی‌شوند.', 'اگر مرورگر قالب فایل را پخش نکند، از دکمهٔ دانلود همان جلسه استفاده کنید.'];
@endphp
<div>
    <div class="flex p-1 mb-5 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
        @foreach($tabs as $tab)
            <button type="button" data-tab="{{ $tab['id'] }}" x-on:click="setLegendTab($el.dataset.tab)" :aria-selected="legendTab === $el.dataset.tab" class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-[13px] font-bold text-[var(--md-sys-color-on-surface-variant)] aria-selected:bg-[var(--md-sys-color-primary)] aria-selected:text-[var(--md-sys-color-on-primary)]"><span class="material-symbols-rounded text-lg">{{ $tab['icon'] }}</span>{{ $tab['label'] }}</button>
        @endforeach
    </div>
    @foreach($sections as $id => $rows)
        <div data-tab="{{ $id }}" x-show="legendTab === $el.dataset.tab" class="space-y-2">
            @foreach($rows as $row)
                <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3"><span class="material-symbols-rounded h-8 w-8 rounded-lg flex items-center justify-center shrink-0 bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">{{ $row['icon'] }}</span><div><h3 class="text-[12px] font-bold mb-0.5">{{ $row['title'] }}</h3><p class="text-[12px] leading-6">{{ $row['text'] }}</p></div></div>
            @endforeach
        </div>
    @endforeach
    <div x-show="legendTab === 'tips'" x-cloak class="space-y-2">@foreach($notes as $note)<div class="flex items-start gap-2 px-1"><span class="material-symbols-rounded text-[15px] mt-0.5 opacity-70">info</span><p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $note }}</p></div>@endforeach</div>
</div>
