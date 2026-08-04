@component('livewire.dashboard.messaging.empty-state', [
    'icon' => 'campaign',
    'title' => 'به کانال‌ها خوش آمدید',
    'subtitle' => 'از لیست سمت راست یک کانال را انتخاب کنید یا کانال‌های عمومی را جستجو کنید.',
    'mobileIcon' => 'campaign',
    'mobileText' => 'یک کانال انتخاب کنید',
])
    @php $p = $this->presenter; $totalUnread = $p->totalUnread($this->channels); @endphp
    <div class="relative z-10 flex items-center gap-6 mt-2" aria-label="آمار">
        <div class="flex flex-col items-center gap-1"><span class="text-2xl font-bold text-[var(--md-sys-color-on-surface)]">{{ count($this->channels) }}</span><span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">کانال‌ها</span></div>
        <div class="w-px h-8 bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]" aria-hidden="true"></div>
        <div class="flex flex-col items-center gap-1"><span class="text-2xl font-bold text-[var(--md-sys-color-primary)]">{{ $totalUnread }}</span><span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">خوانده‌نشده</span></div>
    </div>
@endcomponent