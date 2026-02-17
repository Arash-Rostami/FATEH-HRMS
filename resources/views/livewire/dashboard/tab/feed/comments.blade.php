<div class="mt-4 pt-4 border-t border-[var(--md-sys-color-outline-variant)]/20">
    <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-3 flex items-center gap-2">
        <span class="material-symbols-rounded text-[18px]">chat_bubble</span>
        <span>نظرات ({{ $comments->count() }})</span>
    </h3>

    <div class="space-y-4 max-h-60 overflow-y-auto custom-scrollbar">
        @forelse($comments as $comment)
            <div class="flex gap-3 group" wire:key="comment-{{$comment->id}}">
                <img src="{{ $comment->user->profile_photo_url }}" class="w-8 h-8 rounded-full border border-[var(--md-sys-color-outline-variant)]/30 shrink-0 object-cover">

                <div class="flex-1">
                    <div class="bg-[var(--md-sys-color-surface-container-low)] rounded-2xl rounded-tr-none px-3 py-2 text-sm text-[var(--md-sys-color-on-surface)] relative group-hover:bg-[var(--md-sys-color-surface-container)] transition-colors">
                        <div class="flex justify-between items-center mb-1">
                            <span class="font-bold text-xs">{{ $comment->user->name }}</span>
                            <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">{{ $comment->created_at->diffForHumans() }}</span>
                        </div>

                        @if($this->editingCommentId === $comment->id)
                            <div class="flex flex-col gap-2">
                                <textarea wire:model="editingContent" class="w-full bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline)] rounded-lg p-2 text-sm focus:ring-2 focus:ring-[var(--md-sys-color-primary)] outline-none resize-none" rows="2"></textarea>
                                <div class="flex justify-end gap-2">
                                    <button wire:click="updateComment" class="px-3 py-1 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] rounded-full text-xs font-bold hover:shadow-md transition-shadow">ذخیره</button>
                                    <button wire:click="$set('editingCommentId', null)" class="px-3 py-1 text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] rounded-full text-xs font-bold transition-colors">لغو</button>
                                </div>
                            </div>
                        @else
                            <p class="leading-relaxed whitespace-pre-line">{{ $comment->content }}</p>
                        @endif
                    </div>

                    @if(auth()->id() === $comment->user_id)
                        <div class="flex gap-3 mt-1 px-2 text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-0 group-hover:opacity-100 transition-opacity">
                            <button wire:click="startEditing({{ $comment->id }})" class="hover:text-[var(--md-sys-color-primary)] hover:underline">ویرایش</button>
                            <button wire:click="deleteComment({{ $comment->id }})" class="hover:text-[var(--md-sys-color-error)] hover:underline">حذف</button>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-4 text-[var(--md-sys-color-on-surface-variant)] text-xs italic">
                هنوز نظری ثبت نشده است. اولین نفر باشید!
            </div>
        @endforelse
    </div>

    <!-- Add Comment Input -->
    <div class="mt-4 flex gap-2 items-center sticky bottom-0 bg-[var(--md-sys-color-surface)] pt-2 pb-1 border-t border-[var(--md-sys-color-outline-variant)]/10 z-10 backdrop-blur-xl">
        <img src="{{ auth()->user()->profile_photo_url }}" class="w-8 h-8 rounded-full border border-[var(--md-sys-color-outline-variant)]/30 shrink-0 object-cover">
        <div class="flex-1 relative">
            <input
                type="text"
                wire:model="newComments.{{ $feed->id }}"
                wire:keydown.enter="addComment({{ $feed->id }})"
                placeholder="نظر خود را بنویسید..."
                class="w-full bg-[var(--md-sys-color-surface-container-high)] border-none rounded-full ps-4 pe-12 py-2 text-sm focus:ring-2 focus:ring-[var(--md-sys-color-primary)] outline-none placeholder-[var(--md-sys-color-on-surface-variant)]/50 transition-shadow shadow-inner"
            >
            <button
                wire:click="addComment({{ $feed->id }})"
                class="absolute end-1 top-1 bottom-1 w-10 flex items-center justify-center rounded-full bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:scale-105 active:scale-95 transition-all shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                wire:loading.attr="disabled"
            >
                <span class="material-symbols-rounded text-[18px] rtl:rotate-180">send</span>
            </button>
        </div>
    </div>
</div>
