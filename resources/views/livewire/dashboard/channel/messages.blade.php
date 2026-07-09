@php
    $hasOlder = $this->hasOlder;
@endphp

<div x-show="senderChips.length > 1" x-cloak
     class="flex-shrink-0 flex items-center gap-1.5 px-4 md:px-8 pt-3 pb-1 overflow-x-auto msg-scrollbar bg-[var(--md-sys-color-surface)]">
    <button type="button" x-on:click="activeSender = null"
            :class="activeSender === null ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]'"
            class="flex-shrink-0 px-2.5 py-1 rounded-full text-[10px] font-semibold whitespace-nowrap transition-all hover:brightness-95 active:scale-95">
        همه
    </button>
    <template x-for="name in senderChips" :key="name">
        <button type="button" x-on:click="activeSender = name"
                :class="activeSender === name ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]'"
                class="flex-shrink-0 px-2.5 py-1 rounded-full text-[10px] font-semibold whitespace-nowrap transition-all hover:brightness-95 active:scale-95"
                x-text="name"></button>
    </template>
</div>

<div id="msg-viewport"
     class="flex flex-col flex-1 overflow-y-auto px-4 md:px-8 border-none shadow-[inset_0_4px_20px_color-mix(in_srgb,var(--md-sys-color-shadow)_3%,transparent)] py-6 space-y-1 msg-scrollbar relative"
     x-bind:class="{
         'bg-[var(--md-sys-color-primary-container)]/20': isHighlighted,
         'bg-[var(--md-sys-color-surface)]': !isHighlighted
     }"
     role="log"
     aria-label="پیام‌های کانال"
     aria-live="polite"
     aria-relevant="additions">

    <div wire:key="load-older" class="flex justify-center py-2" x-show="$wire.hasOlder" x-transition style="{{ $hasOlder ? '' : 'display:none;' }}">
        <x-ui.buttons.load-more
            action="loadOlder"
            text="پیام‌های قدیمی‌تر"
            loadingText="..."
            icon="expand_less"
            wire:loading.attr="disabled"
            wire:target="loadOlder"
            class="px-4 py-2 rounded-xl bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] text-xs font-semibold shadow-sm transition-all disabled:opacity-50" />
    </div>

    @php
        $readersMap = $this->readers;
    @endphp

    @forelse($this->groupedMessages as $date => $rawMessages)
        @php
            $p = $this->presenter;
            $group = $p->messageGroup($date, $rawMessages, auth()->id(), $this->editTimeLimit, $readersMap);
        @endphp

        <div wire:key="date-{{ $date }}" class="flex items-center gap-3 py-4" role="heading" aria-level="3" aria-label="{{ $group['label'] }}">
            <div class="flex-1 h-px bg-[linear-gradient(to_left,transparent,color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent))]" aria-hidden="true"></div>
            <span class="text-[10px] font-bold uppercase tracking-widest px-3 py-1.5 rounded-lg shadow-sm bg-[color-mix(in_srgb,var(--md-sys-color-surface-variant)_85%,transparent)] text-[var(--md-sys-color-on-surface-variant)]" aria-hidden="true">
                {{ $group['label'] }}
            </span>
            <div class="flex-1 h-px bg-[linear-gradient(to_right,transparent,color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent))]" aria-hidden="true"></div>
        </div>

        @foreach($group['messages'] as $msg)
            <div wire:key="cmsg-{{ $msg['id'] }}"
                 data-rf="channel-message-{{ $msg['id'] }}"
                 data-sender-name="{{ $msg['sender_name'] }}"
                 x-on:click="if(window.getSelection().toString().trim()==='' && !$event.target.closest('a,button,[role=button],[contenteditable],input,textarea')) toggleActions({{ $msg['id'] }})"
                 :class="activeSender && $el.dataset.senderName !== activeSender ? 'opacity-40 grayscale transition-opacity' : ''"
                 @class([
                     'flex items-end gap-2 group bubble-enter',
                     'justify-start' => $msg['is_mine'],
                     'justify-end' => !$msg['is_mine'],
                     $msg['gap_class']
                 ]) style="animation-delay: {{ $msg['animation_delay'] }}s">

                @if($msg['is_last'])
                    <div class="flex-shrink-0 w-7 h-7 rounded-lg overflow-hidden self-end mb-0.5 shadow-sm">
                        <x-ui.avatar :existingImage="$msg['sender_avatar']" :alt="$msg['sender_name']" icon="person" icon-size="text-base" />
                    </div>
                @else
                    <div class="flex-shrink-0 w-7" aria-hidden="true"></div>
                @endif

                <div class="max-w-[70%] md:max-w-[60%] lg:max-w-[55%] relative">

                    <div x-show="isEditing && editingMsgId === {{ $msg['id'] }}" style="display: none;"
                         class="rounded-xl overflow-hidden !bg-[var(--md-sys-color-surface)] ring-2 ring-[var(--md-sys-color-primary)] ring-offset-0 shadow-[0_4px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_18%,transparent)]">
                        <textarea x-model="editingBody"
                                  x-on:keydown.enter="if(!$event.shiftKey){$event.preventDefault();saveEdit({{ $msg['id'] }})}"
                                  x-on:keydown.escape="cancelEdit"
                                  x-effect="if (isEditing && editingMsgId === {{ $msg['id'] }}) $nextTick(() => { $el.focus(); $el.selectionStart = $el.selectionEnd = $el.value.length })"
                                  x-on:input="$el.style.height='auto'; $el.style.height = Math.min($el.scrollHeight, 120) + 'px'"
                                  rows="1"
                                  class="w-full bg-transparent px-4 py-3 text-sm resize-none focus:outline-none leading-relaxed min-h-[40px] max-h-[120px] text-[var(--md-sys-color-on-surface)] placeholder:text-[var(--md-sys-color-on-surface-variant)]"></textarea>
                        @error('edit.editingBody')
                        <p class="px-4 pb-1 text-[10px] text-[var(--md-sys-color-error)] flex items-center gap-1">
                            <span class="material-symbols-rounded text-[10px]">error</span>{{ $message }}
                        </p>
                        @enderror
                        <div class="flex items-center justify-between px-3 pb-2.5 pt-1.5 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_20%,transparent)]">
                            <span class="text-[9px] tracking-wide text-[var(--md-sys-color-on-surface-variant)] opacity-40">Enter ذخیره · Esc انصراف</span>
                            <div class="flex items-center gap-1.5">
                                <button x-on:click.prevent="cancelEdit"
                                        class="px-3.5 py-1.5 rounded-lg text-[11px] font-semibold transition-all duration-150 hover:brightness-90 active:scale-95 bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_25%,transparent)] text-[var(--md-sys-color-on-surface-variant)]">
                                    انصراف
                                </button>
                                <button x-on:click.prevent="saveEdit({{ $msg['id'] }})"
                                        :disabled="(editingBody || '') === editingOriginal"
                                        data-loading:class="opacity-50"
                                        class="px-3.5 py-1.5 rounded-lg text-[11px] font-semibold transition-all duration-150 hover:shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)] hover:brightness-110 active:scale-95 disabled:opacity-40 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]">
                                    ذخیره
                                </button>
                            </div>
                        </div>
                    </div>

                    <div x-show="deletingId === {{ $msg['id'] }}" style="display: none;"
                         class="flex flex-col gap-2.5 px-4 py-3.5 rounded-lg bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-error)_12%,transparent)] ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-error)_15%,transparent)]">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-rounded text-[15px] opacity-80">delete_forever</span>
                            <span class="text-xs font-semibold">حذف این پیام؟</span>
                        </div>
                        <div class="flex items-center gap-2 justify-end">
                            <button x-on:click.prevent="cancelDelete"
                                    class="px-3.5 py-1.5 rounded-lg text-[11px] font-semibold transition-all duration-150 hover:brightness-90 active:scale-95 bg-[color-mix(in_srgb,var(--md-sys-color-on-error-container)_10%,transparent)]">
                                انصراف
                            </button>
                            <button x-on:click.prevent="deleteMessage" wire:loading.attr="disabled" wire:target="deleteMessage"
                                    class="px-3.5 py-1.5 rounded-lg text-[11px] font-semibold transition-all duration-150 hover:brightness-110 hover:shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-error)_35%,transparent)] active:scale-95 disabled:opacity-40 bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)]">
                                <span wire:loading.remove wire:target="deleteMessage">حذف</span>
                                <span wire:loading wire:target="deleteMessage" class="material-symbols-rounded text-[12px] animate-spin">progress_activity</span>
                            </button>
                        </div>
                    </div>

                    <div x-show="!(isEditing && editingMsgId === {{ $msg['id'] }}) && deletingId !== {{ $msg['id'] }}" class="relative">
                        <div @class([
                                  'px-4 py-2.5 text-sm leading-relaxed break-words select-text cursor-default ' . $msg['bubble_radius'],
                                  'bg-[linear-gradient(145deg,var(--md-sys-color-primary)_0%,color-mix(in_srgb,var(--md-sys-color-primary)_82%,var(--md-sys-color-tertiary))_100%)] text-[var(--md-sys-color-on-primary)] shadow-[0_3px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_28%,transparent),inset_0_1px_0_color-mix(in_srgb,white_12%,transparent)]' => $msg['is_mine'],
                                  'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-[0_1px_6px_color-mix(in_srgb,var(--md-sys-color-shadow)_6%,transparent),inset_0_1px_0_color-mix(in_srgb,white_6%,transparent)]' => !$msg['is_mine']
                              ])
                             :style="isEmojiOnly(@js($msg['body_html'])) && 'font-size:2rem;background:none;border:none;box-shadow:none;padding:0'">

                            @if(!$msg['is_mine'] && $msg['is_first'])
                                <p class="text-[10px] font-bold mb-1 text-[var(--md-sys-color-primary)]">{{ $msg['sender_name'] }}</p>
                            @endif

                            @if($msg['reply_to'])
                                <div @class([
                                            'flex items-start gap-2 mb-2.5 px-2.5 py-2 rounded-lg cursor-pointer transition-all duration-150 hover:brightness-110',
                                            'bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_12%,transparent)] border-l-[2.5px] border-[color-mix(in_srgb,var(--md-sys-color-on-primary)_45%,transparent)]' => $msg['is_mine'],
                                            'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_6%,transparent)] border-r-[2.5px] border-[var(--md-sys-color-primary)]' => !$msg['is_mine']
                                        ])
                                     data-id="{{ $msg['reply_to']['id'] ?? 0 }}"
                                     x-on:click="scrollToMessage(Number($el.dataset.id))"
                                     x-on:keydown.enter.prevent="scrollToMessage(Number($el.dataset.id))"
                                     x-on:keydown.space.prevent="scrollToMessage(Number($el.dataset.id))"
                                     role="button"
                                     tabindex="0"
                                     title="رفتن به پیام اصلی"
                                     aria-label="رفتن به پیام اصلی">
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
                            @if($msg['body'] !== '')
                                <span>{!! $msg['body_html'] !!}</span>
                            @endif
                            @if(count($msg['attachments']))
                                <div @class(['flex flex-wrap gap-1.5', 'mt-2' => $msg['body'] !== ''])>
                                    @foreach($msg['attachments'] as $i => $att)
                                        @if($att['is_image'])
                                            <a href="{{ $att['url'] }}" target="_blank" rel="noopener noreferrer"
                                               class="block w-32 h-32 rounded-lg overflow-hidden border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
                                                <img src="{{ $att['url'] }}" alt="{{ $att['name'] }}" loading="lazy" class="w-full h-full object-cover">
                                            </a>
                                        @else
                                            <button type="button" wire:click="downloadAttachment({{ $msg['id'] }}, {{ $i }})"
                                                @class([
                                                    'flex items-center gap-2 px-2.5 py-2 rounded-lg max-w-[220px] transition-colors text-right',
                                                    'bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_12%,transparent)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_18%,transparent)]' => $msg['is_mine'],
                                                    'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_6%,transparent)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)]' => !$msg['is_mine'],
                                                ])>
                                                <span class="material-symbols-rounded text-[18px] flex-shrink-0">description</span>
                                                <span class="min-w-0">
                                                    <span class="block text-[11px] font-medium truncate">{{ $att['name'] }}</span>
                                                    <span class="block text-[9px] opacity-70">{{ $att['size_label'] }}</span>
                                                </span>
                                                <span class="material-symbols-rounded text-[15px] flex-shrink-0 opacity-70">download</span>
                                            </button>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div @class([
                                    'absolute top-1/2 -translate-y-1/2 flex items-center gap-0.5 px-1 py-1 rounded-lg opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-all duration-200 z-20 bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-[0_4px_20px_color-mix(in_srgb,var(--md-sys-color-shadow)_12%,transparent)] scale-90 group-hover:scale-100 group-focus-within:scale-100',
                                    'left-0 -translate-x-[calc(100%+6px)]' => $msg['is_mine'],
                                    'right-0 translate-x-[calc(100%+6px)]' => !$msg['is_mine']
                                ])
                             :class="openActionsId === {{ $msg['id'] }} ? 'opacity-100 scale-100' : ''">
                            <button x-on:click="copyMessage($el.dataset.text)"
                                    data-text="{{ strip_tags($msg['body_html']) }}"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-tertiary)_10%,transparent)] hover:text-[var(--md-sys-color-tertiary)] hover:scale-110 active:scale-90 text-[var(--md-sys-color-on-surface-variant)]"
                                    title="کپی" aria-label="کپی">
                                <span class="material-symbols-rounded text-[15px]">content_copy</span>
                            </button>
                            <button x-on:click.prevent="startReply({{ $msg['id'] }}, $el.dataset.sender, $el.dataset.body)"
                                    data-sender="{{ $msg['sender_name'] }}"
                                    data-body="{{ strip_tags($msg['body']) }}"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)] hover:scale-110 active:scale-90 text-[var(--md-sys-color-on-surface-variant)]"
                                    title="پاسخ" aria-label="پاسخ">
                                <span class="material-symbols-rounded text-[15px]">reply</span>
                            </button>
                            @if($msg['can_edit'] && $msg['is_last'])
                                <button x-on:click.prevent="startEdit({{ $msg['id'] }}, $el.dataset.body)"
                                        data-body="{{ $msg['body'] }}"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-secondary)_10%,transparent)] hover:text-[var(--md-sys-color-secondary)] hover:scale-110 active:scale-90 text-[var(--md-sys-color-on-surface-variant)]"
                                        title="ویرایش" aria-label="ویرایش">
                                    <span class="material-symbols-rounded text-[15px]">edit</span>
                                </button>
                            @endif
                            @if($msg['can_delete'] && $msg['id'] === $this->lastMessageId)
                                <button x-on:click.prevent="confirmDelete({{ $msg['id'] }})"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center transition-all duration-150 hover:bg-[color-mix(in_srgb,var(--md-sys-color-error)_10%,transparent)] hover:scale-110 active:scale-90 text-[color-mix(in_srgb,var(--md-sys-color-error)_80%,var(--md-sys-color-on-surface-variant))]"
                                        title="حذف" aria-label="حذف">
                                    <span class="material-symbols-rounded text-[15px]">delete</span>
                                </button>
                            @endif
                        </div>

                        @if($msg['is_last'])
                            <div class="flex items-center gap-1.5 mt-1.5 {{ $msg['is_mine'] ? 'justify-start pl-1' : 'justify-end pr-1' }}">
                                @if($msg['is_edited'])
                                    <span class="text-[9px] tracking-wide font-medium italic text-[var(--md-sys-color-on-surface-variant)] opacity-45">ویرایش شده</span>
                                    <span class="w-[2px] h-[2px] rounded-full bg-[var(--md-sys-color-on-surface-variant)] opacity-30"></span>
                                @endif
                                <time class="text-[10px] font-medium tabular-nums text-[var(--md-sys-color-on-surface-variant)] opacity-60" datetime="{{ $msg['datetime'] }}" dir="ltr">{{ $msg['time'] }}</time>
                                @if($msg['is_mine'])
                                    @php
                                        $readerNames = array_map(fn($r) => $r['name'] ?? '—', $msg['readers']);
                                        $shownReaders = array_slice($msg['readers'], 0, 3);
                                        $extraReaders = max(0, $msg['read_count'] - count($shownReaders));
                                    @endphp
                                    <span class="flex items-center gap-1" title="{{ $msg['read_count'] > 0 ? 'خوانده توسط: ' . implode('، ', $readerNames) : 'ارسال شد' }}">
                                        @if($msg['is_read_by_all'])
                                            <span class="material-symbols-rounded text-[13px] text-[var(--md-sys-color-primary)]">done_all</span>
                                        @elseif($msg['is_read'])
                                            <span class="material-symbols-rounded text-[13px] text-[var(--md-sys-color-on-surface-variant)] opacity-50">done_all</span>
                                        @else
                                            <span class="material-symbols-rounded text-[13px] text-[var(--md-sys-color-on-surface-variant)] opacity-50">done</span>
                                        @endif
                                        @if(count($shownReaders) > 0)
                                            <span class="flex -space-x-1">
                                                @foreach($shownReaders as $r)
                                                    <span class="w-4 h-4 rounded-md overflow-hidden ring-2 ring-[var(--md-sys-color-surface)]">
                                                        <x-ui.avatar :existingImage="$r['avatar']" :alt="$r['name']" icon="person" icon-size="text-[8px]" />
                                                    </span>
                                                @endforeach
                                            </span>
                                            @if($extraReaders > 0)
                                                <span class="text-[9px] font-medium text-[var(--md-sys-color-on-surface-variant)] opacity-70" dir="ltr">+{{ $extraReaders }}</span>
                                            @endif
                                        @endif
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

    @empty
        <div wire:key="empty-state" class="flex flex-col items-center justify-center flex-1 gap-5 text-center">
            <div class="w-20 h-20 rounded-3xl flex items-center justify-center shadow-lg bg-[linear-gradient(135deg,var(--md-sys-color-primary-container),var(--md-sys-color-secondary-container))] text-[var(--md-sys-color-on-primary-container)]" aria-hidden="true">
                <span class="material-symbols-rounded text-4xl">campaign</span>
            </div>
            <div>
                <p class="text-base font-bold text-[var(--md-sys-color-on-surface)]">شروع گفتگو در {{ $this->activeChannel->name }}</p>
                <p class="text-[11px] mt-2 leading-relaxed text-[var(--md-sys-color-on-surface-variant)]">اولین پیام خود را در این کانال ارسال کنید</p>
            </div>
        </div>
    @endforelse

</div>

<button x-show="showScrollFab" x-transition x-on:click="scrollToBottom()"
        class="absolute bottom-28 left-8 z-20 w-10 h-10 rounded-lg flex items-center justify-center shadow-xl transition-all hover:scale-110 active:scale-95 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_20px_color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)]"
        aria-label="اسکرول به پایین">
    <span class="material-symbols-rounded text-[18px]" aria-hidden="true">keyboard_arrow_down</span>
</button>

<div x-show="showUndo"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-4"
     class="absolute bottom-20 left-1/2 -translate-x-1/2 z-50 flex items-center gap-4 px-5 py-3 rounded-lg shadow-2xl text-sm bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]">
    <button x-on:click="$wire.$island('messages').undoDelete()" class="font-bold transition-colors text-[#facc15]">↻ بازگشت</button>
</div>
