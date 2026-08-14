@php
    $p = $this->presenter;
    $hasOlder = $this->hasOlder;
@endphp

<div id="msg-viewport"
     class="flex flex-col flex-1 overflow-y-auto px-4 md:px-8 border-none shadow-[inset_0_4px_20px_color-mix(in_srgb,var(--md-sys-color-shadow)_3%,transparent)] py-6 space-y-1 msg-scrollbar relative"
     x-bind:class="{
         'bg-[var(--md-sys-color-primary-container)]/20': isHighlighted,
         'bg-[var(--md-sys-color-surface)]': !isHighlighted
     }"
     role="log"
     aria-label="پیام‌ها"
     aria-live="polite"
     aria-relevant="additions">

    <div wire:key="load-older" class="flex justify-center py-2" x-show="$wire.hasOlder" x-transition style="{{ $hasOlder ? '' : 'display:none;' }}">
        <x-ui.buttons.load-more
            action="loadMoreMessages"
            text="پیام‌های قدیمی‌تر"
            loadingText="..."
            icon="expand_less"
            wire:loading.attr="disabled"
            wire:target="loadMoreMessages"
            class="px-4 py-2 rounded-xl bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] text-xs font-semibold shadow-sm transition-all disabled:opacity-50" />
    </div>

    @forelse($this->groupedMessages as $date => $rawMessages)
        @php( $group = $p->messageGroup($date, $rawMessages, auth()->id(), $this->editTimeLimit))

        <div wire:key="date-{{ $date }}" class="flex items-center gap-3 py-4" role="separator" aria-label="{{ $group['label'] }}">
            <div
                class="flex-1 h-px bg-[linear-gradient(to_left,transparent,color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent))]"></div>
            <span
                class="text-[10px] font-bold uppercase tracking-widest px-3 py-1.5 rounded-lg shadow-sm bg-[color-mix(in_srgb,var(--md-sys-color-surface-variant)_85%,transparent)] text-[var(--md-sys-color-on-surface-variant)]">
                {{ $group['label'] }}
            </span>
            <div
                class="flex-1 h-px bg-[linear-gradient(to_right,transparent,color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent))]"></div>
        </div>

        @foreach($group['messages'] as $msg)
            <div wire:key="msg-{{ $msg['id'] }}"
                 data-rf="message-{{ $msg['id'] }}"
                 x-on:click="toggleActions({{ $msg['id'] }}, $event)"
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

                    <template x-if="editingMsg?.id === {{ $msg['id'] }}">
                        <div
                            class="rounded-xl overflow-hidden !bg-[var(--md-sys-color-surface)] ring-2 ring-[var(--md-sys-color-primary)] ring-offset-0 shadow-[0_4px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_18%,transparent)]">
                            <textarea wire:model.live="edit.editingBody"
                                      x-on:keydown.enter="if(!event.shiftKey){event.preventDefault();saveEdit({{ $msg['id'] }})}"
                                      x-on:keydown.escape="cancelEdit"
                                      x-data
                                      x-init="$nextTick(() => { $el.focus(); $el.selectionStart = $el.selectionEnd = $el.value.length })"
                                      x-on:input="$el.style.height='auto'; $el.style.height = Math.min($el.scrollHeight, 120) + 'px'"
                                      rows="1"
                                      class="w-full bg-transparent px-4 py-3 text-sm resize-none focus:outline-none leading-relaxed min-h-[40px] max-h-[120px] text-[var(--md-sys-color-on-surface)] placeholder:text-[var(--md-sys-color-on-surface-variant)]"></textarea>
                            @error('edit.editingBody')
                            <p class="px-4 pb-1 text-[10px] text-[var(--md-sys-color-error)] flex items-center gap-1">
                                <span class="material-symbols-rounded text-[10px]">error</span>{{ $message }}
                            </p>
                            @enderror
                            <div
                                class="flex items-center justify-between px-3 pb-2.5 pt-1.5 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_20%,transparent)]">
                                <span
                                    class="text-[9px] tracking-wide text-[var(--md-sys-color-on-surface-variant)] opacity-40">Enter ذخیره · Esc انصراف</span>
                                <div class="flex items-center gap-1.5">
                                    <button x-on:click.prevent="cancelEdit"
                                            class="px-3.5 py-1.5 rounded-lg text-[11px] font-semibold transition-all duration-150 hover:brightness-90 active:scale-95 bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_25%,transparent)] text-[var(--md-sys-color-on-surface-variant)]">
                                        انصراف
                                    </button>
                                    <button x-on:click.prevent="saveEdit({{ $msg['id'] }})"
                                            wire:loading.attr="disabled"
                                            class="px-3.5 py-1.5 rounded-lg text-[11px] font-semibold transition-all duration-150 hover:shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)] hover:brightness-110 active:scale-95 disabled:opacity-40 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]">
                                        <span wire:loading.remove wire:target="saveEdit">ذخیره</span>
                                        <span wire:loading wire:target="saveEdit"
                                              class="material-symbols-rounded text-[12px] animate-spin">progress_activity</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="deletingId === {{ $msg['id'] }}">
                        <div
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
                                <button x-on:click.prevent="deleteMessage" wire:loading.attr="disabled"
                                        class="px-3.5 py-1.5 rounded-lg text-[11px] font-semibold transition-all duration-150 hover:brightness-110 hover:shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-error)_35%,transparent)] active:scale-95 disabled:opacity-40 bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)]">
                                    <span wire:loading.remove wire:target="deleteMessage">حذف</span>
                                    <span wire:loading wire:target="deleteMessage"
                                          class="material-symbols-rounded text-[12px] animate-spin">progress_activity</span>
                                </button>
                            </div>
                        </div>
                    </template>

                    <template x-if="editingMsg?.id !== {{ $msg['id'] }} && deletingId !== {{ $msg['id'] }}">
                        <div class="relative">
                            <div
                                :style="isEmojiOnly(@js($msg['body_html'])) && 'font-size:2rem;background:none;box-shadow:none;padding:0'"                                @class([
                                      'px-4 py-2.5 text-sm leading-relaxed break-words select-text cursor-default ' . $msg['bubble_radius'],
                                      'bg-[linear-gradient(145deg,var(--md-sys-color-primary)_0%,color-mix(in_srgb,var(--md-sys-color-primary)_82%,var(--md-sys-color-tertiary))_100%)] text-[var(--md-sys-color-on-primary)] shadow-[0_3px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_28%,transparent),inset_0_1px_0_color-mix(in_srgb,white_12%,transparent)]' => $msg['is_mine'],
                                      'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-[0_1px_6px_color-mix(in_srgb,var(--md-sys-color-shadow)_6%,transparent),inset_0_1px_0_color-mix(in_srgb,white_6%,transparent)]' => !$msg['is_mine']
                                  ])
                                :class="$store.pinned.isPinned(@js($msg['id']), @js('message')) ? '!ring-2 !ring-[var(--md-sys-color-primary)]' : ''"
                            >

                                @include('livewire.dashboard.messaging.reply-quote', ['msg' => $msg])
                                @if($msg['body'] !== '')
                                    <span>{!! $msg['body_html'] !!}</span>
                                @endif
                                @include('livewire.dashboard.messaging.message-attachments', ['msg' => $msg])
                            </div>

                                @include('livewire.dashboard.messaging.message-actions', ['msg' => $msg])

                            @if($msg['is_last'])
                                <div
                                    class="flex items-center gap-1.5 mt-1.5 {{ $msg['is_mine'] ? 'justify-start pl-1' : 'justify-end pr-1' }}">
                                    @if($msg['is_edited'])
                                        <span
                                            class="text-[9px] tracking-wide font-medium italic text-[var(--md-sys-color-on-surface-variant)] opacity-45">ویرایش شده</span>
                                        <span
                                            class="w-[2px] h-[2px] rounded-full bg-[var(--md-sys-color-on-surface-variant)] opacity-30"></span>
                                    @endif
                                    <time
                                        class="text-[10px] font-medium tabular-nums text-[var(--md-sys-color-on-surface-variant)] opacity-60"
                                        datetime="{{ $msg['datetime'] }}"
                                        dir="ltr">{{ $msg['time'] }}</time>
                                    @if($msg['is_mine'])
                                        <span @class([
                                                    'material-symbols-rounded text-[13px] transition-colors duration-300',
                                                    'text-[var(--md-sys-color-primary)]' => $msg['is_read'],
                                                    'text-[var(--md-sys-color-on-surface-variant)] opacity-50' => !$msg['is_read']
                                                ])
                                              title="{{ $msg['is_read'] ? 'خوانده شد: ' . $msg['read_at_label'] : 'ارسال شد' }}">{{ $msg['is_read'] ? 'done_all' : 'done' }}
                                            </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </template>
                </div>
            </div>
        @endforeach

    @empty
        <div class="flex flex-col items-center justify-center flex-1 gap-5 text-center">
            <div
                class="w-20 h-20 rounded-3xl flex items-center justify-center shadow-lg bg-[linear-gradient(135deg,var(--md-sys-color-primary-container),var(--md-sys-color-secondary-container))] text-[var(--md-sys-color-on-primary-container)]"
                aria-hidden="true">
                <span class="material-symbols-rounded text-4xl">chat</span>
            </div>
            <div>
                <p class="text-base font-bold text-[var(--md-sys-color-on-surface)]">شروع گفتگو
                    با {{ $this->activeContact->name }}</p>
                <p class="text-[11px] mt-2 leading-relaxed text-[var(--md-sys-color-on-surface-variant)]">اولین پیام خود
                    را بنویسید و ارتباط کاری را آغاز کنید</p>
            </div>
        </div>
    @endforelse

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
    <button x-on:click="$wire.$island('messages').undoDelete().then(() => $wire.$island('sidebar').refreshUnread()).catch(() => {})" class="font-bold transition-colors text-[#facc15]">↻ بازگشت</button>
</div>
