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
        @if($feed->user?->profile?->image)
            <img class="h-10 w-10 rounded-full object-cover"
                 src="{{ $feed->user->profile->image }}"
                 alt="{{ $feed->user->full_name ?? 'Guest' }}">
        @else
            <div class="h-10 w-10 flex items-center justify-center rounded-full bg-gray-100 border border-gray-200">
                <svg class="h-6 w-6 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12,2C6.48,2,2,6.48,2,12s4.48,10,10,10s10-4.48,10-10S17.52,2,12,2z M12,6c1.93,0,3.5,1.57,3.5,3.5S13.93,13,12,13 s-3.5-1.57-3.5-3.5S10.07,6,12,6z M12,20c-2.03,0-4.43-0.82-6.14-2.88C7.55,15.8,9.68,15,12,15s4.45,0.8,6.14,2.12 C16.43,19.18,14.03,20,12,20z"/>
                </svg>
            </div>
        @endif

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
