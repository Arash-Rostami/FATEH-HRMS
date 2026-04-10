@php
    $isNestedView = isset($isNested);
@endphp

<div
    class="{{ $isNestedView ? 'mt-3 mr-5 space-y-3 border-r-2 border-[var(--md-sys-color-primary)]/20 pr-3' : 'mt-2 flex flex-col h-full' }}"
    x-data="{ replyingTo: null }"
>
    {{-- Comments List --}}
    <div
        class="{{ $isNestedView ? 'space-y-3' : 'space-y-3 flex-1 overflow-y-auto feed-scrollbar px-2 py-1 min-h-0' }}">
        @forelse($comments ?? [] as $comment)
            @php
                $commentUser = $comment?->user;
                $hasProfilePhoto = !empty($commentUser?->profile?->profile_photo_url);
                $isUserOnline = $commentUser?->isOnline() ?? false;
            @endphp

            <div class="flex flex-col gap-1.5 group" wire:key="comment-{{ $comment?->id ?? 'unknown' }}">
                <div class="flex gap-2.5">
                    {{-- Avatar --}}
                    <div class="relative shrink-0 mt-0.5">
                        @if($hasProfilePhoto)
                            <img
                                src="{{ $commentUser->profile->profile_photo_url }}"
                                class="w-7 h-7 rounded-full border-2 border-[var(--md-sys-color-primary)]/20 object-cover"
                                alt="{{ $commentUser?->name ?? 'کاربر' }}"
                            >
                        @else
                            <div
                                class="w-7 h-7 rounded-full border-2 border-[var(--md-sys-color-primary)]/20 bg-[var(--md-sys-color-primary-container)] flex items-center justify-center">
                                <span
                                    class="material-symbols-rounded text-xs text-[var(--md-sys-color-on-primary-container)]">person</span>
                            </div>
                        @endif
                        @if($isUserOnline)
                            <div
                                class="absolute -bottom-0.5 -right-0.5 w-2 h-2 bg-emerald-500 border-2 border-[var(--md-sys-color-surface)] rounded-full"></div>
                        @endif
                    </div>

                    {{-- Comment Bubble --}}
                    <div class="flex-1 min-w-0">
                        <div class="
                            relative
                            bg-[var(--md-sys-color-surface-container-high)]
                            rounded-2xl rounded-tr-sm
                            px-3.5 py-2.5
                            shadow-sm
                            border border-[var(--md-sys-color-outline-variant)]/30
                            transition-all duration-200
                            group-hover:border-[var(--md-sys-color-primary)]/20
                            group-hover:shadow-md
                        ">
                            {{-- Header --}}
                            <div class="flex justify-between items-center mb-1 gap-2">
                                <span class="font-bold text-[11px] text-[var(--md-sys-color-primary)] truncate">
                                    {!! superClean($commentUser?->name) ?? 'کاربر حذف شده' !!}
                                </span>
                                <span class="text-[9px] opacity-50 shrink-0">
                                    {{ $comment?->created_at?->diffForHumans() ?? 'زمان نامشخص' }}
                                </span>
                            </div>

                            {{-- Edit Mode --}}
                            @if($editingCommentId === ($comment?->id ?? null))
                                <div class="flex flex-col gap-2 py-1">
                                    <textarea
                                        wire:model="commentForm.content"
                                        class="w-full bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-primary)]/30 rounded-xl p-2 text-xs focus:ring-1 focus:ring-[var(--md-sys-color-primary)] outline-none resize-none"
                                        rows="2"
                                    ></textarea>
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="updateComment"
                                                class="px-3 py-1 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] rounded-lg text-[10px] font-bold">
                                            ذخیره
                                        </button>
                                        <button wire:click="$set('editingCommentId', null)"
                                                class="px-3 py-1 text-[var(--md-sys-color-primary)] text-[10px] hover:bg-[var(--md-sys-color-primary-container)] rounded-lg">
                                            لغو
                                        </button>
                                    </div>
                                </div>
                            @else
                                <p class="leading-relaxed text-[12.5px] tracking-wide text-[var(--md-sys-color-on-surface)]">
                                    {!! superClean($comment?->content) ?? '' !!}
                                </p>
                            @endif

                            {{-- Actions --}}
                            <div
                                class="mt-2 flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-all duration-200 translate-y-1 group-hover:translate-y-0">
                                <button
                                    @click="replyingTo = replyingTo === {{ $comment->id }} ? null : {{ $comment->id }}"
                                    class="flex items-center gap-1 px-2 py-1 text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] rounded-full transition-colors text-[10px] font-medium"
                                    title="پاسخ"
                                >
                                    <span class="material-symbols-rounded !text-[12px]">reply</span>
                                    <span>پاسخ</span>
                                </button>
                                @if(auth()->id() === $comment->user_id)
                                    <button
                                        wire:click="startEditing({{ $comment->id }})"
                                        class="p-1.5 text-[var(--md-sys-color-secondary)] hover:bg-[var(--md-sys-color-secondary-container)] rounded-full transition-colors"
                                        title="ویرایش"
                                    >
                                        <span class="material-symbols-rounded !text-[12px]">edit</span>
                                    </button>
                                    <button
                                        @click="$dispatch('open-confirmation', {
                                            title: 'حذف نظر',
                                            message: 'آیا از حذف این نظر مطمئن هستید؟ این عملیات غیرقابل بازگشت است.',
                                            method: 'delete-comment-confirmed',
                                            params: {{ $comment->id }},
                                            type: 'js'
                                        })"
                                        class="p-1.5 text-[var(--md-sys-color-error)] hover:bg-[var(--md-sys-color-error-container)] rounded-full transition-colors"
                                        title="حذف"
                                    >
                                        <span class="material-symbols-rounded !text-[12px]">delete</span>
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- Reply Input --}}
                        <div
                            x-show="replyingTo === {{ $comment?->id ?? 'null' }}"
                            x-collapse
                            class="mt-2"
                            style="display: none;"
                        >
                            <div
                                class="flex items-center gap-2 bg-[var(--md-sys-color-primary-container)]/40 border border-[var(--md-sys-color-primary)]/30 rounded-2xl px-3 py-2">
                                <span
                                    class="material-symbols-rounded text-sm text-[var(--md-sys-color-primary)] shrink-0">subdirectory_arrow_left</span>
                                <input
                                    type="text"
                                    wire:model="replyComments.{{ $comment?->id ?? 'null' }}"
                                    wire:keydown.enter="addComment({{ $feed?->id ?? 'null' }}, {{ $comment?->id ?? 'null' }}); replyingTo = null"
                                    placeholder="پاسخ به {!! superClean($commentUser?->name) ?? 'کاربر' !!}..."
                                    class="flex-1 bg-transparent border-none text-xs focus:ring-0 outline-none placeholder:text-[var(--md-sys-color-primary)]/50 text-[var(--md-sys-color-on-surface)]"
                                >
                                <button
                                    wire:click="addComment({{ $feed?->id ?? 'null' }}, {{ $comment?->id ?? 'null' }}); replyingTo = null"
                                    class="w-7 h-7 rounded-full bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] flex items-center justify-center shrink-0 hover:brightness-110 active:scale-90 transition-all"
                                >
                                    <span class="material-symbols-rounded text-sm rotate-180">send</span>
                                </button>
                            </div>
                        </div>

                        {{-- Nested Replies --}}
                        @if($comment?->children?->isNotEmpty())
                            @include('livewire.dashboard.tab.feeds.partials.comments', [
                                'comments' => $comment->children,
                                'feed' => $feed,
                                'isNested' => true
                            ])
                        @endif
                    </div>
                </div>
            </div>
        @empty
            @if(!$isNestedView)
                <div class="py-8 text-center opacity-40 flex flex-col items-center gap-2">
                    <div
                        class="w-12 h-12 rounded-full bg-[var(--md-sys-color-surface-container-high)] flex items-center justify-center">
                        <span class="material-symbols-rounded text-2xl">chat_bubble</span>
                    </div>
                    <p class="text-xs">اولین نظر را شما بنویسید</p>
                </div>
            @endif
        @endforelse
    </div>

    {{-- MAIN COMMENT INPUT --}}
    @if(!$isNestedView)
        @auth
            @php
                $authUser = auth()?->user();
                $authHasPhoto = !empty($authUser?->profile?->profile_photo_url);
            @endphp

            <div class="
                shrink-0
                mt-3 mx-1 mb-1
                p-3
                bg-[var(--md-sys-color-primary-container)]/30
                border-2 border-[var(--md-sys-color-primary)]/40
                rounded-2xl
                shadow-[0_0_0_4px_color-mix(in_srgb,var(--md-sys-color-primary)_8%,transparent)]
                focus-within:border-[var(--md-sys-color-primary)]/80
                focus-within:bg-[var(--md-sys-color-primary-container)]/50
                focus-within:shadow-[0_0_0_4px_color-mix(in_srgb,var(--md-sys-color-primary)_15%,transparent)]
                transition-all duration-300
            ">
                {{-- Label --}}
                <div class="flex items-center gap-1.5 mb-2 px-1">
                    <span class="material-symbols-rounded text-sm text-[var(--md-sys-color-primary)]">edit_note</span>
                    <span class="text-[10px] font-semibold text-[var(--md-sys-color-primary)] tracking-wide">نظر خود را بنویسید</span>
                </div>

                <div class="flex gap-2 items-center">
                    {{-- Avatar --}}
                    <div class="relative shrink-0">
                        @if($authHasPhoto)
                            <img
                                src="{{ $authUser->profile->profile_photo_url }}"
                                class="w-8 h-8 rounded-full object-cover border-2 border-[var(--md-sys-color-primary)]/30 shadow-sm"
                                alt="{{ $authUser?->name ?? 'کاربر' }}"
                            >
                        @else
                            <div
                                class="w-8 h-8 rounded-full bg-[var(--md-sys-color-primary-container)] border-2 border-[var(--md-sys-color-primary)]/30 flex items-center justify-center shadow-sm">
                                <span
                                    class="material-symbols-rounded text-sm text-[var(--md-sys-color-on-primary-container)]">person</span>
                            </div>
                        @endif
                        @if($authUser?->isOnline() ?? false)
                            <div
                                class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-500 border-2 border-[var(--md-sys-color-surface)] rounded-full"></div>
                        @endif
                    </div>

                    {{-- Input --}}
                    <div class="flex-1 relative">
                        <input
                            type="text"
                            wire:model="newComments.{{ $feed?->id ?? 'null' }}"
                            wire:keydown.enter="addComment({{ $feed?->id ?? 'null' }})"
                            placeholder="نظرت رو بنویس..."
                            class="
                                w-full
                                bg-[var(--md-sys-color-surface)]
                                border border-[var(--md-sys-color-outline-variant)]/50
                                rounded-xl
                                px-3 py-2
                                text-xs
                                text-[var(--md-sys-color-on-surface)]
                                placeholder:text-[var(--md-sys-color-on-surface-variant)]/60
                                focus:ring-1 focus:ring-[var(--md-sys-color-primary)]
                                focus:border-[var(--md-sys-color-primary)]
                                outline-none
                                transition-all duration-200
                                shadow-inner
                            "
                        >
                    </div>

                    {{-- Send Button --}}
                    <button
                        wire:click="addComment({{ $feed?->id ?? 'null' }})"
                        class="
                            shrink-0
                            w-9 h-9
                            flex items-center justify-center
                            rounded-xl
                            bg-[var(--md-sys-color-primary)]
                            text-[var(--md-sys-color-on-primary)]
                            shadow-md
                            hover:brightness-110
                            active:scale-90
                            transition-all duration-200
                        "
                        title="ارسال"
                    >
                        <span class="material-symbols-rounded text-base rotate-180">send</span>
                    </button>
                </div>
            </div>
        @else
            <div
                class="mt-3 mx-1 p-3 bg-[var(--md-sys-color-surface-container-low)] rounded-2xl border-2 border-dashed border-[var(--md-sys-color-primary)]/30 text-center">
                <span
                    class="material-symbols-rounded text-lg text-[var(--md-sys-color-primary)]/50 block mb-1">login</span>
                <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">برای ثبت نظر وارد شوید</p>
            </div>
        @endauth
    @endif
</div>
