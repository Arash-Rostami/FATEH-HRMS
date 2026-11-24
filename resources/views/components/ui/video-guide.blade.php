@use('App\Models\Media')
@props(['module'])

@unless(tenantOff('knowledge:media'))
    @if($mediaId = Media::guideFor($module, auth()->user()))
        <a wire:key="video-guide-{{ $module }}" href="{{ route('knowledge', ['tab' => 'media', 'open' => $mediaId]) }}"
           target="_blank" rel="noopener" title="راهنمای ویدیویی" aria-label="راهنمای ویدیویی"
           class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">
            <span class="material-symbols-rounded text-lg">play_circle</span>
        </a>
    @endif
@endunless
