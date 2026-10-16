@php $meta = $presenter->systemEntryMeta($kind); @endphp

<div wire:key="reply-{{ $reply->id }}"
     data-ths-system="{{ $kind }}"
     class="self-center inline-flex flex-wrap items-center justify-center gap-2 max-w-full px-3 py-1.5 rounded-xl sm:rounded-full text-[11px] font-semibold text-center border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] {{ $meta['classes'] ?? 'bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)]' }}">
    <span class="material-symbols-rounded text-[15px]">{{ $meta['icon'] ?? 'info' }}</span>
    <span class="whitespace-pre-wrap [overflow-wrap:anywhere]">{{ $reply->body }}</span>
    <span class="font-normal opacity-85">توسط {{ $reply->user?->name ?? '—' }}</span>
    <time class="opacity-60 text-[10px] tabular-nums" dir="ltr">{{ $presenter->replyTime($reply->created_at) }}</time>
</div>
