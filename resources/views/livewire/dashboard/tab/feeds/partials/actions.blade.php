<div class="flex flex-col gap-3">
    <!-- Reaction Picker -->
    <div class="flex items-center gap-2 justify-between">
        @php
            $emojis = ['👍', '❤️', '😂', '😮', '😢', '💔', '👏'];
            $userReaction = $feed->reactions->firstWhere('user_id', auth()->id());
        @endphp

        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 -mx-1 px-1 custom-scrollbar scrollbar-hide">
            @foreach($emojis as $emoji)
                <button
                    wire:click="toggleReaction({{ $feed->id }}, '{{ $emoji }}')"
                    class="relative group w-10 h-10 flex items-center justify-center rounded-full text-lg transition-all duration-200
                    {{ $userReaction && $userReaction->emoji === $emoji
                        ? 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] border border-[var(--md-sys-color-primary)] shadow-sm'
                        : 'bg-[var(--md-sys-color-surface-variant)]/40 hover:bg-[var(--md-sys-color-secondary-container)] hover:text-[var(--md-sys-color-on-secondary-container)] border border-transparent' }}
                    active:scale-95"
                    title="{{ $emoji }}"
                >
                    <span class="transform transition-transform group-hover:rotate-12 group-hover:scale-110">{{ $emoji }}</span>

                    @if($userReaction && $userReaction->emoji === $emoji)
                        <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-[var(--md-sys-color-primary)] rounded-full animate-ping"></span>
                    @endif
                </button>
            @endforeach
        </div>

        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-surface-variant)]/30 border border-[var(--md-sys-color-outline-variant)]/10 text-[var(--md-sys-color-on-surface-variant)] text-xs font-bold">
            <span class="material-symbols-rounded text-base">thumb_up</span>
            <span>{{ $feed->reactions->count() }}</span>
        </div>
    </div>
</div>
