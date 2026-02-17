@php
    $isNestedView = isset($isNested);
@endphp

<div
        class="{{ $isNestedView ? 'mt-4 mr-6 space-y-4 border-r-2 border-[var(--md-sys-color-outline-variant)]/20 pr-4' : 'mt-4' }}"
        x-data="{ replyingTo: null }"
>
    {{-- Comments List --}}
    <div class="{{ $isNestedView ? '' : 'space-y-4 max-h-[40vh] overflow-y-auto feed-scrollbar px-1' }}">
        @forelse($comments ?? [] as $comment)
            @php
                $commentUser = $comment?->user;
                $hasProfilePhoto = !empty($commentUser?->profile?->profile_photo_url);
                $isUserOnline = $commentUser?->isOnline() ?? false;
            @endphp

            <div class="flex flex-col gap-2 group" wire:key="comment-{{ $comment?->id ?? 'unknown' }}">
                <div class="flex gap-3">
                    {{-- Avatar with Online Indicator --}}
                    <div class="relative shrink-0">
                        @if($hasProfilePhoto)
                            <img
                                    src="{{ $commentUser->profile->profile_photo_url }}"
                                    class="w-8 h-8 rounded-full border border-[var(--md-sys-color-outline-variant)]/30 object-cover shadow-sm"
                                    alt="{{ $commentUser?->name ?? 'کاربر' }}"
                            >
                        @else
                            <div class="w-8 h-8 rounded-full border border-[var(--md-sys-color-outline-variant)]/30 bg-[var(--md-sys-color-surface-container)] flex items-center justify-center shadow-sm">
                                <span class="material-symbols-rounded text-sm text-[var(--md-sys-color-on-surface-variant)]">person</span>
                            </div>
                        @endif

                        @if($isUserOnline)
                            <div class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-green-500 border-2 border-[var(--md-sys-color-surface)] rounded-full"></div>
                        @endif
                    </div>

                    {{-- Comment Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="bg-[var(--md-sys-color-surface-container-high)] rounded-2xl rounded-tr-none px-4 py-3 text-sm text-[var(--md-sys-color-on-surface)] shadow-sm transition-all hover:shadow-md">

                            {{-- Header: Name & Time --}}
                            <div class="flex justify-between items-center mb-1">
                                <span class="font-bold text-[11px] text-[var(--md-sys-color-primary)]">
                                    {!! superClean($commentUser?->name) ?? 'کاربر حذف شده' !!}
                                </span>
                                <span class="text-[9px] opacity-60">
                                    {{ $comment?->created_at?->diffForHumans() ?? 'زمان نامشخص' }}
                                </span>
                            </div>

                            {{-- Edit Mode --}}
                            @if($editingCommentId === ($comment?->id ?? null))
                                <div class="flex flex-col gap-2 py-1">
                                    <textarea
                                            wire:model="editingContent"
                                            class="w-full bg-[var(--md-sys-color-surface)] border-none rounded-xl p-2 text-xs focus:ring-1 focus:ring-[var(--md-sys-color-primary)] outline-none resize-none"
                                            rows="2"
                                    ></textarea>
                                    <div class="flex justify-end gap-2">
                                        <button
                                                wire:click="updateComment"
                                                class="px-3 py-1 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] rounded-lg text-[10px] font-bold"
                                        >
                                            ذخیره
                                        </button>
                                        <button
                                                wire:click="$set('editingCommentId', null)"
                                                class="px-3 py-1 text-[var(--md-sys-color-primary)] text-[10px]"
                                        >
                                            لغو
                                        </button>
                                    </div>
                                </div>
                            @else
                                {{-- Comment Text --}}
                                <p class="leading-relaxed text-[13px] tracking-wide">
                                    {!! superClean($comment?->content) ?? '' !!}
                                </p>
                            @endif

                            {{-- Actions --}}
                            <div class="mt-2 flex items-center justify-end gap-3 text-[10px] font-bold opacity-0 group-hover:opacity-100 transition-opacity">
                                <button
                                        @click="replyingTo = replyingTo === {{ $comment?->id ?? 'null' }} ? null : {{ $comment?->id ?? 'null' }}"
                                        class="text-[var(--md-sys-color-primary)]"
                                >
                                    پاسخ
                                </button>

                                @if(auth()?->id() === ($comment?->user_id ?? null))
                                    <button
                                            wire:click="startEditing({{ $comment?->id ?? 'null' }})"
                                            class="text-[var(--md-sys-color-secondary)]"
                                    >
                                        ویرایش
                                    </button>
                                    <button
                                            wire:click="deleteComment({{ $comment?->id ?? 'null' }})"
                                            class="text-[var(--md-sys-color-error)]"
                                    >
                                        حذف
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
                            <div class="relative">
                                <input
                                        type="text"
                                        wire:model="replyComments.{{ $comment?->id ?? 'null' }}"
                                        wire:keydown.enter="addComment({{ $feed?->id ?? 'null' }}, {{ $comment?->id ?? 'null' }}); replyingTo = null"
                                        placeholder="در پاسخ به {!! superClean($commentUser?->name) ?? 'کاربر' !!}..."
                                        class="w-full pl-10 pr-4 py-2 text-xs rounded-full bg-[var(--md-sys-color-surface-container-highest)] border-none focus:ring-1 focus:ring-[var(--md-sys-color-primary)] outline-none"
                                >
                                <button
                                        wire:click="addComment({{ $feed?->id ?? 'null' }}, {{ $comment?->id ?? 'null' }}); replyingTo = null"
                                        class="absolute left-1 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full text-[var(--md-sys-color-primary)] flex items-center justify-center"
                                >
                                    <span class="material-symbols-rounded text-lg rotate-180">send</span>
                                </button>
                            </div>
                        </div>

                        {{-- Nested Replies --}}
                        @if($comment?->children?->isNotEmpty())
                            @include('livewire.dashboard.tab.feeds.comments', [
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
                <div class="py-10 text-center opacity-40">
                    <span class="material-symbols-rounded text-3xl">chat_bubble</span>
                    <p class="text-xs mt-2">اولین نظر را شما بنویسید</p>
                </div>
            @endif
        @endforelse
    </div>

    {{-- Main Comment Input --}}
    @if(!$isNestedView)
        @auth
            @php
                $authUser = auth()?->user();
                $authHasPhoto = !empty($authUser?->profile?->profile_photo_url);
            @endphp

            <div class="mt-4 flex gap-2 items-center bg-[var(--md-sys-color-surface-container)] p-2 rounded-full border border-[var(--md-sys-color-outline-variant)]/10 shadow-inner">
                {{-- Current User Avatar --}}
                <div class="relative shrink-0">
                    @if($authHasPhoto)
                        <img
                                src="{{ $authUser->profile->profile_photo_url }}"
                                class="w-8 h-8 rounded-full object-cover shadow-sm"
                                alt="{{ $authUser?->name ?? 'کاربر' }}"
                        >
                    @else
                        <div class="w-8 h-8 rounded-full bg-[var(--md-sys-color-surface-container-high)] flex items-center justify-center shadow-sm">
                            <span class="material-symbols-rounded text-sm text-[var(--md-sys-color-on-surface-variant)]">person</span>
                        </div>
                    @endif

                    @if($authUser?->isOnline() ?? false)
                        <div class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-green-500 border-2 border-[var(--md-sys-color-surface)] rounded-full"></div>
                    @endif
                </div>

                <input
                        type="text"
                        wire:model="newComments.{{ $feed?->id ?? 'null' }}"
                        wire:keydown.enter="addComment({{ $feed?->id ?? 'null' }})"
                        placeholder="نظر خود را بنویسید..."
                        class="flex-1 bg-transparent border-none text-xs focus:ring-0 outline-none"
                >

                <button
                        wire:click="addComment({{ $feed?->id ?? 'null' }})"
                        class="w-8 h-8 flex items-center justify-center rounded-full bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md active:scale-90 transition-transform"
                >
                    <span class="material-symbols-rounded text-lg rotate-180">send</span>
                </button>
            </div>
        @else
            <div class="mt-4 p-3 bg-[var(--md-sys-color-surface-container-low)] rounded-2xl text-center border border-dashed border-[var(--md-sys-color-outline-variant)]">
                <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">برای ثبت نظر وارد شوید</p>
            </div>
        @endauth
    @endif
</div>
