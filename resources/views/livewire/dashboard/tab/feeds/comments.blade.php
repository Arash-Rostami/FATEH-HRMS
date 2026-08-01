@php
    $isNestedView = isset($isNested);
@endphp

<div
    class="{{ $isNestedView ? 'mt-3 mr-5 space-y-3 border-r-2 border-[var(--md-sys-color-primary)]/20 pr-3' : 'mt-2 flex flex-col h-full' }}"
    x-data="{ replyingTo: null }"
>
    {{--  Comment Input (top) --}}
    @if(!$isNestedView)
        @auth
            @php
                $authUser     = auth()?->user();
                $authHasPhoto = !empty($authUser?->profile?->image);
                $authOnline   = $authUser?->isOnline() ?? false;
            @endphp

            <div class="shrink-0 mx-1 mt-1 mb-3 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/50 rounded-xl overflow-hidden transition-all duration-200 focus-within:border-[var(--md-sys-color-primary)]/60 focus-within:shadow-[0_0_0_3px_color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)]">
                {{-- Row 1: Avatar + label --}}
                <div class="flex items-center gap-2 px-3 py-2 bg-[var(--md-sys-color-surface-variant)]/40 border-b border-[var(--md-sys-color-outline-variant)]/30">
                    <div class="relative shrink-0">
                        @if($authHasPhoto)
                            <x-ui.avatar
                                title="تصویر پروفایل"
                                :existingImage="$authUser?->getProfileImageUrl() ?? $feed->user?->getInitialsAvatarUrl()"
                                :alt="$authUser?->name ?? 'کاربر'"
                                class="!w-6 !h-6 !rounded-md"
                            />
                        @else
                            <div class="w-6 h-6 rounded-md bg-[var(--md-sys-color-primary-container)] flex items-center justify-center">
                                <span class="material-symbols-rounded text-[13px] text-[var(--md-sys-color-on-primary-container)]">person</span>
                            </div>
                        @endif

                        @if($authOnline)
                            <div class="absolute -top-0.5 -right-0.5 w-1.5 h-1.5 bg-emerald-500 border border-[var(--md-sys-color-surface)] rounded-full"></div>
                        @endif
                    </div>

                    <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-on-surface-variant)]">edit_note</span>
                    <span class="text-[11.5px] font-medium text-[var(--md-sys-color-on-surface-variant)] tracking-wide">نظر خود را بنویسید</span>
                </div>

                {{-- Row 2: Textarea + Send --}}
                <div class="flex items-end">
                    <div
                        class="flex-1 relative"
                        x-data="{ value: @entangle('newComments.' . ($feed?->id ?? 'null')).live, ...feedComposer({{ $feed?->id ?? 'null' }}) }"
                        @keydown.escape.window="showEmoji = false"
                    >
                        <textarea
                            x-ref="commentInput"
                            wire:model="newComments.{{ $feed?->id ?? 'null' }}"
                            @keydown.enter.prevent="onEnter($event)"
                            @input="autoGrow($el)"
                            placeholder="نظرت رو بنویس..."
                            rows="1"
                            class="w-full bg-transparent border-none outline-none resize-none pl-8 pr-16 py-2.5 text-[13px] leading-relaxed text-[var(--md-sys-color-on-surface)] placeholder:text-[var(--md-sys-color-on-surface-variant)]/50 overflow-hidden"
                        ></textarea>

                        <button
                            type="button"
                            @click="toggleBold()"
                            title="پررنگ (B)"
                            class="absolute bottom-0 right-8 w-6 h-6 flex items-center justify-center rounded-md text-[13px] font-bold text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] active:scale-90 transition-all duration-100"
                        >
                            <span class="leading-none select-none">B</span>
                        </button>

                        <button
                            type="button"
                            x-ref="emojiBtn"
                            @click="toggleEmoji()"
                            :class="showEmoji ? 'bg-[var(--md-sys-color-surface-variant)]' : ''"
                            class="absolute bottom-0 right-1.5 w-6 h-6 flex items-center justify-center rounded-md text-[14px] hover:bg-[var(--md-sys-color-surface-variant)] active:scale-90 transition-all duration-100"
                        >
                            <span class="leading-none select-none">😊</span>
                        </button>

                        <template x-teleport="body">
                            <div
                                x-show="showEmoji"
                                :style="panelStyle"
                                @click.outside="showEmoji = false"
                                style="display:none"
                                class="p-2 bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/40 rounded-xl shadow-xl shadow-black/15"
                            >
                                <div class="grid grid-cols-8 gap-0.5">
                                    <template x-for="emoji in emojis" :key="emoji">
                                        <button
                                            type="button"
                                            @click="insertEmoji(emoji)"
                                            class="w-7 h-7 flex items-center justify-center rounded-md text-lg hover:bg-[var(--md-sys-color-surface-variant)] hover:scale-110 active:scale-90 transition-all duration-100"
                                        >
                                            <span class="leading-none select-none" x-text="emoji"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <x-ui.forms.maximize-trigger class="top-2.5 left-0"/>
                        <x-ui.forms.maximize-overlay title="نظر شما"/>
                    </div>

                    <div class="self-stretch w-px bg-[var(--md-sys-color-outline-variant)]/30 my-1.5"></div>

                    <button
                        type="button"
                        wire:click="addComment({{ $feed?->id ?? 'null' }})"
                        title="ارسال نظر"
                        class="shrink-0 mx-2 mb-2 flex h-8 w-8 items-center justify-center rounded-lg bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:brightness-110 active:scale-90 transition-all duration-150 rotate-180"
                    >
                        <span class="material-symbols-rounded text-[18px]">send</span>
                    </button>
                </div>

                <div class="flex items-center gap-2 px-3 py-1.5 bg-[var(--md-sys-color-surface-variant)]/30 border-t border-[var(--md-sys-color-outline-variant)]/20 text-[10px] text-[var(--md-sys-color-on-surface-variant)]/70 select-none">
                    <span class="inline-flex items-center gap-1">
                        <kbd class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container-high)] border border-[var(--md-sys-color-outline-variant)]/40 font-sans font-semibold">Enter</kbd>
                        <span>ارسال</span>
                    </span>
                    <span class="opacity-40">·</span>
                    <span class="inline-flex items-center gap-1">
                        <kbd class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container-high)] border border-[var(--md-sys-color-outline-variant)]/40 font-sans font-semibold">Shift</kbd>
                        <span>+</span>
                        <kbd class="px-1 py-0.5 rounded bg-[var(--md-sys-color-surface-container-high)] border border-[var(--md-sys-color-outline-variant)]/40 font-sans font-semibold">Enter</kbd>
                        <span>خط جدید</span>
                    </span>
                    <span class="opacity-40">·</span>
                    <span class="inline-flex items-center gap-1">
                        <span class="font-bold">**</span>
                        <span>متن</span>
                        <span class="font-bold">**</span>
                        <span>پررنگ</span>
                    </span>
                </div>
            </div>
        @else
            <div class="mt-1 mx-1 mb-3 p-3 bg-[var(--md-sys-color-surface-container-low)] rounded-2xl border-2 border-dashed border-[var(--md-sys-color-primary)]/30 text-center">
                <span class="material-symbols-rounded text-lg text-[var(--md-sys-color-primary)]/50 block mb-1">login</span>
                <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">برای ثبت نظر وارد شوید</p>
            </div>
        @endauth
    @endif

    {{-- ─── Comments List ─────────────────────────────────────────── --}}
    <div class="{{ $isNestedView ? 'space-y-3' : 'space-y-4 flex-1 overflow-y-auto feed-scrollbar px-2 py-1 min-h-0' }}">
        @forelse($comments ?? [] as $comment)
            @php
                $meta = $presenter->commentMeta($comment, $editingCommentId ?? null);
            @endphp

            <div class="flex flex-col gap-1.5 group" wire:key="comment-{{ $comment?->id ?? 'unknown' }}">
                <div class="flex gap-2.5">
                    {{-- Avatar --}}
                    <div class="relative shrink-0 mt-1">
                        @if($meta['hasPhoto'])
                            <x-ui.avatar
                                title="تصویر پروفایل"
                                :existingImage="$meta['avatarUrl']"
                                :alt="$meta['user']?->name"
                                class="!w-8 !h-8 !rounded-lg shadow-md group-hover:scale-105 transition-all hover:grayscale duration-500"
                            />
                        @else
                            <div class="w-8 h-8 !rounded-lg border-2 border-[var(--md-sys-color-primary)]/20 bg-[var(--md-sys-color-primary-container)] flex items-center justify-center">
                                <span class="material-symbols-rounded text-sm text-[var(--md-sys-color-on-primary-container)]">person</span>
                            </div>
                        @endif

                        @if($meta['isOnline'])
                            <div class="absolute top-0 -right-0.5 w-2.5 h-2.5 bg-emerald-500 border-2 border-[var(--md-sys-color-surface)] rounded-full animate-pulse"></div>
                        @endif
                    </div>

                    {{-- Bubble + Actions --}}
                    <div class="flex-1 min-w-0">
                        <div class="relative bg-[var(--md-sys-color-surface-container-high)] rounded-2xl rounded-tr-none px-4 py-3 shadow-sm border border-[var(--md-sys-color-outline-variant)]/30 transition-all duration-200 group-hover:border-[var(--md-sys-color-primary)]/20 group-hover:shadow-md">
                            {{-- Header --}}
                            <div class="flex justify-between items-baseline mb-1.5 gap-2">
                                <span class="font-bold text-[12px] text-[var(--md-sys-color-primary)] truncate">
                                    {!! superClean($meta['user']?->name) ?? 'کاربر حذف شده' !!}
                                </span>
                                <sup class="!text-[10px] opacity-50 shrink-0 font-medium" dir="rtl">
                                    {{ $comment?->created_at ? convertToPersian(toJalali($comment->created_at, 'j F Y')) : 'زمان نامشخص' }}
                                </sup>
                            </div>

                            {{-- Body: Edit or Read --}}
                            @if($meta['isEditing'])
                                <div class="flex flex-col gap-2 py-1">
                                    <x-ui.forms.textarea
                                        wire:model="commentForm.content"
                                        label="ویرایش نظر"
                                        name="edit-comment-{{ $comment?->id ?? 'null' }}"
                                        rows="2"
                                        :maximizable="true"
                                    />

                                    <div class="flex justify-end gap-2">
                                        <button
                                            wire:click="updateComment"
                                            class="px-3.5 py-1.5 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] rounded-lg text-[11px] font-bold shadow-sm"
                                        >
                                            ذخیره
                                        </button>
                                        <button
                                            wire:click="$set('editingCommentId', null)"
                                            class="px-3.5 py-1.5 text-[var(--md-sys-color-primary)] text-[11px] font-medium hover:bg-[var(--md-sys-color-primary-container)] rounded-lg transition-colors"
                                        >
                                            لغو
                                        </button>
                                    </div>
                                </div>
                            @else
                                <p class="leading-relaxed text-[13.5px] tracking-wide text-[var(--md-sys-color-on-surface)] [&_strong]:font-bold [&_strong]:text-[var(--md-sys-color-on-surface)]">
                                    {!! renderComment($comment?->content) !!}
                                </p>
                            @endif

                            {{-- Hover Actions --}}
                            <div class="mt-2.5 flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-all duration-200 translate-y-0.5 group-hover:translate-y-0">
                                <button
                                    @click="replyingTo = replyingTo === {{ $comment->id }} ? null : {{ $comment->id }}"
                                    class="flex items-center gap-1 px-2.5 py-1 text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] rounded-full transition-colors text-[11px] font-semibold"
                                >
                                    <span class="material-symbols-rounded !text-[13px]">reply</span>
                                    <span>پاسخ</span>
                                </button>

                                @if($meta['isOwner'])
                                    <button
                                        wire:click="startEditing({{ $comment->id }})"
                                        class="p-1.5 text-[var(--md-sys-color-secondary)] hover:bg-[var(--md-sys-color-secondary-container)] rounded-full transition-colors"
                                    >
                                        <span class="material-symbols-rounded !text-[13px]">edit</span>
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
                                    >
                                        <span class="material-symbols-rounded !text-[13px]">delete</span>
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- Reply Input --}}
                        <div x-show="replyingTo === {{ $comment?->id ?? 'null' }}" x-collapse class="mt-2" style="display:none">
                            <div class="flex items-center gap-2 bg-[var(--md-sys-color-primary-container)]/40 border border-[var(--md-sys-color-primary)]/30 rounded-xl px-3.5 py-2.5">
                                <span class="material-symbols-rounded text-sm text-[var(--md-sys-color-primary)] shrink-0">subdirectory_arrow_left</span>

                                <input
                                    type="text"
                                    wire:model="replyComments.{{ $comment?->id ?? 'null' }}"
                                    @keydown.enter="$wire.addComment({{ $feed?->id ?? 'null' }}, {{ $comment?->id ?? 'null' }}); replyingTo = null"
                                    placeholder="پاسخ به {{ superClean($meta['user']?->name ?? 'کاربر') }}..."
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
                                    <span x-text="showReplies ? 'بستن پاسخ‌ها' : '{{ $comment->children->count() }} پاسخ'"></span>
                                    <span
                                        class="material-symbols-rounded !text-[14px] transition-transform duration-300"
                                        :class="showReplies ? 'rotate-90' : ''"
                                    >chevron_left</span>
                                </button>

                                <div x-show="showReplies" x-collapse style="display:none">
                                    @include('livewire.dashboard.tab.feeds.comments', [
                                        'comments'         => $comment->children,
                                        'feed'             => $feed,
                                        'isNested'         => true,
                                        'editingCommentId' => $editingCommentId ?? null,
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
                    <div class="w-14 h-14 rounded-full bg-[var(--md-sys-color-surface-container-high)] flex items-center justify-center shadow-inner">
                        <span class="material-symbols-rounded text-3xl">chat_bubble</span>
                    </div>
                    <p class="text-[13px] font-medium">اولین نظر را شما بنویسید</p>
                </div>
            @endif
        @endforelse
    </div>
</div>
