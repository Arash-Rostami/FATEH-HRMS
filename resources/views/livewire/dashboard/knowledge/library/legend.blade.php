@php
    $tabs = [
        ['id' => 'playback', 'label' => 'پخش و پیشرفت', 'icon' => 'play_circle'],
        ['id' => 'materials', 'label' => 'متن جلسه و یادداشت', 'icon' => 'edit_note'],
        ['id' => 'tips', 'label' => 'نکات', 'icon' => 'info'],
    ];
    $sections = [
        'playback' => [
            ['icon' => 'history', 'title' => 'ادامه از آخرین توقف', 'text' => 'اگر بخشی را دوباره ببینید و پخش را متوقف کنید، دفعهٔ بعد از همان حوالی ادامه می‌دهید. درصد پیشرفت، دورترین جایی را نشان می‌دهد که به آن رسیده‌اید.'],
            ['icon' => 'tune', 'title' => 'سرعت، صدا و پایان پخش', 'text' => 'سرعت و صدا برای حساب شما به خاطر سپرده می‌شوند. «توقف پس از این جلسه» را بزنید تا پخش در پایان همین جلسه متوقف شود.'],
            ['icon' => 'keyboard', 'title' => 'کلیدها و تمام‌صفحه', 'text' => 'با کلید فاصله پخش را شروع یا متوقف کنید. پیکان چپ ۱۰ ثانیه به عقب و پیکان راست ۱۰ ثانیه به جلو می‌برد. برای قطع یا وصل صدا M را بزنید. در ویدیو، F حالت تمام‌صفحه را باز یا بسته می‌کند.'],
            ['icon' => 'push_pin', 'title' => 'سنجاق، رنگ و یادآوری', 'text' => 'از دکمه‌های کنار رسانه، آن را بالای فهرست سنجاق کنید، رنگ ردیف را تغییر دهید یا برای تماشا و شنیدن آن یادآوری بگذارید. سنجاق و رنگ در همین مرورگر ذخیره می‌شوند.'],
            ['icon' => 'view_column', 'title' => 'ابزار جدول جلسه‌ها', 'text' => 'با ابزار بالای جدول می‌توانید ستون‌های نوع، مدت، تاریخ، امکانات، یادداشت و وضعیت را پنهان کنید یا نمایش را فشرده کنید. انتخاب شما در همین مرورگر می‌ماند.'],
            ['icon' => 'fiber_new', 'title' => 'نشان «جدید»', 'text' => 'با اولین پخش، نشان «جدید» برداشته می‌شود. رسانه حداکثر ۳۰ روز این نشان را دارد.'],
            ['icon' => 'cloud_sync', 'title' => 'ذخیرهٔ پیشرفت', 'text' => 'پیشرفت ابتدا در مرورگر ذخیره می‌شود. هنگام توقف پخش، پایان جلسه یا خروج از صفحه، سامانه آخرین پیشرفت را ثبت می‌کند.'],
            ['icon' => 'lock', 'title' => 'زمان انتشار جلسه‌ها', 'text' => 'جلسه‌های قفل‌شده در زمان درج‌شده قابل پخش می‌شوند. اگر فهرست به‌روز نشد، «تلاش دوباره» را بزنید.'],
        ],
        'materials' => [
            ['icon' => 'subtitles', 'title' => 'متن و بخش‌های جلسه', 'text' => 'در «متن جلسه»، روی عنوان یک فصل یا خطی از متن بزنید تا به زمان همان بخش بروید. برای دیدن فصل‌های بیشتر، نوار فصل‌ها را به طرفین بکشید.'],
            ['icon' => 'bookmark_add', 'title' => 'یادداشت شخصی', 'text' => 'یادداشت شخصی با زمان فعلی پخش ذخیره می‌شود و فقط خودتان آن را می‌بینید. روی زمان یادداشت بزنید تا به همان لحظه بروید.'],
            ['icon' => 'done_all', 'title' => 'علامت‌زدن جلسهٔ تمام‌شده', 'text' => 'اگر جلسه‌ای را قبلاً دیده یا شنیده‌اید، «علامت‌زدن به‌عنوان تمام‌شده» را بزنید. این کار قابل برگشت نیست.'],
            ['icon' => 'link', 'title' => 'پیوند یک لحظه', 'text' => '«کپی پیوند این لحظه» را بزنید و پیوند را برای همکار بفرستید. اگر به همان جلسه دسترسی داشته باشد، با زدن پخش از زمان مشخص‌شده شروع می‌کند.'],
        ],
    ];
    $notes = ['رسانهٔ دلخواه را با «نشان کردن» ذخیره کنید و بعد از «فقط نشان‌شده‌ها» پیدایش کنید. نشان‌های شخصی در همین مرورگر می‌مانند.', 'کتابخانه رسانه‌های فعال و معتبرِ در دسترس شما را نشان می‌دهد.', 'اگر مرورگر قالب فایل را پخش نکند، از دکمهٔ دانلود همان جلسه استفاده کنید.'];
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
