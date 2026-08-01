<div x-data="{ role: 'owner' }">
    <div class="flex p-1 mb-5 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
        @foreach([
            ['id' => 'owner', 'icon' => 'shield_person', 'label' => 'مالک کانال'],
            ['id' => 'member', 'icon' => 'person', 'label' => 'عضو عادی'],
        ] as $role)
            <button
                type="button"
                @click="role = '{{ $role['id'] }}'"
                :class="role === '{{ $role['id'] }}'
                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                    : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-[13px] font-bold transition-all duration-200"
            >
                <span class="material-symbols-rounded text-[17px]">{{ $role['icon'] }}</span>
                {{ $role['label'] }}
            </button>
        @endforeach
    </div>

    @php
        $rows = [
            'owner' => [
                'note' => 'شما این کانال را ساخته‌اید یا مدیریت آن به شما محول شده است.',
                ['icon' => 'visibility', 'color' => 'primary', 'label' => 'می‌بینید', 'text' => 'همان پیام‌ها و اطلاعات کانال که سایر اعضا می‌بینند.'],
                ['icon' => 'bolt', 'color' => 'tertiary', 'label' => 'اقدام شما', 'text' => 'از «اطلاعات کانال» → «مدیریت اعضا» می‌توانید عضو اضافه یا حذف کنید. دکمهٔ «خروج از کانال» برای شما نمایش داده نمی‌شود؛ مالک نمی‌تواند از کانال خودش خارج شود.'],
            ],
            'member' => [
                'note' => 'شما عضو این کانال هستید ولی مالک آن نیستید.',
                ['icon' => 'visibility', 'color' => 'primary', 'label' => 'می‌بینید', 'text' => 'همان پیام‌ها و اطلاعات کانال که مالک می‌بیند.'],
                ['icon' => 'bolt', 'color' => 'tertiary', 'label' => 'اقدام شما', 'text' => 'مدیریت اعضا در اختیار شما نیست. در عوض هر زمان بخواهید می‌توانید از دکمهٔ «خروج از کانال» استفاده کنید.'],
            ],
        ];
    @endphp

    @foreach($rows as $roleId => $sections)
        <div x-show="role === '{{ $roleId }}'" x-cloak class="space-y-3">
            <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1">{{ $sections['note'] }}</p>
            @foreach(array_slice($sections, 1) as $s)
                @php
                    $chipClasses = match ($s['color']) {
                        'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
                        'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
                    };
                @endphp
                <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses }}">
                        <span class="material-symbols-rounded text-[16px]">{{ $s['icon'] }}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $s['label'] }}</p>
                        <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $s['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
</div>
