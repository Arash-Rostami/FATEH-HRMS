@php
    $isNestedView = isset($isNested);
@endphp

<div
    class="{{ $isNestedView ? 'mt-3 mr-5 space-y-3 border-r-2 border-[var(--md-sys-color-primary)]/20 pr-3' : 'mt-2 flex flex-col h-full' }}"
    x-data="{ replyingTo: null }"
>
    {{-- ─── Comment Input (top) ────────────────────────────────────── --}}
    @if(!$isNestedView)
        @auth
            @php
                $authUser     = auth()?->user();
                $authHasPhoto = !empty($authUser?->profile?->image);
                $authOnline   = $authUser?->isOnline() ?? false;
            @endphp

            <div
                class="shrink-0 mt-1 mx-1 mb-3 p-3 bg-[var(--md-sys-color-primary-container)]/30 border-2 border-[var(--md-sys-color-primary)]/40 rounded-2xl shadow-[0_0_0_4px_color-mix(in_srgb,var(--md-sys-color-primary)_8%,transparent)] focus-within:border-[var(--md-sys-color-primary)]/80 focus-within:bg-[var(--md-sys-color-primary-container)]/50 focus-within:shadow-[0_0_0_4px_color-mix(in_srgb,var(--md-sys-color-primary)_15%,transparent)] transition-all duration-300">

                <div class="flex items-center gap-1.5 mb-2.5 px-1">
                    <span class="material-symbols-rounded text-sm text-[var(--md-sys-color-primary)]">edit_note</span>
                    <span class="text-[10px] font-semibold text-[var(--md-sys-color-primary)] tracking-wide">نظر خود را بنویسید</span>
                </div>

                {{-- Changed to items-end so avatar aligns to bottom when textarea expands --}}
                <div class="flex gap-2.5 items-end">

                    {{-- Auth Avatar --}}
                    <div class="relative shrink-0">
                        @if($authHasPhoto)
                            <x-ui.avatar
                                title="تصویر پروفایل"
                                :existingImage="$authUser?->getProfileImageUrl() ?? $feed->user?->getInitialsAvatarUrl()"
                                :alt="$authUser?->name ?? 'کاربر'"
                                class="!w-8 !h-8 !rounded-lg shadow-md group-hover:scale-105 transition-all hover:grayscale duration-500"
                            />
                        @else
                            <div
                                class="w-8 h-8 rounded-full bg-[var(--md-sys-color-primary-container)] border-2 border-[var(--md-sys-color-primary)]/30 flex items-center justify-center shadow-sm">
                                <span
                                    class="material-symbols-rounded text-sm text-[var(--md-sys-color-on-primary-container)]">person</span>
                            </div>
                        @endif
                        @if($authOnline)
                            <div
                                class="absolute top-0 -right-0.5 w-2.5 h-2.5 bg-emerald-500 border-2 border-[var(--md-sys-color-surface)] rounded-full animate-pulse"></div>
                        @endif
                    </div>

                    {{-- Textarea + Emoji Picker --}}
                    <div
                        class="flex-1 relative"
                        x-data="{
                                showEmoji: false,
                                emojis: ['😀','😁','😂','🤣','😊','😍','😎','🤩','😘','😋','🤗','🤔','😐','😴','😢','😭','😡','😱','🥳','😏','🙄','👍','👎','👏','🙏','🔥','❤️','💔','💕','💯','✨','🎉','🌟','💪','🙌','🫶','👌','🤝','🎂','☕'],
                                panelStyle: '',
                                toggleEmoji() {
                                    if (!this.showEmoji) {
                                        const btn = this.$refs.emojiBtn;
                                        const r = btn.getBoundingClientRect();
                                        const panelW = 256;
                                        const panelH = 260;
                                        const vw = window.innerWidth;
                                        const above = r.top > panelH + 8;
                                        const topVal = above ? r.top - panelH - 8 : r.bottom + 8;

                                        let leftVal;
                                        if (vw < 640) {
                                            leftVal = Math.max(8, (vw - panelW) / 2);
                                        } else {
                                            leftVal = Math.min(r.left, vw - panelW - 8);
                                            leftVal = Math.max(8, leftVal);
                                        }

                                        this.panelStyle = `position:fixed;z-index:9999;left:${leftVal}px;top:${topVal}px;width:${panelW}px;`;
                                    }
                                    this.showEmoji = !this.showEmoji;
                                },
                                insertEmoji(emoji) {
                                    const ta = this.$refs.commentInput;
                                    const start = ta.selectionStart ?? ta.value.length;
                                    const end   = ta.selectionEnd ?? ta.value.length;
                                    ta.value = ta.value.slice(0, start) + emoji + ta.value.slice(end);
                                    const pos = start + emoji.length;
                                    ta.focus();
                                    ta.setSelectionRange(pos, pos);
                                    ta.dispatchEvent(new Event('input'));
                                    ta.style.height = 'auto';
                                    ta.style.height = ta.scrollHeight + 'px';
                                }
                            }"
                        x-init="$refs.commentInput.style.height = 'auto'"
                        @keydown.escape.window="showEmoji = false"
                    >
                            <textarea
                                x-ref="commentInput"
                                wire:model="newComments.{{ $feed?->id ?? 'null' }}"
                                @keydown.enter.prevent="if (!$event.shiftKey) { $wire.addComment({{ $feed?->id ?? 'null' }}) } else { const s = $el.selectionStart; $el.value = $el.value.slice(0, s) + '\n' + $el.value.slice($el.selectionEnd); $el.selectionStart = $el.selectionEnd = s + 1; $el.dispatchEvent(new Event('input')) }"
                                @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                                placeholder="نظرت رو بنویس..."
                                rows="1"
                                class="w-full bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/50 rounded-xl pr-11 pl-11 py-2.5 text-[13px] text-[var(--md-sys-color-on-surface)] placeholder:text-[var(--md-sys-color-on-surface-variant)]/60 focus:ring-1 focus:ring-[var(--md-sys-color-primary)] focus:border-[var(--md-sys-color-primary)] outline-none transition-all duration-200 shadow-inner resize-none overflow-hidden leading-relaxed"
                            ></textarea>

                        {{-- Emoji Toggle --}}
                        <button
                            type="button"
                            x-ref="emojiBtn"
                            @click="toggleEmoji()"
                            class="absolute bottom-3 right-2 w-7 h-7 flex items-center justify-center rounded-lg text-lg hover:bg-[var(--md-sys-color-secondary-container)] active:scale-90 transition-all duration-150"
                            :class="showEmoji ? 'bg-[var(--md-sys-color-secondary-container)]' : ''"
                        >
                            <span class="leading-none select-none">😊</span>
                        </button>

                        {{-- Send --}}
                        <button
                            type="button"
                            wire:click="addComment({{ $feed?->id ?? 'null' }})"
                            class="absolute bottom-4 left-2 w-7 h-7 flex items-center justify-center rounded-full bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm hover:brightness-110 active:scale-90 transition-all duration-200"
                        >
                            <span class="material-symbols-rounded text-xs">check</span>
                        </button>

                        {{-- Emoji Panel — teleported to body to escape overflow:hidden parents --}}
                        <template x-teleport="body">
                            <div
                                x-show="showEmoji"
                                :style="panelStyle"
                                @click.outside="showEmoji = false"
                                style="display:none"
                                class="p-2.5 bg-[var(--md-sys-color-primary-container)] border border-[var(--md-sys-color-outline-variant)]/40 rounded-2xl shadow-2xl shadow-black/20"
                            >
                                <div <div class="grid grid-cols-8 gap-1">
                                    <template x-for="emoji in emojis" :key="emoji">
                                        <button
                                            type="button"
                                            @click="insertEmoji(emoji)"
                                            class="w-7 h-7 flex items-center justify-center rounded-lg text-xl hover:bg-[var(--md-sys-color-secondary-container)] hover:scale-110 active:scale-90 transition-all duration-150"
                                        >
                                            <span class="leading-none select-none" x-text="emoji"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

        @else
            <div
                class="mt-1 mx-1 mb-3 p-3 bg-[var(--md-sys-color-surface-container-low)] rounded-2xl border-2 border-dashed border-[var(--md-sys-color-primary)]/30 text-center">
                <span
                    class="material-symbols-rounded text-lg text-[var(--md-sys-color-primary)]/50 block mb-1">login</span>
                <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">برای ثبت نظر وارد شوید</p>
            </div>
        @endauth
    @endif

    {{-- ─── Comments List ─────────────────────────────────────────── --}}
    <div
        class="{{ $isNestedView ? 'space-y-3' : 'space-y-4 flex-1 overflow-y-auto feed-scrollbar px-2 py-1 min-h-0' }}">
        @forelse($comments ?? [] as $comment)
            @php
                $commentUser = $comment?->user;
                $hasPhoto    = !empty($commentUser?->getProfileImageUrl() ?? $commentUser?->getInitialsAvatarUrl());
                $isOnline    = $commentUser?->isOnline() ?? false;
                $isOwner     = auth()->id() === $comment->user_id;
                $isEditing   = ($editingCommentId ?? null) === ($comment?->id ?? null);
            @endphp

            <div class="flex flex-col gap-1.5 group" wire:key="comment-{{ $comment?->id ?? 'unknown' }}">
                <div class="flex gap-2.5">

                    {{-- Avatar --}}
                    <div class="relative shrink-0 mt-1">
                        @if($hasPhoto)
                            <x-ui.avatar
                                title="تصویر پروفایل"
                                :existingImage="$commentUser?->getProfileImageUrl() ?? $commentUser?->getInitialsAvatarUrl()"
                                :alt="$commentUser?->name"
                                class="!w-8 !h-8 !rounded-lg shadow-md group-hover:scale-105 transition-all hover:grayscale duration-500"
                            />
                        @else
                            <div
                                class="w-8 h-8 !rounded-lg border-2 border-[var(--md-sys-color-primary)]/20 bg-[var(--md-sys-color-primary-container)] flex items-center justify-center">
                                <span
                                    class="material-symbols-rounded text-sm text-[var(--md-sys-color-on-primary-container)]">person</span>
                            </div>
                        @endif

                        @if($isOnline)
                            <div
                                class="absolute top-0 -right-0.5 w-2.5 h-2.5 bg-emerald-500 border-2 border-[var(--md-sys-color-surface)] rounded-full animate-pulse"></div>
                        @endif
                    </div>

                    {{-- Bubble + Actions --}}
                    <div class="flex-1 min-w-0">
                        <div
                            class="relative bg-[var(--md-sys-color-surface-container-high)] rounded-2xl rounded-tr-none px-4 py-3 shadow-sm border border-[var(--md-sys-color-outline-variant)]/30 transition-all duration-200 group-hover:border-[var(--md-sys-color-primary)]/20 group-hover:shadow-md">

                            {{-- Header --}}
                            <div class="flex justify-between items-baseline mb-1.5 gap-2">
                                <span class="font-bold text-[12px] text-[var(--md-sys-color-primary)] truncate">
                                    {!! superClean($commentUser?->name) ?? 'کاربر حذف شده' !!}
                                </span>
                                <sup class="!text-[10px] opacity-50 shrink-0 font-medium" dir="rtl">
                                    {{ $comment?->created_at ? convertToPersian(toJalali($comment->created_at, 'j F Y')) : 'زمان نامشخص' }}
                                </sup>
                            </div>

                            {{-- Body: Edit or Read --}}
                            @if($isEditing)
                                <div class="flex flex-col gap-2 py-1">
                                    <textarea
                                        wire:model="commentForm.content"
                                        rows="2"
                                        class="w-full bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-primary)]/30 rounded-xl p-2.5 text-[13px] focus:ring-1 focus:ring-[var(--md-sys-color-primary)] outline-none resize-none leading-relaxed"
                                    ></textarea>
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="updateComment"
                                                class="px-3.5 py-1.5 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] rounded-lg text-[11px] font-bold shadow-sm">
                                            ذخیره
                                        </button>
                                        <button wire:click="$set('editingCommentId', null)"
                                                class="px-3.5 py-1.5 text-[var(--md-sys-color-primary)] text-[11px] font-medium hover:bg-[var(--md-sys-color-primary-container)] rounded-lg transition-colors">
                                            لغو
                                        </button>
                                    </div>
                                </div>
                            @else
                                <p class="leading-relaxed text-[13.5px] tracking-wide text-[var(--md-sys-color-on-surface)]">
                                    {!! superClean($comment?->content, 100, true) ?? '' !!}
                                </p>
                            @endif

                            {{-- Hover Actions --}}
                            <div
                                class="mt-2.5 flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-all duration-200 translate-y-0.5 group-hover:translate-y-0">
                                <button
                                    @click="replyingTo = replyingTo === {{ $comment->id }} ? null : {{ $comment->id }}"
                                    class="flex items-center gap-1 px-2.5 py-1 text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] rounded-full transition-colors text-[11px] font-semibold"
                                >
                                    <span class="material-symbols-rounded !text-[13px]">reply</span>
                                    <span>پاسخ</span>
                                </button>

                                @if($isOwner)
                                    <button wire:click="startEditing({{ $comment->id }})"
                                            class="p-1.5 text-[var(--md-sys-color-secondary)] hover:bg-[var(--md-sys-color-secondary-container)] rounded-full transition-colors">
                                        <span class="material-symbols-rounded !text-[13px]">edit</span>
                                    </button>
                                    <button @click="$dispatch('open-confirmation', {
                                            title: 'حذف نظر',
                                            message: 'آیا از حذف این نظر مطمئن هستید؟ این عملیات غیرقابل بازگشت است.',
                                            method: 'delete-comment-confirmed',
                                            params: {{ $comment->id }},
                                            type: 'js'
                                        })"
                                            class="p-1.5 text-[var(--md-sys-color-error)] hover:bg-[var(--md-sys-color-error-container)] rounded-full transition-colors">
                                        <span class="material-symbols-rounded !text-[13px]">delete</span>
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- Reply Input --}}
                        <div x-show="replyingTo === {{ $comment?->id ?? 'null' }}" x-collapse class="mt-2"
                             style="display:none">
                            <div
                                class="flex items-center gap-2 bg-[var(--md-sys-color-primary-container)]/40 border border-[var(--md-sys-color-primary)]/30 rounded-xl px-3.5 py-2.5">
                                <span
                                    class="material-symbols-rounded text-sm text-[var(--md-sys-color-primary)] shrink-0">subdirectory_arrow_left</span>
                                <input
                                    type="text"
                                    wire:model="replyComments.{{ $comment?->id ?? 'null' }}"
                                    @keydown.enter="$wire.addComment({{ $feed?->id ?? 'null' }}, {{ $comment?->id ?? 'null' }}); replyingTo = null"
                                    placeholder="پاسخ به {{ superClean($commentUser?->name ?? 'کاربر') }}..."
                                    class="flex-1 bg-transparent border-none text-[13px] focus:ring-0 outline-none placeholder:text-[var(--md-sys-color-primary)]/50 text-[var(--md-sys-color-on-surface)]"
                                >
                                <button
                                    @click="$wire.addComment({{ $feed?->id ?? 'null' }}, {{ $comment?->id ?? 'null' }}); replyingTo = null"
                                    class="w-7 h-7 rounded-full bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] flex items-center justify-center shrink-0 hover:brightness-110 active:scale-90 transition-all shadow-sm"
                                >
                                    <span class="material-symbols-rounded text-sm rotate-180">send</span>
                                </button>
                            </div>
                        </div>

                        {{-- Nested Replies --}}
                        @if($comment?->children?->isNotEmpty())
                            <div x-data="{ showReplies: false }">
                                <button
                                    @click="showReplies = !showReplies"
                                    class="mt-2 flex items-center gap-1.5 text-[var(--md-sys-color-primary)] text-[11px] font-semibold hover:opacity-80 transition-opacity px-1"
                                >
                                    <span
                                        x-text="showReplies ? 'بستن پاسخ‌ها' : '{{ $comment->children->count() }} پاسخ'"></span>
                                    <span
                                        class="material-symbols-rounded !text-[14px] transition-transform duration-300"
                                        :class="showReplies ? 'rotate-90' : ''">chevron_left</span>
                                </button>

                                <div x-show="showReplies" x-collapse style="display:none">
                                    @include('livewire.dashboard.tab.feeds.comments', [
                                        'comments'          => $comment->children,
                                        'feed'              => $feed,
                                        'isNested'          => true,
                                        'editingCommentId'  => $editingCommentId ?? null,
                                    ])
                                </div>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        @empty
            @if(!$isNestedView)
                <div class="py-10 text-center opacity-40 flex flex-col items-center gap-2">
                    <div
                        class="w-14 h-14 rounded-full bg-[var(--md-sys-color-surface-container-high)] flex items-center justify-center shadow-inner">
                        <span class="material-symbols-rounded text-3xl">chat_bubble</span>
                    </div>
                    <p class="text-[13px] font-medium">اولین نظر را شما بنویسید</p>
                </div>
            @endif
        @endforelse
    </div>

</div>
