@if(count($attachments))
    @php($totalCount = collect($attachments)->sum(fn($group) => count($group['attachments'])))
    <div x-data="{ open: false }" class="rounded-2xl border border-[var(--md-sys-color-outline-variant)]/60 bg-[var(--md-sys-color-surface-container-low)] overflow-hidden">
        <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-6 py-3 text-right">
            <span class="flex items-center gap-2 text-xs font-bold text-[var(--md-sys-color-on-surface)]">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-primary)]">attach_file</span>
                پیوست‌ها
                <span class="px-2 py-0.5 rounded-md bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] text-[10px] font-black tabular-nums">{{ convertToPersian($totalCount) }}</span>
            </span>
            <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-on-surface-variant)] transition-transform duration-200" :class="open ? 'rotate-180' : ''">expand_more</span>
        </button>
        <div x-show="open" x-collapse x-cloak class="flex flex-wrap gap-2 px-6 pb-4 pt-3 border-t border-[var(--md-sys-color-outline-variant)]/40">
            @foreach($attachments as $group)
                @foreach($group['attachments'] as $attachment)
                    @php($isImage = str_starts_with($attachment['mime'] ?? '', 'image/'))
                    @php($fileIcon = $dmsPresenter->extensionIcon(pathinfo($attachment['path'] ?? '', PATHINFO_EXTENSION)))
                    <div class="flex flex-col gap-1 w-40 rounded-xl border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface)] p-2">
                        @if($isImage)
                            <a href="{{ rtrim(asset('storage'), '/') }}/{{ $attachment['path'] }}" data-fancybox="task-{{ $group['task_id'] }}-attachments" data-caption="{{ $attachment['name'] ?? '' }}"
                               class="flex items-center gap-1.5 text-[11px] font-medium text-[var(--md-sys-color-primary)] truncate">
                                <img src="{{ rtrim(asset('storage'), '/') }}/{{ $attachment['path'] }}" class="w-5 h-5 rounded object-cover shrink-0" alt="">
                                <span class="truncate">{{ $attachment['name'] ?? basename($attachment['path']) }}</span>
                            </a>
                        @else
                            <a href="{{ rtrim(asset('storage'), '/') }}/{{ $attachment['path'] }}" target="_blank"
                               class="flex items-center gap-1.5 text-[11px] font-medium text-[var(--md-sys-color-primary)] truncate">
                                <span class="material-symbols-rounded text-[14px] {{ $fileIcon['text'] }}">{{ $fileIcon['icon'] }}</span>
                                <span class="truncate">{{ $attachment['name'] ?? basename($attachment['path']) }}</span>
                            </a>
                        @endif
                        <a href="{{ route('tasks', ['open' => $group['task_id']]) }}" wire:navigate dir="auto"
                           class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] truncate hover:text-[var(--md-sys-color-primary)] hover:underline">
                            {{ superClean($group['task_title']) }}
                        </a>
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>
@endif
