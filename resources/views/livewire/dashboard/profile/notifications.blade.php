<div class="space-y-5 animate-[fade-in_0.4s_ease-out]" dir="rtl">

    <x-ui.buttons.tab-selector
        :tabs="[
            ['id' => 'notifications', 'icon' => 'notifications', 'label' => 'اعلان‌ها'],
            ['id' => 'reminders', 'icon' => 'alarm', 'label' => 'یادآوری‌ها'],
        ]"
        :active-tab="$activeSection"
        class="!mb-0"
    />

    @if($activeSection === 'notifications')
    <div wire:key="profile-notifications-section-notifications" class="space-y-5 mt-5">

    <div class="flex items-center justify-between px-1">
        <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">تاریخچه کامل اعلان‌های شما</p>
        <x-ui.buttons.form
            wire:click="markAllRead"
            wire:loading.attr="disabled"
            wire:target="markAllRead"
            loading="markAllRead"
            variant="tonal"
            icon="done_all"
        >
            علامت‌گذاری همه به‌عنوان خوانده‌شده
        </x-ui.buttons.form>
    </div>

    <x-ui.table>
        <x-slot:head>
            <tr>
                <th class="px-4 py-3 text-right">عنوان و متن</th>
                <th class="px-4 py-3 text-right">تاریخ</th>
                <th class="px-4 py-3 text-right">وضعیت</th>
                <th class="px-4 py-3 text-right">اقدام</th>
            </tr>
        </x-slot:head>

        @forelse($this->notifications as $n)
            <tr wire:key="profile-notif-{{ $n->id }}"
                class="border-t border-[var(--md-sys-color-outline-variant)]/60 {{ $n->read_at ? '' : 'bg-[var(--md-sys-color-primary)]/5' }}">
                <td class="px-4 py-3">
                    <p class="font-bold text-sm text-[var(--md-sys-color-on-surface)]">{{ $n->data['title'] ?? '' }}</p>
                    <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] mt-0.5">{{ superClean($n->data['body'] ?? '', 150) }}</p>
                </td>
                <td class="px-4 py-3 text-xs text-[var(--md-sys-color-on-surface-variant)] whitespace-nowrap" dir="ltr">
                    {{ toJalaliRelative($n->created_at) }}
                </td>
                <td class="px-4 py-3">
                    @if($n->read_at)
                        <span wire:key="profile-notif-status-read" class="inline-flex items-center gap-1.5 text-xs text-[var(--md-sys-color-on-surface-variant)]">
                            <span class="w-1.5 h-1.5 rounded-full bg-[var(--md-sys-color-outline)]"></span>
                            خوانده‌شده
                        </span>
                    @else
                        <span wire:key="profile-notif-status-unread" class="inline-flex items-center gap-1.5 text-xs font-bold text-[var(--md-sys-color-primary)]">
                            <span class="w-1.5 h-1.5 rounded-full bg-[var(--md-sys-color-primary)]"></span>
                            خوانده‌نشده
                        </span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if(!$n->read_at)
                        <x-ui.buttons.form
                            wire:key="profile-notif-mark-read"
                            wire:click="markRead('{{ $n->id }}')"
                            wire:loading.attr="disabled"
                            wire:target="markRead('{{ $n->id }}')"
                            loading="markRead('{{ $n->id }}')"
                            variant="ghost"
                            size="icon"
                            icon="done"
                            title="علامت‌گذاری به‌عنوان خوانده‌شده"
                        />
                    @endif
                </td>
            </tr>
        @empty
            <tr wire:key="profile-notifications-empty-row">
                <td colspan="4">
                    <x-ui.empty icon="notifications_off" title="اعلانی وجود ندارد" description="هنوز اعلانی برای شما ثبت نشده است." variant="list"/>
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    @if($this->notifications->hasMorePages())
        <div class="flex justify-center">
            <x-ui.buttons.load-more action="loadMore" text="نمایش بیشتر" loading-text="در حال بارگذاری…"
                                     class="mx-auto my-2 px-4 py-2 rounded-xl text-xs font-medium bg-[var(--md-sys-color-surface-variant)]/50 text-[var(--md-sys-color-on-surface-variant)]"/>
        </div>
    @endif

    </div>
    @endif

    @if($activeSection === 'reminders')
    <div wire:key="profile-notifications-section-reminders" class="mt-5">
        <livewire:dashboard.reminder.main variant="embedded" wire:key="profile-reminders"/>
    </div>
    @endif

</div>
