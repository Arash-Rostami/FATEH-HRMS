@component('livewire.dashboard.messaging.empty-state', [
    'icon' => 'campaign',
    'title' => 'به کانال‌ها خوش آمدید',
    'subtitle' => 'از لیست سمت راست یک کانال را انتخاب کنید یا کانال‌های عمومی را جستجو کنید.',
    'mobileIcon' => 'campaign',
    'mobileText' => 'یک کانال انتخاب کنید',
])
    @php
        $channelsList = is_iterable($this->channels) ? $this->channels : [];
        $totalUnread = $this->presenter?->totalUnread($channelsList) ?? 0;
    @endphp

    <div class="relative z-10 w-full max-w-md mx-auto mt-6" aria-label="آمار و وضعیت کانال‌ها">
        <div class="grid grid-cols-2 gap-2.5 p-2 rounded-2xl bg-[var(--md-sys-color-surface-container-low)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-xs">

            <div class="flex items-center gap-3 p-2.5 rounded-xl transition-colors hover:bg-[var(--md-sys-color-surface-container-high)]">
                <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.455a16.544 16.544 0 01-1.33-3.844m2.992-.375c2.406-.21 4.757-.655 7.01-1.32a1.05 1.05 0 00.758-1.01V7.93a1.05 1.05 0 00-.758-1.01 48.334 48.334 0 00-7.01-1.32" />
                    </svg>
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="text-xs font-medium text-[var(--md-sys-color-on-surface-variant)] truncate">کانال‌ها</span>
                    <span class="text-base font-bold tracking-tight text-[var(--md-sys-color-on-surface)]">{{ number_format(count($channelsList)) }}</span>
                </div>
            </div>

            <div class="flex items-center gap-3 p-2.5 rounded-xl transition-colors hover:bg-[var(--md-sys-color-surface-container-high)]">
                <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                    </svg>
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="text-xs font-medium text-[var(--md-sys-color-on-surface-variant)] truncate">خوانده‌نشده</span>
                    <span class="text-base font-bold tracking-tight text-[var(--md-sys-color-on-surface)]">{{ number_format($totalUnread) }}</span>
                </div>
            </div>

        </div>
    </div>
@endcomponent
