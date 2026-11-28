@props(['icon', 'label', 'click' => '', 'checked' => null, 'role' => null, 'href' => null, 'download' => false, 'ai' => false])

@php
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
        @if($href)
            href="{{ $href }}" @if($download) download @endif x-on:click="open = false"
        @else
            type="button" x-on:click="{{ $click }}"
        @endif
        @if($checked) role="{{ $role ?? 'menuitemcheckbox' }}" :aria-checked="{{ $checked }}" @endif
        {{ $attributes }}
        class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium {{ $ai ? 'bg-[var(--ai-bg)] text-[var(--ai-text)] hover:brightness-95' : 'hover:bg-[var(--md-sys-color-surface-container-high)]/50' }}">
    <span class="material-symbols-rounded text-[16px] {{ $ai ? 'text-[var(--ai-color)]' : '' }}">{{ $icon }}</span>
    <span>{{ $label }}</span>
    @if($checked)
        <span class="ms-auto flex h-4 w-4 items-center justify-center rounded-sm bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]" x-show="{{ $checked }}" x-cloak>
            <span class="material-symbols-rounded !text-[12px] leading-none font-fill">check</span>
        </span>
    @endif
</{{ $tag }}>
