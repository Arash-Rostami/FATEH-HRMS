@php
    $emoji = match ($feed->category ?? null) {
        'General' => '📢',
        'Event' => '📅',
        'Birthday' => '🎂',
        'Work Anniversary' => '🏆',
        'Poll' => '📊',
        default => '💬',
    };
@endphp

<div class="flex flex-row-reverse items-center justify-between mb-4">
    <div class="flex items-center space-x-3" dir="ltr">
        <img class="h-10 w-10 rounded-full object-cover"
             src="{{ $feed->user?->getProfileImageUrl() ?? $feed->user?->getInitialsAvatarUrl() }}"
             alt="{{ $feed->user?->name ?? 'Guest' }}">
        <div>
            <h4 class="text-sm mr-2 text-[var(--md-sys-color-on-surface)]">
                {{ $feed->user?->full_name ?? 'کاربر ناشناس' }}
            </h4>

            <p dir="rtl" class="text-xs mr-2 text-[var(--md-sys-color-on-surface-variant)]">
                {{ $feed->created_at ? jdate($feed->created_at)->ago() : '' }}
            </p>
        </div>
    </div>

    <span class="inline-flex items-center space-x-2 px-3 py-1 text-sm font-semibold text-white rounded direction-rtl bg-main-mode">
        <span>{{ $emoji }}</span>
    </span>
</div>
