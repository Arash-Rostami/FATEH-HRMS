<div wire:key="correspondence-diff" x-show="{{ $toggle ? 'showDiff' : 'true' }}" x-cloak class="text-sm leading-7">
    @foreach(($v['diff']['segments'] ?? []) as $segment)
        @if($segment['kind'] === 'del')
            <span wire:key="correspondence-diff-del-{{ $loop->index }}" class="text-[var(--md-sys-color-error)] line-through">{{ $segment['text'] }}</span>
        @elseif($segment['kind'] === 'add')
            <span wire:key="correspondence-diff-add-{{ $loop->index }}" class="text-[var(--md-sys-color-primary)]">{{ $segment['text'] }}</span>
        @else
            <span wire:key="correspondence-diff-same-{{ $loop->index }}">{{ $segment['text'] }}</span>
        @endif
    @endforeach
    @if($v['diff']['truncated'] ?? false)
        <p wire:key="correspondence-diff-truncated" class="text-xs text-[var(--md-sys-color-on-surface-variant)]">فقط بخشی از تفاوت نشان داده شد.</p>
    @endif
</div>
