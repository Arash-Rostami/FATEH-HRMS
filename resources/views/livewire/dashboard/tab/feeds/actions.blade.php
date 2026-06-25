<div class="flex items-center gap-2 justify-between">
    @php
        $userReaction = $feed->reactions->firstWhere('user_id', auth()->id());
        $selectedEmoji = $userReaction?->emoji;
    @endphp

    <div class="flex-shrink-0 flex items-center gap-1 h-9 px-2.5 rounded-lg bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)]">
        <span class="material-symbols-rounded text-[15px] leading-none">thumb_up</span>
        <span class="text-xs font-semibold tabular-nums leading-none">{{ $feed->reactions->count() }}</span>
    </div>

    <div class="flex items-center gap-0.5 py-1 overflow-visible"
         wire:key="reaction-strip-{{ $feed->id }}-{{ $selectedEmoji }}"
         x-data="feedReactions({{ $feed->id }}, '{{ $selectedEmoji }}')">

        <template x-for="emoji in emojis.slice(page * per, page * per + per)" :key="emoji">
            <button
                x-on:click="$wire.toggleReaction({{ $feed->id }}, emoji)"
                class="relative flex-shrink-0 w-9 h-9 flex items-center justify-center rounded-lg text-base transition-all duration-150 hover:bg-[var(--md-sys-color-secondary-container)] hover:scale-110 active:scale-95 bg-[var(--md-sys-color-surface-container)]"
                :class="emoji === selected ? '!bg-[var(--md-sys-color-primary-container)]' : ''"
            >
                <span class="leading-none select-none" x-text="emoji"></span>
                <span x-show="emoji === selected"
                      class="absolute -top-1 -right-1 w-2 h-2 rounded-sm bg-[var(--md-sys-color-primary)] animate-pulse z-10"></span>
            </button>
        </template>

        <button @click="next()"
                class="flex-shrink-0 w-9 h-9 flex items-center justify-center rounded-lg bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-secondary-container)] active:scale-95 transition-all duration-150">
            <span class="material-symbols-rounded text-[18px] leading-none">chevron_left</span>
        </button>
    </div>
</div>
