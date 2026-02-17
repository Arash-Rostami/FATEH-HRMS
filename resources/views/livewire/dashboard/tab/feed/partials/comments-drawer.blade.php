{{-- Slide-Over Comments Drawer --}}
<div
    x-cloak
    x-show="$wire.commentDrawerOpen"
    class="fixed inset-0 z-[100] flex justify-end"
    style="display: none;"
>
    {{-- Backdrop --}}
    <div
        x-show="$wire.commentDrawerOpen"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="$wire.closeComments()"
        class="absolute inset-0 bg-black/40 backdrop-blur-sm"
    ></div>

    {{-- Panel --}}
    <div
        x-show="$wire.commentDrawerOpen"
        x-transition:enter="transform transition ease-in-out duration-300 sm:duration-500"
        x-transition:enter-start="translate-x-full rtl:-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition ease-in-out duration-300 sm:duration-500"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full rtl:-translate-x-full"
        class="relative w-full max-w-md h-full bg-[var(--md-sys-color-surface)] shadow-2xl flex flex-col border-l border-[var(--md-sys-color-outline-variant)]/20"
    >
        {{-- Header --}}
        <div class="px-6 py-4 border-b border-[var(--md-sys-color-outline-variant)]/20 flex items-center justify-between bg-[var(--md-sys-color-surface-container-low)]">
            <h2 class="text-lg font-bold text-[var(--md-sys-color-on-surface)] flex items-center gap-2">
                <span class="material-symbols-rounded text-[var(--md-sys-color-primary)]">forum</span>
                Comments
            </h2>
            <button wire:click="closeComments" class="p-2 rounded-full hover:bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] transition-colors">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        {{-- Scrollable List --}}
        <div class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-6">
            @if($this->selectedFeed)
                @forelse($this->selectedFeed->comments as $comment)
                    <div class="flex gap-4 group">
                         <img
                            src="{{ $comment->user->profile_photo_url ?? 'https://ui-avatars.com/api/?name='.urlencode($comment->user->name ?? 'User') }}"
                            class="w-10 h-10 rounded-full object-cover shrink-0 border border-[var(--md-sys-color-outline-variant)]"
                        >
                        <div class="flex-1 space-y-1">
                            <div class="bg-[var(--md-sys-color-surface-container-high)] rounded-2xl px-4 py-3 rounded-tl-none relative group-hover:bg-[var(--md-sys-color-surface-container-highest)] transition-colors">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-bold text-sm text-[var(--md-sys-color-on-surface)]">{{ $comment->user->name }}</span>
                                    <span class="text-xs text-[var(--md-sys-color-on-surface-variant)]">{{ $comment->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] leading-relaxed">{{ $comment->content }}</p>
                            </div>
                            <div class="flex items-center gap-4 px-2">
                                <button
                                    wire:click="setReply({{ $comment->id }})"
                                    class="text-xs font-medium text-[var(--md-sys-color-primary)] hover:underline flex items-center gap-1"
                                >
                                    Reply
                                </button>
                                {{-- Like comment button placeholder --}}
                            </div>

                            {{-- Replies --}}
                            @if($comment->replies->count() > 0)
                                <div class="mt-3 pl-4 space-y-3 border-l-2 border-[var(--md-sys-color-outline-variant)]/30">
                                    @foreach($comment->replies as $reply)
                                        <div class="flex gap-3">
                                            <img src="{{ $reply->user->profile_photo_url ?? 'https://ui-avatars.com/api/?name='.urlencode($reply->user->name ?? 'U') }}" class="w-8 h-8 rounded-full">
                                            <div class="bg-[var(--md-sys-color-surface-container)] rounded-xl px-3 py-2 flex-1">
                                                <div class="flex justify-between items-center">
                                                    <span class="font-bold text-xs">{{ $reply->user->name }}</span>
                                                </div>
                                                <p class="text-xs mt-1">{{ $reply->content }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-12 text-[var(--md-sys-color-on-surface-variant)] opacity-60">
                        <span class="material-symbols-rounded text-6xl mb-4">chat_bubble_outline</span>
                        <p class="text-sm">No comments yet. Be the first!</p>
                    </div>
                @endforelse
            @else
                 <div class="flex justify-center p-8">
                     <span class="loading loading-spinner text-primary"></span>
                 </div>
            @endif
        </div>

        {{-- Input Area --}}
        <div class="p-4 bg-[var(--md-sys-color-surface)] border-t border-[var(--md-sys-color-outline-variant)]/20">
            @if($replyingToId)
                <div class="flex justify-between items-center px-4 py-2 bg-[var(--md-sys-color-primary-container)] rounded-lg mb-3 text-xs">
                    <span class="text-[var(--md-sys-color-on-primary-container)]">Replying to comment #{{ $replyingToId }}</span>
                    <button wire:click="$set('replyingToId', null)" class="text-[var(--md-sys-color-on-primary-container)] hover:bg-black/10 rounded-full p-1">
                        <span class="material-symbols-rounded text-sm">close</span>
                    </button>
                </div>
            @endif

            <form wire:submit.prevent="addComment" class="flex items-end gap-2">
                <div class="flex-1 relative">
                    <textarea
                        wire:model="newComment"
                        rows="1"
                        placeholder="Write a comment..."
                        class="w-full bg-[var(--md-sys-color-surface-container-high)] border-0 rounded-2xl py-3 px-4 text-sm text-[var(--md-sys-color-on-surface)] placeholder-[var(--md-sys-color-on-surface-variant)]/50 focus:ring-2 focus:ring-[var(--md-sys-color-primary)] resize-none custom-scrollbar transition-all"
                        @input="$el.style.height = ''; $el.style.height = $el.scrollHeight + 'px'"
                    ></textarea>
                </div>
                <button
                    type="submit"
                    class="p-3 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] rounded-full hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                    wire:loading.attr="disabled"
                >
                    <span class="material-symbols-rounded" wire:loading.remove target="addComment">send</span>
                    <span class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" wire:loading target="addComment"></span>
                </button>
            </form>
        </div>
    </div>
</div>
