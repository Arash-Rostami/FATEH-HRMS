@component('livewire.dashboard.messaging.empty-state', [
    'icon' => 'mark_chat_unread',
    'title' => 'به پیام‌رسان داخلی خوش آمدید',
    'subtitle' => 'از لیست سمت راست یک همکار را انتخاب کنید.',
    'mobileIcon' => 'chat',
    'mobileText' => 'یک مکالمه انتخاب کنید',
])
    @php
        $contactsList = is_iterable($this->contacts) ? $this->contacts : [];
        $totalUnread = array_sum(array_column($contactsList, 'unread_count'));
        $onlineCount = count(array_filter($contactsList, static fn(array $contact): bool => !empty($contact['is_online'])));
        $totalOccasions = $this->presenter?->totalOccasions($contactsList) ?? 0;
    @endphp

    <div class="relative z-10 w-full max-w-2xl mx-auto mt-6" aria-label="آمار و وضعیت">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 p-2 rounded-2xl bg-[var(--md-sys-color-surface-container-low)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-xs">

            <div class="flex items-center gap-3 p-2.5 rounded-xl transition-colors hover:bg-[var(--md-sys-color-surface-container-high)]">
                <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="text-xs font-medium text-[var(--md-sys-color-on-surface-variant)] truncate">همکاران</span>
                    <span class="text-base font-bold tracking-tight text-[var(--md-sys-color-on-surface)]">{{ number_format($this->totalStaff ?? 0) }}</span>
                </div>
            </div>

            <div class="flex items-center gap-3 p-2.5 rounded-xl transition-colors hover:bg-[var(--md-sys-color-surface-container-high)]">
                <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="text-xs font-medium text-[var(--md-sys-color-on-surface-variant)] truncate">آنلاین</span>
                    <span class="text-base font-bold tracking-tight text-[var(--md-sys-color-on-surface)]">{{ number_format($onlineCount) }}</span>
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

            <div class="flex items-center gap-3 p-2.5 rounded-xl transition-colors hover:bg-[var(--md-sys-color-surface-container-high)]">
                <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v-1.5m0 1.5c-1.355 0-2.697.056-4.024.166C6.845 8.51 6 9.473 6 10.608v2.513m6-4.87c1.355 0 2.697.055 4.024.165C17.155 8.51 18 9.473 18 10.608v2.513m-12 4.872v1.27c0 1.135.845 2.098 1.976 2.192a48.424 48.424 0 008.048 0c1.131-.094 1.976-1.057 1.976-2.192v-1.27m-12 0c1.355.11 2.697.166 4.024.166m7.952-1.436c.016.48.024.96.024 1.436m-12 0c0-.476.008-.956.024-1.436m0 0a48.667 48.667 0 007.952 0" />
                    </svg>
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="text-xs font-medium text-[var(--md-sys-color-on-surface-variant)] truncate">رویدادها</span>
                    <span class="text-base font-bold tracking-tight text-[var(--md-sys-color-on-surface)]">{{ number_format($totalOccasions) }}</span>
                </div>
            </div>

        </div>
    </div>
@endcomponent
