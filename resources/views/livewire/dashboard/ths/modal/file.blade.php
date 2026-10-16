@php
    $name = $file['name'] ?? basename((string) ($file['file'] ?? ''));
    $isImage = Str::contains((string) ($file['file'] ?? ''), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
@endphp

@if($isImage)
    <a wire:key="{{ $prefix }}-img-{{ $index }}" href="{{ $file['file_url'] }}"
       data-fancybox="{{ $group }}" data-caption="{{ $name }}" title="{{ $name }}" aria-label="مشاهدهٔ {{ $name }}"
       class="group flex flex-col items-center justify-center w-24 h-24 rounded-2xl bg-[var(--md-sys-color-surface-container)] border border-[var(--md-sys-color-outline-variant)]/50 hover:border-[var(--md-sys-color-primary)] transition-all overflow-hidden relative shadow-sm hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]">
        <img src="{{ $file['file_url'] }}" alt="{{ $name }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
        <div class="absolute inset-0 bg-black/50 max-sm:opacity-100 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center backdrop-blur-[2px]">
            <span class="material-symbols-rounded text-white text-[24px]">zoom_in</span>
        </div>
        <span class="absolute bottom-0 inset-x-0 px-1.5 py-1 text-[9px] font-bold text-white bg-gradient-to-t from-black/55 to-transparent truncate text-left" dir="ltr">{{ $name }}</span>
    </a>
@else
    <a wire:key="{{ $prefix }}-doc-{{ $index }}" href="{{ $file['file_url'] }}" target="_blank" title="{{ $name }}"
       class="group inline-flex items-center gap-2 min-h-[44px] max-w-full px-3 py-1.5 rounded-xl bg-[var(--md-sys-color-surface-container)] border border-[var(--md-sys-color-outline-variant)]/50 hover:border-[var(--md-sys-color-primary)] text-xs font-bold text-[var(--md-sys-color-on-surface)] transition-all shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]">
        <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-surface-variant)] group-hover:text-[var(--md-sys-color-primary)] transition-colors">description</span>
        <span class="truncate max-w-[160px]" dir="auto">{{ $name }}</span>
        <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-on-surface-variant)] opacity-60">open_in_new</span>
    </a>
@endif
