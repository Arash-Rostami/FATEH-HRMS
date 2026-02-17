<div class="{{ isset($isNested) ? 'mt-3 space-y-3' : 'mt-4 pt-4 border-t border-[var(--md-sys-color-outline-variant)]/20' }}" x-data="{ replyingTo: null }">
    <div class="{{ isset($isNested) ? '' : 'space-y-4 max-h-60 overflow-y-auto custom-scrollbar feed-scrollbar px-2' }}">
        @forelse($comments ?? [] as $comment)
            <div class="flex gap-3 group animate-slide-in relative" wire:key="comment-{{ $comment->id ?? '' }}">
                <img src="{{ optional($comment->user)->profile_photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode(optional($comment->user)->name ?? 'U') }}"
                     class="w-8 h-8 rounded-full border border-[var(--md-sys-color-outline-variant)]/30 shrink-0 object-cover shadow-sm"
                     alt="Profile">

                <div class="flex-1 min-w-0">
                    <div class="bg-[var(--md-sys-color-surface-container-low)] rounded-3xl rounded-tr-none px-4 py-3 text-sm text-[var(--md-sys-color-on-surface)] relative group-hover:bg-[var(--md-sys-color-surface-container)] transition-all duration-300 shadow-sm hover:shadow-md">
                        <div class="flex justify-between items-center mb-1">
                            <span class="font-bold text-xs tracking-wide">{{ optional($comment->user)->name ?? 'کاربر حذف شده' }}</span>
                            <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">
                                {{ optional($comment->created_at)->diffForHumans() ?? '' }}
                            </span>
                        </div>

                        @if(($editingCommentId ?? null) === ($comment->id ?? null))
                            <div class="flex flex-col gap-2">
                                <textarea wire:model="editingContent" class="w-full bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline)] rounded-lg p-2 text-sm focus:ring-2 focus:ring-[var(--md-sys-color-primary)] outline-none resize-none" rows="2"></textarea>
                                <div class="flex justify-end gap-2">
                                    <button wire:click="updateComment" class="px-3 py-1 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] rounded-full text-xs font-bold hover:shadow-md transition-shadow">ذخیره</button>
                                    <button wire:click="$set('editingCommentId', null)" class="px-3 py-1 text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] rounded-full text-xs font-bold transition-colors">لغو</button>
                                </div>
                            </div>
                        @else
                            <p class="leading-relaxed whitespace-pre-line text-base font-normal tracking-wide">{{ $comment->content ?? '' }}</p>
                        @endif

                        <div class="mt-2 flex items-center justify-end gap-3 text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-70 group-hover:opacity-100 transition-opacity">
                            <button @click="replyingTo = replyingTo === {{ $comment->id ?? 'null' }} ? null : {{ $comment->id ?? 'null' }}" class="hover:text-[var(--md-sys-color-primary)] font-bold transition-colors">پاسخ</button>

                            @if(auth()->check() && auth()->id() === ($comment->user_id ?? null))
                                <button wire:click="startEditing({{ $comment->id ?? 'null' }})" class="hover:text-[var(--md-sys-color-primary)] transition-colors">ویرایش</button>
                                <button wire:click="deleteComment({{ $comment->id ?? 'null' }})" class="hover:text-[var(--md-sys-color-error)] transition-colors">حذف</button>
                            @endif
                        </div>
                    </div>

                    <div x-show="replyingTo === {{ $comment->id ?? 'null' }}" x-collapse class="mt-2 pl-2" style="display: none;">
                        <div class="relative group">
                            <input
                                type="text"
                                wire:model="replyComments.{{ $comment->id ?? 'null' }}"
                                wire:keydown.enter="addComment({{ $feed->id ?? 'null' }}, {{ $comment->id ?? 'null' }}); replyingTo = null"
                                placeholder="پاسخ به {{ optional($comment->user)->name ?? '' }}..."
                                class="w-full pl-10 pr-4 py-2 text-xs rounded-full bg-[var(--md-sys-color-surface-container-high)] border-none focus:ring-1 focus:ring-[var(--md-sys-color-primary)] outline-none transition-all placeholder:text-[var(--md-sys-color-outline)]"
                            >
                            <button
                                wire:click="addComment({{ $feed->id ?? 'null' }}, {{ $comment->id ?? 'null' }}); replyingTo = null"
                                class="absolute left-1 top-1/2 -translate-y-1/2 w-7 h-7 rounded-full text-[var(--md-sys-color-primary)] flex items-center justify-center hover:bg-[var(--md-sys-color-primary-container)] transition-colors"
                            >
                                <span class="material-symbols-rounded text-[18px] rotate-180">send</span>
                            </button>
                        </div>
                    </div>

                    @if(optional($comment->children)->isNotEmpty())
                        <div class="mr-4 border-r border-[var(--md-sys-color-outline-variant)]/20 pr-3">
                            @include('livewire.dashboard.tab.feeds.comments', ['comments' => $comment->children ?? [], 'feed' => $feed ?? null, 'isNested' => true])
                        </div>
                    @endif
                </div>
            </div>
        @empty
            @if(!isset($isNested))
                <div class="flex flex-col items-center justify-center py-8 text-[var(--md-sys-color-on-surface-variant)] opacity-60">
                    <span class="material-symbols-rounded text-4xl mb-2">chat_bubble_outline</span>
                    <span class="text-sm italic">هنوز نظری ثبت نشده است. اولین نفر باشید!</span>
                </div>
            @endif
        @endforelse
    </div>

    @if(!isset($isNested))
        @auth
            <div class="mt-4 flex gap-3 items-center sticky bottom-0 bg-[var(--md-sys-color-surface)]/90 pt-3 pb-2 border-t border-[var(--md-sys-color-outline-variant)]/10 z-20 backdrop-blur-xl rounded-b-3xl -mx-4 px-4">
                <img src="{{ optional(auth()->user())->profile_photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode(optional(auth()->user())->name ?? 'U') }}"
                     class="w-9 h-9 rounded-full border border-[var(--md-sys-color-outline-variant)]/30 shrink-0 object-cover shadow-sm ring-2 ring-[var(--md-sys-color-surface)]">
                <div class="flex-1 relative group focus-within:ring-2 focus-within:ring-[var(--md-sys-color-primary)]/50 rounded-full transition-all duration-300">
                    <input
                        type="text"
                        wire:model="newComments.{{ $feed->id ?? 'null' }}"
                        wire:keydown.enter="addComment({{ $feed->id ?? 'null' }})"
                        placeholder="نظر خود را بنویسید..."
                        class="w-full bg-[var(--md-sys-color-surface-container-high)] border-none rounded-full ps-5 pe-14 py-2.5 text-sm focus:ring-0 outline-none placeholder-[var(--md-sys-color-on-surface-variant)]/60 transition-all shadow-inner"
                    >
                    <button
                        wire:click="addComment({{ $feed->id ?? 'null' }})"
                        class="absolute end-1.5 top-1.5 bottom-1.5 w-9 flex items-center justify-center rounded-full bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:scale-110 active:scale-95 transition-all shadow-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)]"
                        wire:loading.attr="disabled"
                    >
                        <span class="material-symbols-rounded text-xl rtl:rotate-180">send</span>
                    </button>
                </div>
            </div>
        @else
            <div class="mt-4 p-4 bg-[var(--md-sys-color-surface-container-low)] rounded-2xl text-center">
                <p class="text-sm text-[var(--md-sys-color-on-surface-variant)]">
                    برای ثبت نظر <a href="{{ route('login') }}" class="text-[var(--md-sys-color-primary)] font-bold hover:underline">وارد شوید</a>.
                </p>
            </div>
        @endauth
    @endif
</div>
