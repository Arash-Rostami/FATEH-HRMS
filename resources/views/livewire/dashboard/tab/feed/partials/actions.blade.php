@props(['feed'])

<div class="flex items-center justify-between mt-4 px-1">
    {{-- Reactions --}}
    <div class="flex items-center gap-2">
        <button
            wire:click="toggleReaction({{ $feed->id }}, '❤️')"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-full transition-all duration-300 {{ $feed->reactions->where('user_id', auth()->id())->where('emoji', '❤️')->isNotEmpty() ? 'bg-red-500/10 text-red-500 border-red-500/20' : 'hover:bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)]' }}"
        >
            <span class="material-symbols-rounded text-[20px] {{ $feed->reactions->where('user_id', auth()->id())->where('emoji', '❤️')->isNotEmpty() ? 'fill-current' : '' }}">favorite</span>
            <span class="text-sm font-medium">{{ $feed->reactions->where('emoji', '❤️')->count() }}</span>
        </button>

        <button
            wire:click="openComments({{ $feed->id }})"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-full hover:bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-300"
        >
            <span class="material-symbols-rounded text-[20px]">chat_bubble</span>
            <span class="text-sm font-medium">{{ $feed->comments->count() }}</span>
        </button>
    </div>

    {{-- Share / More --}}
    <button class="p-2 rounded-full hover:bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] transition-colors">
        <span class="material-symbols-rounded">share</span>
    </button>
</div>
