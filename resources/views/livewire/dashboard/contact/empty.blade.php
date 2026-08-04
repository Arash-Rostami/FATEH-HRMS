@component('livewire.dashboard.messaging.empty-state', [
    'icon' => 'mark_chat_unread',
    'title' => 'به پیام‌رسان داخلی خوش آمدید',
    'subtitle' => 'از لیست سمت راست یک همکار را انتخاب کنید.',
    'mobileIcon' => 'chat',
    'mobileText' => 'یک مکالمه انتخاب کنید',
])
    @php $totalUnread = array_sum(array_column($this->contacts, 'unread_count')); @endphp
    <div class="relative z-10 flex items-center gap-6 mt-2" aria-label="آمار">
        <div class="flex flex-col items-center gap-1"><span class="text-2xl font-bold text-[var(--md-sys-color-on-surface)]">{{ $this->totalStaff }}</span><span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">همکاران</span></div>
        <div class="w-px h-8 bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]" aria-hidden="true"></div>
        <div class="flex flex-col items-center gap-1"><span class="text-2xl font-bold text-[var(--md-sys-color-on-surface)]">{{ count(array_filter($this->contacts, fn($u) => $u['is_online'] ?? false)) }}</span><span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">آنلاین</span></div>
        <div class="w-px h-8 bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]" aria-hidden="true"></div>
        <div class="flex flex-col items-center gap-1"><span class="text-2xl font-bold text-[var(--md-sys-color-primary)]">{{ $totalUnread }}</span><span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">خوانده‌نشده</span></div>
    </div>
@endcomponent