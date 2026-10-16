@php
    $isMine = $reply->user_id === auth()->id();
@endphp

<div wire:key="reply-{{ $reply->id }}" class="flex items-start gap-2.5 {{ $isMine ? 'flex-row-reverse' : '' }} {{ ($animateIn ?? false) ? 'animate-slide-up-fade' : '' }}">
    @if($reply->user)
        <div class="flex-shrink-0">
            <x-dashboard.user-avatar :user="$reply->user" size="sm"/>
        </div>
    @else
        <div class="flex-shrink-0 w-7 h-7 rounded-lg overflow-hidden shadow-sm">
            <div class="w-full h-full flex items-center justify-center text-[11px] font-bold bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">—</div>
        </div>
    @endif

    <div class="max-w-[80%] rounded-2xl px-3.5 py-2.5 shadow-sm"
         @class([
             'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]' => $isMine,
             'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)]' => !$isMine,
         ])>
        <div class="flex items-center gap-2 mb-1">
            <span class="text-[11px] font-bold">{{ $reply->user?->name ?? '—' }}</span>
            <span class="text-[10px] opacity-60" dir="ltr">{{ $presenter->replyTime($reply->created_at) }}</span>
        </div>

        @if($reply->body)
            <p class="text-sm leading-relaxed whitespace-pre-wrap [overflow-wrap:anywhere]">{{ $reply->body }}</p>
        @endif

        @if(!empty($reply->files))
            <div class="flex flex-wrap gap-1.5 mt-2">
                @foreach($reply->files as $file)
                    @php
                        $url = \App\Models\Reply::resolvePublicAssetUrl($file['path'] ?? null);
                        $isImage = str_starts_with($file['mime'] ?? '', 'image/');
                    @endphp
                    @if($isImage)
                        <a href="{{ $url }}" data-fancybox="reply-{{ $reply->id }}" data-caption="{{ $file['name'] ?? '' }}"
                           class="block w-20 h-20 rounded-lg overflow-hidden border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
                            <img src="{{ $url }}" alt="{{ $file['name'] ?? '' }}" loading="lazy" class="w-full h-full object-cover">
                        </a>
                    @else
                        <a href="{{ $url }}" target="_blank"
                           class="flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] bg-[var(--md-sys-color-surface)]/60 hover:bg-[var(--md-sys-color-surface)] transition">
                            <span class="material-symbols-rounded text-[13px]">attachment</span>
                            {{ $file['name'] ?? 'فایل' }}
                        </a>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>
