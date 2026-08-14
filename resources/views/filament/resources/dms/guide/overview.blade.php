@php
    $rows = [
        [
            'icon' => 'description',
            'label' => '«یک سند» چیست؟',
            'hint' => 'هر ردیف از جدول <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">dms</code> یک سند رسمی است: عنوان، کد یکتا، نسخه، وضعیت، فایل اصلی، فایل‌های الحاقی، واحدهای مالک، کاربران اختصاصی، توضیحات بازبینی و دو فیلد کلید/مقدارِ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">extra</code> و <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">tags</code>. اسناد در پنل کاربری به‌صورت دو زبانهٔ «سیستمی» و «غیر سیستمی» نمایش داده می‌شوند.',
        ],
        [
            'icon' => 'account_tree',
            'label' => 'دو نوع سند: سیستمی و غیر سیستمی',
            'hint' => 'فیلد <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">type</code> یک بولین است: <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">true</code> = سیستمی (سند رسمی سازمان با کد و نسخه)، <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">false</code> = غیر سیستمی. اسکوپ‌های <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">systematic()</code> و <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">nonSystematic()</code> کاربران را در دو زبانهٔ جداگانه می‌بینند. تغییر این مقدار، سند را از یک زبانه به زبانهٔ دیگر منتقل می‌کند.',
        ],
        [
            'icon' => 'sync',
            'label' => 'سه وضعیت: فعال / در بررسی / منسوخ',
            'hint' => 'وضعیت از نوع <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">DocumentStatus</code> است. تنها اسنادِ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">live</code> در پنل کاربری دیده می‌شوند (<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">scopeVisibleToUser</code> وضعیت را فیلتر می‌کند). <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">under_review</code> و <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">obsolete</code> از دسترس کاربر خارج‌اند ولی در فهرست ادمین با نشان زرد/قرمز دیده می‌شوند.',
        ],
        [
            'icon' => 'verified_user',
            'label' => 'دو تأیید جداگانه = امضای دیجیتال',
            'hint' => 'هر کاربر برای هر سند دو مرحله دارد: «تأیید دریافت» (فیلد <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">read=true</code> در جدول <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">reads</code>) و «تأیید مطالعه» (<code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">read_count&gt;0</code>). تا هر دو ثبت نشود، سند در کارتابل کاربر «اقدام مورد نیاز» می‌ماند. این مدل در زبانهٔ «تجربهٔ کاربر» کامل توضیح داده شده است.',
        ],
        [
            'icon' => 'sync',
            'label' => 'تغییر فایل یا بازبینی، تأییدها را بازنشانی می‌کند',
            'hint' => 'وقتی <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">file</code> یا <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">revision</code> در ویرایش تغییر کند، هookِ <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">updated</code> همهٔ رکوردهای <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">reads</code> آن سند را به <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">read=false, read_count=0</code> برمی‌گرداند و <code class="px-1.5 py-0.5 rounded-md font-mono text-[11px] bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">combined_read_count</code> را صفر می‌کند — یعنی کاربران باید سندِ جدید را از نو تأیید کنند.',
        ],
    ];
@endphp

<div class="flex flex-col gap-5" dir="rtl">

    <div class="flex items-center gap-3 px-1">
        <span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">info</span>
        <p class="text-[14px] font-black text-[var(--md-sys-color-on-surface)]">«مدیریت اسناد» سامانهٔ انتشار و تأیید اسناد رسمی سازمان است</p>
    </div>
    <p class="text-[12.5px] text-[var(--md-sys-color-on-surface-variant)] leading-7 font-medium px-1">
        شما (ادمین) سند را با کد، نسخه، فایل و مالکیت واحدها ثبت می‌کنید؛ کاربران آن را در پنل کاربری دیده و در دو مرحله تأیید می‌کنند. این صفحه نظارت کل‌سازمان است: همهٔ اسناد (فعال/در بررسی/منسوخ) را می‌بینید، آمار مطالعه را بازبینی می‌کنید، فایل‌ها را جایگزانی می‌کنید و خروجی اکسل می‌گیرید.
    </p>

    <div class="flex flex-col rounded-2xl bg-[var(--md-sys-color-surface)] shadow-md shadow-[var(--md-sys-color-shadow)]/5 overflow-hidden animate-slide-up-fade">
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-primary-container)] flex items-center gap-2">
            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-primary-container)]">folder_open</span>
            <p class="text-[13px] font-black text-[var(--md-sys-color-on-primary-container)]">مفاهیم پایه</p>
        </div>
        <div class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @foreach($rows as $r)
                <div class="flex items-start gap-4 p-5 hover:bg-[var(--md-sys-color-surface-container)] transition-colors duration-300">
                    <div class="shrink-0 mt-0.5">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                            <span class="material-symbols-rounded text-[22px]">{{ $r['icon'] }}</span>
                        </span>
                    </div>
                    <div class="flex-1 flex flex-col gap-1.5">
                        <p class="text-[13px] font-black text-[var(--md-sys-color-on-surface)]">{{ $r['label'] }}</p>
                        <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] leading-6 font-medium">{!! $r['hint'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3.5 bg-[var(--md-sys-color-surface-container-lowest)] border-t border-[var(--md-sys-color-outline-variant)]">
            <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">lightbulb</span>
                تنها اسنادِ «فعال» به کاربران می‌رسد؛ برای جمع‌آوری یک سند از دسترس کاربران، کافی است وضعیت را به «منسوخ» تغییر دهید — حذف ردیف لازم نیست.
            </p>
        </div>
    </div>
</div>