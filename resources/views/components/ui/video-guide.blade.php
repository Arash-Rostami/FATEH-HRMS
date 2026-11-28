@use('App\Models\Media')
@props(['module', 'variant' => 'icon'])

@unless(tenantOff('knowledge:media'))
    @if($mediaId = Media::guideFor($module, auth()->user()))
        @if($variant === 'row')
            <a wire:key="video-guide-row-{{ $module }}" href="{{ route('knowledge', ['tab' => 'media', 'open' => $mediaId]) }}"
               target="_blank" rel="noopener" title="راهنمای ویدیویی" aria-label="راهنمای ویدیویی"
               class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[var(--md-sys-color-surface-container-high)]/50">
                <span class="material-symbols-rounded text-[16px]">play_circle</span>
                <span>راهنمای ویدیویی</span>
            </a>
        @else
            <a wire:key="video-guide-{{ $module }}" href="{{ route('knowledge', ['tab' => 'media', 'open' => $mediaId]) }}"
               target="_blank" rel="noopener" title="راهنمای ویدیویی" aria-label="راهنمای ویدیویی"
               class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">
                <span class="material-symbols-rounded text-lg">play_circle</span>
            </a>
        @endif
    @endif
@endunless
