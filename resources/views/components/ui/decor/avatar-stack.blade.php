@props([
    'users' => [],
    'max' => 3,
    'total' => null,
    'compact' => false,
    'suffix' => '',
])

@php
    $shown = array_slice($users, 0, $max);
    $overflow = ($total ?? count($users)) - count($shown);
@endphp

@if(!empty($shown))
    <div {{ $attributes->merge(['class' => 'flex items-center -space-x-1.5 rtl:space-x-reverse shrink-0']) }}>
        @if($compact)
            @foreach($shown as $item)
                <span class="block w-4 h-4 shrink-0">
                    <x-ui.avatar :existingImage="$item['avatar_url']" :alt="$item['name']" class="!rounded-[28%] border border-[var(--md-sys-color-surface)] ring-1 ring-[var(--md-sys-color-outline-variant)]"/>
                </span>
            @endforeach
            @if($overflow > 0)
                <span class="w-4 h-4 text-[8px] rounded-[28%] bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] font-bold flex items-center justify-center border border-[var(--md-sys-color-surface)] ring-1 ring-[var(--md-sys-color-outline-variant)]">
                    +{{ convertToPersian($overflow) }}{{ $suffix }}
                </span>
            @endif
        @else
            @foreach($shown as $item)
                <x-ui.avatar :existingImage="$item['avatar_url']" :alt="$item['name']" size="xs" :online="$item['online'] ?? null"/>
            @endforeach
            @if($overflow > 0)
                <span class="w-5 h-5 text-[9px] rounded-[28%] bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] font-bold flex items-center justify-center border border-[var(--md-sys-color-surface)] ring-1 ring-[var(--md-sys-color-outline-variant)]">
                    +{{ convertToPersian($overflow) }}{{ $suffix }}
                </span>
            @endif
        @endif
    </div>
@endif
