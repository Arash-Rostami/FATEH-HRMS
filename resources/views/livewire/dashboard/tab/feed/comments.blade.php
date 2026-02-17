<div class="mt-2 pt-2 border-t border-[var(--md-sys-color-outline-variant)]/10">
    <div class="space-y-3 max-h-48 overflow-y-auto custom-scrollbar feed-scrollbar px-1">
        @forelse($comments as $comment)
            <div class="flex gap-2 group relative" wire:key="comment-{{$comment->id}}">
                <img src="{{ $comment->user->profile_photo_url }}" class="w-8 h-8 rounded-full border border-[var(--md-sys-color-outline-variant)]/30 shrink-0 object-cover" loading="lazy">

                <div class="flex-1">
                    <div class="bg-[var(--md-sys-color-surface-container-low)] rounded-xl rounded-tr-none px-3 py-2 text-sm text-[var(--md-sys-color-on-surface)] relative transition-colors shadow-sm">
                        <div class="flex justify-between items-center mb-0.5">
                            <span class="font-bold text-[11px] tracking-wide">{{ $comment->user->name }}</span>
                            <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">{{ $comment->created_at->diffForHumans() }}</span>
                        </div>

                        @if($this->editingCommentId === $comment->id)
                            <div class="flex flex-col gap-2">
                                <textarea wire:model.defer="editingContent" class="w-full bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline)] rounded-lg p-2 text-xs focus:ring-1 focus:ring-[var(--md-sys-color-primary)] outline-none resize-none" rows="2"></textarea>
                                <div class="flex justify-end gap-2">
                                    <button wire:click="updateComment" class="px-2 py-0.5 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] rounded-md text-[10px] font-bold">ذخیره</button>
                                    <button wire:click="$set('editingCommentId', null)" class="px-2 py-0.5 text-[var(--md-sys-color-primary)] rounded-md text-[10px] font-bold">لغو</button>
                                </div>
                            </div>
                        @else
                            <p class="leading-relaxed whitespace-pre-line text-xs">{{ $comment->content }}</p>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center py-4 text-[var(--md-sys-color-on-surface-variant)] opacity-60">
                <span class="text-xs italic">هنوز نظری ثبت نشده است.</span>
            </div>
        @endforelse
    </div>

    <!-- Input Area -->
    <div class="mt-3 flex gap-2 items-center sticky bottom-0 bg-[var(--md-sys-color-surface)] pt-2 pb-1 border-t border-[var(--md-sys-color-outline-variant)]/10 z-20">
        <img src="{{ auth()->user()->profile_photo_url }}" class="w-8 h-8 rounded-full border border-[var(--md-sys-color-outline-variant)]/30 shrink-0 object-cover">
        <div class="flex-1 relative rounded-full">
            <input
                type="text"
                wire:model.defer="newComments.{{ $feed->id }}"
                wire:keydown.enter="addComment({{ $feed->id }})"
                placeholder="نظر خود را بنویسید..."
                class="w-full bg-[var(--md-sys-color-surface-container-high)] border-none rounded-full ps-4 pe-10 py-1.5 text-xs focus:ring-1 focus:ring-[var(--md-sys-color-primary)] outline-none transition-shadow"
            >
            <button
                wire:click="addComment({{ $feed->id }})"
                class="absolute end-1 top-1 bottom-1 w-7 flex items-center justify-center rounded-full bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] active:scale-95 transition-transform"
                wire:loading.attr="disabled"
            >
                <span class="material-symbols-rounded text-sm rtl:rotate-180">send</span>
            </button>
        </div>
    </div>
</div>
