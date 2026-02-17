<div class="flex flex-col gap-3">
    <!-- Reaction Picker -->
    <div class="flex items-center gap-2 justify-between">
        @php
            $emojis = ['👍', '❤️', '😂', '😮', '😢', '💔', '👏'];
            $userReaction = $feed->reactions->firstWhere('user_id', auth()->id());
        @endphp

        <div class="flex items-center gap-1 overflow-x-auto pb-1 -mx-1 px-1 custom-scrollbar">
            @foreach($emojis as $emoji)
                <button
                    wire:click="toggleReaction({{ $feed->id }}, '{{ $emoji }}')"
                    class="relative group w-10 h-10 flex items-center justify-center rounded-full text-lg hover:bg-[var(--md-sys-color-secondary-container)] hover:scale-110 active:scale-95 transition-all
                    {{ $userReaction && $userReaction->emoji === $emoji ? 'bg-[var(--md-sys-color-primary-container)] border border-[var(--md-sys-color-primary)] shadow-md' : 'bg-[var(--md-sys-color-surface-container)] border border-transparent' }}"
                >
                    <span class="transform transition-transform group-hover:rotate-12">{{ $emoji }}</span>

                    @if($userReaction && $userReaction->emoji === $emoji)
                        <span class="absolute -top-1 -right-1 w-2 h-2 bg-[var(--md-sys-color-primary)] rounded-full animate-ping"></span>
                    @endif
                </button>
            @endforeach
        </div>

        <div class="flex items-center gap-1 text-[var(--md-sys-color-on-surface-variant)] text-xs font-medium">
            <span class="material-symbols-rounded text-[16px]">thumb_up</span>
            <span>{{ $feed->reactions->count() }}</span>
        </div>
    </div>
</div>
