<div id="msg-viewport"
     x-ref="msgViewport"
     wire:poll.10s
     class="flex-1 overflow-y-auto px-4 md:px-8 border-none shadow-md py-6 space-y-1 msg-scrollbar relative bg-[radial-gradient(ellipse_at_70%_0%,color-mix(in_srgb,var(--md-sys-color-primary-container)_20%,transparent)_0%,transparent_50%),radial-gradient(ellipse_at_30%_100%,color-mix(in_srgb,var(--md-sys-color-tertiary-container)_12%,transparent)_0%,transparent_50%),var(--md-sys-color-background)]"
     role="log" aria-label="پیام‌ها" aria-live="polite">

    @if($this->totalMessages > count($this->messages))
        <div class="flex justify-center py-2">
            <x-dashboard.button.load-more
                action="loadMoreMessages"
                text="پیام‌های قدیمی‌تر"
                loadingText="..."
                icon="expand_less"
                class="px-4 py-2 rounded-xl bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] text-xs font-semibold shadow-sm"
            />
        </div>
    @endif

    @forelse($this->groupedMessages as $date => $rawMessages)
        @php( $group = $p->messageGroup($date, $rawMessages, auth()->id(), $this->editTimeLimit))

        <div class="flex items-center gap-3 py-4 sticky top-0 z-10" aria-hidden="true">
            <div
                class="flex-1 h-px bg-[linear-gradient(to_left,transparent,color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent))]"></div>
            <span
                class="text-[10px] font-bold uppercase tracking-widest px-3 py-1.5 rounded-lg shadow-sm backdrop-blur-md bg-[color-mix(in_srgb,var(--md-sys-color-surface-variant)_85%,transparent)] text-[var(--md-sys-color-on-surface-variant)]">
                {{ $group['label'] }}
            </span>
            <div
                class="flex-1 h-px bg-[linear-gradient(to_right,transparent,color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent))]"></div>
        </div>

        @foreach($group['messages'] as $msg)
            <div wire:key="msg-{{ $msg['id'] }}"
                 @class([
                     'flex items-end gap-2 group bubble-enter',
                     'justify-start' => $msg['is_mine'],
                     'justify-end' => !$msg['is_mine'],
                     $msg['gap_class']
                 ]) style="animation-delay: {{ $msg['animation_delay'] }}s">

                @if(!$msg['is_mine'])
                    @if($msg['is_last'])
                        <div
                            class="flex-shrink-0 w-7 h-7 rounded-lg overflow-hidden self-end mb-0.5 shadow-sm bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                            @if($activeContact->profile?->avatar)
                                <img src="{{ $activeContact->profile->avatar }}" class="w-full h-full object-cover"
                                     alt="profile image">
                            @else
                                {{ mb_substr($activeContact->name, 0, 1) }}
                            @endif
                        </div>
                    @else
                        <div class="flex-shrink-0 w-7" aria-hidden="true"></div>
                    @endif
                @endif

                <div class="max-w-[70%] md:max-w-[60%] lg:max-w-[55%] relative">

                    @if(isset($editingId) && (int) $editingId === $msg['id'])
                        <div class="rounded-xl overflow-hidden bg-[var(--md-sys-color-surface)] ring-2 ring-[var(--md-sys-color-primary)] ring-offset-0 shadow-[0_4px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_18%,transparent)]">
                                    <textarea wire:model.live="edit.editingBody"
                                              wire:keydown.enter.prevent="saveEdit"
                                              wire:keydown.escape="cancelEdit"
                                              x-data
                                              x-init="$nextTick(() => { $el.focus(); $el.selectionStart = $el.selectionEnd = $el.value.length })"
                                              x-on:input="$el.style.height='auto'; $el.style.height = Math.min($el.scrollHeight, 120) + 'px'"
                                              rows="1"
                                              class="w-full bg-transparent px-4 py-3 text-sm resize-none focus:outline-none leading-relaxed min-h-[40px] max-h-[120px] text-[var(--md-sys-color-on-surface)] placeholder:text-[var(--md-sys-color-on-surface-variant)]">{{ $this->edit->editingBody }}</textarea>
                            @error('edit.editingBody')
                            <p class="px-4 pb-1 text-[10px] text-[var(--md-sys-color-error)] flex items-center gap-1">
                                <span class="material-symbols-rounded text-[10px]">error</span>{{ $message }}
                            </p>
                            @enderror
                            <div class="flex items-center justify-between px-3 pb-2.5 pt-1.5 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_20%,transparent)]">
                                <span class="text-[9px] tracking-wide text-[var(--md-sys-color-on-surface-variant)] opacity-40">Enter ذخیره · Esc انصراف</span>
                                <div class="flex items-center gap-1.5">
                                    <button wire:click="cancelEdit"
                                            class="px-3.5 py-1.5 rounded-lg text-[11px] font-semibold transition-all duration-150 hover:brightness-90 active:scale-95 bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_25%,transparent)] text-[var(--md-sys-color-on-surface-variant)]">
                                        انصراف
                                    </button>
                                    <button wire:click="saveEdit"
                                            wire:loading.attr="disabled"
                                            class="px-3.5 py-1.5 rounded-lg text-[11px] font-semibold transition-all duration-150 hover:shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)] hover:brightness-110 active:scale-95 disabled:opacity-40 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]">
                                        <span wire:loading.remove wire:target="saveEdit">ذخیره</span>
                                        <span wire:loading wire:target="saveEdit" class="material-symbols-rounded text-[12px] animate-spin">progress_activity</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                    @elseif(isset($deletingId) && (int) $deletingId === $msg['id'])
                        <div class="flex flex-col gap-2.5 px-4 py-3.5 rounded-lg bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-error)_12%,transparent)] ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-error)_15%,transparent)]">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-rounded text-[15px] opacity-80">delete_forever</span>
                                <span class="text-xs font-semibold">حذف این پیام؟</span>
                            </div>
                            <div class="flex items-center gap-2 justify-end">
                                <button wire:click="cancelDelete"
                                        class="px-3.5 py-1.5 rounded-lg text-[11px] font-semibold transition-all duration-150 hover:brightness-90 active:scale-95 bg-[color-mix(in_srgb,var(--md-sys-color-on-error-container)_10%,transparent)]">
                                    انصراف
                                </button>
                                <button wire:click="deleteMessage" wire:loading.attr="disabled"
                                        class="px-3.5 py-1.5 rounded-lg text-[11px] font-semibold transition-all duration-150 hover:brightness-110 hover:shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-error)_35%,transparent)] active:scale-95 disabled:opacity-40 bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)]">
                                    <span wire:loading.remove wire:target="deleteMessage">حذف</span>
                                    <span wire:loading wire:target="deleteMessage" class="material-symbols-rounded text-[12px] animate-spin">progress_activity</span>
                                </button>
                            </div>
                        </div>

                    @else
                        <div @class([
                                    'px-4 py-2.5 text-sm leading-relaxed break-words select-text cursor-default ' . $msg['bubble_radius'],
                                    'bg-[linear-gradient(145deg,var(--md-sys-color-primary)_0%,color-mix(in_srgb,var(--md-sys-color-primary)_82%,var(--md-sys-color-tertiary))_100%)] text-[var(--md-sys-color-on-primary)] shadow-[0_3px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_28%,transparent),inset_0_1px_0_color-mix(in_srgb,white_12%,transparent)]' => $msg['is_mine'],
                                    'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-[0_1px_6px_color-mix(in_srgb,var(--md-sys-color-shadow)_6%,transparent),inset_0_1px_0_color-mix(in_srgb,white_6%,transparent)]' => !$msg['is_mine']
                                ])>
                            @if($msg['reply_to'])
                                <div @class([
                                            'flex items-start gap-2 mb-2.5 px-2.5 py-2 rounded-lg',
                                            'bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_12%,transparent)] border-l-[2.5px] border-[color-mix(in_srgb,var(--md-sys-color-on-primary)_45%,transparent)]' => $msg['is_mine'],
                                            'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_6%,transparent)] border-r-[2.5px] border-[var(--md-sys-color-primary)]' => !$msg['is_mine']
                                        ])>
                                        <span @class([
                                                'material-symbols-rounded text-[11px] mt-0.5 flex-shrink-0',
                                                'text-[color-mix(in_srgb,var(--md-sys-color-on-primary)_65%,transparent)]' => $msg['is_mine'],
                                                'text-[var(--md-sys-color-primary)]' => !$msg['is_mine']
                                            ])>reply</span>
                                    <div class="min-w-0">
                                        <p @class([
                                                'text-[10px] font-bold mb-0.5 truncate',
                                                'text-[color-mix(in_srgb,var(--md-sys-color-on-primary)_85%,transparent)]' => $msg['is_mine'],
                                                'text-[var(--md-sys-color-primary)]' => !$msg['is_mine']
                                            ])>{{ $msg['reply_to']['sender_name'] }}</p>
                                        <p @class([
                                                'text-[11px] truncate',
                                                'text-[color-mix(in_srgb,var(--md-sys-color-on-primary)_55%,transparent)]' => $msg['is_mine'],
                                                'text-[var(--md-sys-color-on-surface-variant)] opacity-70' => !$msg['is_mine']
                                            ])>{{ $msg['reply_to']['body'] }}</p>
                                    </div>
                                </div>
                            @endif
                            <span>{!! $msg['body_html'] !!}</span>
                        </div>

                        @if($msg['is_last'])
                            <div @class([
                                        'absolute top-1/2 -translate-y-1/2 flex items-center gap-0.5 px-1 py-1 rounded-lg opacity-0 group-hover:opacity-100 transition-all duration-200 z-20 bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-[0_4px_20px_color-mix(in_srgb,var(--md-sys-color-shadow)_12%,transparent)] backdrop-blur-sm scale-90 group-hover:scale-100',
                                        'left-0 -translate-x-[calc(100%+6px)]' => $msg['is_mine'],
                                        'right-0 translate-x-[calc(100%+6px)]' => !$msg['is_mine']
                                    ])>
                                <button x-on:click="copyMessage('{{ addslashes(strip_tags($msg['body_html'])) }}')"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-tertiary)_10%,transparent)] hover:text-[var(--md-sys-color-tertiary)] hover:scale-110 active:scale-90 text-[var(--md-sys-color-on-surface-variant)]"
                                        title="کپی" aria-label="کپی">
                                    <span class="material-symbols-rounded text-[15px]">content_copy</span>
                                </button>
                                <button wire:click="startReply({{ $msg['id'] }})"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)] hover:scale-110 active:scale-90 text-[var(--md-sys-color-on-surface-variant)]"
                                        title="پاسخ" aria-label="پاسخ">
                                    <span class="material-symbols-rounded text-[15px]">reply</span>
                                </button>
                                @if($msg['can_edit'])
                                    <button wire:click="startEdit({{ $msg['id'] }})" wire:loading.attr="disabled"
                                            class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-secondary)_10%,transparent)] hover:text-[var(--md-sys-color-secondary)] hover:scale-110 active:scale-90 disabled:opacity-40 text-[var(--md-sys-color-on-surface-variant)]"
                                            title="ویرایش" aria-label="ویرایش">
                                        <span wire:loading.remove wire:target="startEdit" class="material-symbols-rounded text-[15px]">edit</span>
                                        <span wire:loading wire:target="startEdit" class="material-symbols-rounded text-[15px] animate-spin">progress_activity</span>
                                    </button>
                                @endif
                                @if($msg['can_delete'])
                                    <button wire:click="confirmDelete({{ $msg['id'] }})" wire:loading.attr="disabled"
                                            class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-error)_10%,transparent)] hover:scale-110 active:scale-90 disabled:opacity-40 text-[color-mix(in_srgb,var(--md-sys-color-error)_80%,var(--md-sys-color-on-surface-variant))]"
                                            title="حذف" aria-label="حذف">
                                        <span wire:loading.remove wire:target="confirmDelete" class="material-symbols-rounded text-[15px]">delete</span>
                                        <span wire:loading wire:target="confirmDelete" class="material-symbols-rounded text-[15px] animate-spin">progress_activity</span>
                                    </button>
                                @endif
                            </div>

                            <div class="flex items-center gap-1.5 mt-1.5 {{ $msg['is_mine'] ? 'justify-start pl-1' : 'justify-end pr-1' }}">
                                @if($msg['is_edited'])
                                    <span class="text-[9px] tracking-wide font-medium italic text-[var(--md-sys-color-on-surface-variant)] opacity-45">ویرایش شده</span>
                                    <span class="w-[2px] h-[2px] rounded-full bg-[var(--md-sys-color-on-surface-variant)] opacity-30"></span>
                                @endif
                                <time class="text-[10px] font-medium tabular-nums text-[var(--md-sys-color-on-surface-variant)] opacity-60"
                                      datetime="{{ $msg['datetime'] }}"
                                      dir="ltr">{{ $msg['time'] }}</time>
                                @if($msg['is_mine'])
                                    <span @class([
                                                'material-symbols-rounded text-[13px] transition-colors duration-300',
                                                'text-[var(--md-sys-color-primary)]' => $msg['is_read'],
                                                'text-[var(--md-sys-color-on-surface-variant)] opacity-50' => !$msg['is_read']
                                            ])>{{ $msg['is_read'] ? 'done_all' : 'done' }}
                                        </span>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        @endforeach

    @empty
        <div class="flex flex-col items-center justify-center min-h-[60vh] gap-5 text-center">
            <div
                class="w-20 h-20 rounded-3xl flex items-center justify-center shadow-lg bg-[linear-gradient(135deg,var(--md-sys-color-primary-container),var(--md-sys-color-secondary-container))] text-[var(--md-sys-color-on-primary-container)]"
                aria-hidden="true">
                <span class="material-symbols-rounded text-4xl">chat</span>
            </div>
            <div>
                <p class="text-base font-bold text-[var(--md-sys-color-on-surface)]">شروع گفتگو با {{ $activeContact->name }}</p>
                <p class="text-[11px] mt-2 leading-relaxed text-[var(--md-sys-color-on-surface-variant)]">اولین پیام خود را بنویسید و ارتباط کاری را آغاز کنید</p>
            </div>
        </div>
    @endforelse

    <div x-show="isTyping" x-transition.opacity class="flex justify-end items-end gap-2 mt-2" aria-label="در حال نوشتن">
        <div class="px-4 py-3 rounded-lg rounded-tr-md bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_25%,transparent)]">
            <div class="flex items-center gap-1" aria-hidden="true">
                <div class="w-2 h-2 rounded-full typing-dot bg-[var(--md-sys-color-on-surface-variant)] opacity-50"></div>
                <div class="w-2 h-2 rounded-full typing-dot bg-[var(--md-sys-color-on-surface-variant)] opacity-50"></div>
                <div class="w-2 h-2 rounded-full typing-dot bg-[var(--md-sys-color-on-surface-variant)] opacity-50"></div>
            </div>
        </div>
    </div>

    <button x-show="showScrollFab" x-transition x-on:click="scrollToBottom()"
            class="fixed bottom-28 left-8 z-20 w-10 h-10 rounded-lg flex items-center justify-center shadow-xl transition-all hover:scale-110 active:scale-95 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_20px_color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)]"
            aria-label="اسکرول به پایین">
        <span class="material-symbols-rounded text-[18px]" aria-hidden="true">keyboard_arrow_down</span>
    </button>
</div>

<div x-show="showUndo"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-4"
     class="absolute bottom-20 left-1/2 -translate-x-1/2 z-50 flex items-center gap-4 px-5 py-3 rounded-lg shadow-2xl text-sm bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]">
    <button x-on:click="$wire.undoDelete()" class="font-bold transition-colors text-[#facc15]">↻ بازگشت</button>
</div>
