@php
    $isMine = $reply->user_id === auth()->id();
    $userName = $reply->user?->name ?? '—';
    $userInitial = $reply->user ? mb_substr($reply->user->name, 0, 1) : '—';
    $createdAt = toJalali($reply->created_at, 'H:i j F');
@endphp

<div class="relative" wire:key="task-reply-{{ $reply->id }}">
    <span class="absolute top-3 right-[13px] w-[11px] h-[11px] rounded-full border-2 border-[var(--md-sys-color-surface)] z-10 bg-[var(--md-sys-color-primary)]"></span>

    <div class="relative mr-7 px-3.5 py-3 rounded-2xl border bg-[var(--md-sys-color-surface)] transition-colors border-[var(--md-sys-color-outline-variant)]/50 hover:border-[var(--md-sys-color-outline-variant)]/80">
        <div class="flex items-start gap-3">
            <div class="shrink-0 w-8 h-8 rounded-full overflow-hidden ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] flex items-center justify-center text-xs font-bold"
                @class([
                    'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' => $isMine,
                    'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]' => !$isMine,
                ])>
                <span dir="auto">{{ $userInitial }}</span>
            </div>

            <div class="flex-1 min-w-0">
                <header class="flex items-center flex-wrap gap-x-2 gap-y-1 mb-1">
                    <span class="text-sm font-bold text-[var(--md-sys-color-on-surface)]" dir="auto">{{ $userName }}</span>
                    <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-70">·</span>
                    <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-70" dir="ltr">{{ $createdAt }}</span>
                </header>

                @if($reply->body)
                    <p class="text-sm leading-6 break-words whitespace-pre-wrap text-[var(--md-sys-color-on-surface)]" dir="auto">{{ $reply->body }}</p>
                @endif

                @if(!empty($reply->files))
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        @foreach($reply->files as $file)
                            @php
                                $meta = $presenter->replyAttachmentMeta($file);
                                $fileName = $file['name'] ?? 'فایل';
                            @endphp

                            @if($meta['isImage'])
                                <a href="{{ $meta['url'] }}" data-fancybox="task-reply-{{ $reply->id }}" data-caption="{{ $fileName }}"
                                   class="block w-20 h-20 rounded-lg overflow-hidden border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
                                    <img src="{{ $meta['url'] }}" alt="{{ $fileName }}" loading="lazy" class="w-full h-full object-cover">
                                </a>
                            @else
                                <a href="{{ $meta['url'] }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[10px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--tool-gold-bg)] text-[var(--tool-gold-text)] hover:brightness-110 active:scale-95 transition">
                                    <span class="material-symbols-rounded text-[12px]">attach_file</span>
                                    <span dir="auto">{{ $fileName }}</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
