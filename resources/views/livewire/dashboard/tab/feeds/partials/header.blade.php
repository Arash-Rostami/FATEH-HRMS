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

<div class="flex items-center justify-between mb-4 px-1">
    {{-- User Info (Right) --}}
    <div class="flex items-center gap-3">
        <div class="relative">
            <img class="h-10 w-10 rounded-full object-cover border border-[var(--md-sys-color-outline-variant)]/20 shadow-sm"
                 src="{{ $feed->user?->profile?->image }}"
                 alt="{{ $feed->user?->full_name ?? 'Guest' }}">
             {{-- Online status dot (optional, matching comments style) --}}
             @if($feed->user?->isOnline() ?? false)
                <div class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-500 border-2 border-[var(--md-sys-color-surface)] rounded-full"></div>
             @endif
        </div>

        <div class="flex flex-col items-start">
            <h4 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] leading-tight">
                {{ $feed->user?->full_name ?? 'کاربر ناشناس' }}
            </h4>
            <span class="text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)] mt-0.5">
                {{ $feed->created_at ? jdate($feed->created_at)->ago() : '' }}
            </span>
        </div>
    </div>

    {{-- Category Badge (Left) --}}
    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] border border-[var(--md-sys-color-tertiary)]/20 shadow-sm">
        <span class="text-xs">{{ $emoji }}</span>
        <span class="text-[10px] font-bold tracking-wide">{{ $feed->category ?? 'عمومی' }}</span>
    </div>
</div>
