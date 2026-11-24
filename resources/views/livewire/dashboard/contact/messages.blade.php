@php
    $p = $this->presenter;
    $hasOlder = $this->hasOlder;
    $hasNewer = $this->hasNewer;
    $firstUnreadId = $this->newMessagesAnchorId;
    $contact = $this->activeContact;
    $names = [
        (int) auth()->id() => ['name' => auth()->user()->name, 'avatar' => auth()->user()->getProfileImageUrl() ?? auth()->user()->getInitialsAvatarUrl(), 'inactive' => !auth()->user()->isActive()],
        (int) $contact?->id => ['name' => $contact?->name, 'avatar' => $contact?->getProfileImageUrl() ?? $contact?->getInitialsAvatarUrl(), 'inactive' => $contact && !$contact->isActive()],
    ];
    $pin = $p->pinned($contact, $names);
    $canReact = !$this->activeContactArchived;
    $aiOn = contactAiOn();
@endphp

<div x-show="typeFilterOpen || typeFilter" x-cloak x-transition
     class="flex-shrink-0 px-4 md:px-8 pt-3 pb-1 bg-[var(--md-sys-color-surface)]">
    <div class="flex flex-row-reverse items-center gap-3 min-w-0">
        @include('livewire.dashboard.messaging.type-filter')
        <button type="button" x-show="typeFilter" x-cloak x-on:click="clearAllFilters()"
                class="flex-shrink-0 px-2.5 h-7 rounded-full flex items-center gap-1 text-[10px] font-semibold bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] transition-all active:scale-95">
            <span class="material-symbols-rounded text-[13px]">close</span>
            همه
        </button>
    </div>
</div>

@if($pin)
    @include('livewire.dashboard.messaging.pin-banner')
@endif
<div id="msg-viewport"
     x-on:scroll.passive="$store.activityReactionPicker.close()"
     class="no-anchor flex flex-col flex-1 overflow-y-auto px-4 md:px-8 border-none shadow-[inset_0_4px_20px_color-mix(in_srgb,var(--md-sys-color-shadow)_3%,transparent)] py-6 space-y-1 msg-scrollbar relative"
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
            island="messages"
            :keep-place="true"
            text="پیام‌های قدیمی‌تر"
            loadingText="..."
            icon="expand_less"
            wire:loading.attr="disabled"
            wire:target="loadMoreMessages"
            class="px-4 py-2 rounded-xl bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] text-xs font-semibold shadow-sm transition-all disabled:opacity-50" />
    </div>

    @forelse($this->groupedMessages as $date => $rawMessages)
        @php
            $group = $p->messageGroup($date, $rawMessages, auth()->id(), $this->editTimeLimit, $firstUnreadId, $names);
        @endphp

        <div wire:key="date-{{ $date }}" data-day="{{ $date }}" class="flex items-center gap-3 py-4" role="separator" aria-label="{{ $group['label'] }}">
            <div
                class="flex-1 h-px bg-[linear-gradient(to_left,transparent,color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent))]"></div>
            <button type="button" x-on:click="toggleDays()" title="فهرست روزها"
                class="text-[10px] font-bold uppercase tracking-widest px-3 py-1.5 rounded-lg shadow-sm cursor-pointer transition-colors bg-[color-mix(in_srgb,var(--md-sys-color-surface-variant)_85%,transparent)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)]">
                <span class="flex flex-col items-center gap-0.5 leading-tight">
                    <span>{{ $group['label'] }}</span>
                    <span class="text-[9px] font-normal opacity-70">({{ $group['relative'] }})</span>
                </span>
            </button>
            <div
                class="flex-1 h-px bg-[linear-gradient(to_right,transparent,color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent))]"></div>
        </div>

        @foreach($group['messages'] as $msg)
            @if(!empty($msg['is_new_messages']))
                <div wire:key="new-msgs-{{ $msg['id'] }}" class="flex items-center gap-3 py-3" role="separator" aria-label="پیام‌های جدید">
                    <div class="flex-1 h-px bg-[linear-gradient(to_left,transparent,var(--md-sys-color-tertiary))]"></div>
                    <span class="text-[10px] font-bold tracking-widest px-3 py-1.5 rounded-lg shadow-sm flex items-center gap-1.5 bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]" title="اولین پیامی که هنوز نخوانده‌اید">
                        <span class="material-symbols-rounded text-[12px]" aria-hidden="true">mark_chat_unread</span>پیام‌های جدید
                    </span>
                    <div class="flex-1 h-px bg-[linear-gradient(to_right,transparent,var(--md-sys-color-tertiary))]"></div>
                </div>
            @endif
            <div wire:key="msg-{{ $msg['id'] }}"
                 data-rf="message-{{ $msg['id'] }}"
                 data-types="{{ $msg['type_flags'] }}"
                 x-show="passesTypeFilter($el.dataset.types)"
                 x-on:click="toggleActions({{ $msg['id'] }}, $event)"
                 @class([
                     'flex items-end gap-2 group bubble-enter',
                     'justify-start' => $msg['is_mine'],
                     'justify-end' => !$msg['is_mine'],
                     $msg['gap_class']
                 ]) style="animation-delay: {{ $msg['animation_delay'] }}s">

                @if($msg['is_last'])
                    <div wire:key="contact-msg-avatar" class="flex-shrink-0 self-end mb-0.5">
                        <x-ui.avatar :existingImage="$msg['sender_avatar']" :alt="$msg['sender_name']" :inactive="$msg['sender_inactive']"
                                     size="sm" icon="person" icon-size="text-base"
                                     :online="$msg['sender_online']" :status="$msg['sender_presence']" :focus="$msg['sender_focus']"/>
                    </div>
                @else
                    <div wire:key="contact-msg-spacer" class="flex-shrink-0 w-7" aria-hidden="true"></div>
                @endif

                <div class="max-w-[70%] md:max-w-[60%] lg:max-w-[55%] relative"
                     :class="editingMsg?.id === {{ $msg['id'] }} ? 'w-full !max-w-[80%] md:!max-w-[68%] lg:!max-w-[62%]' : ''">

                    @if($msg['is_mine'])
                    <div x-show="editingMsg?.id === {{ $msg['id'] }}" style="display: none;"
                        data-edit-box="{{ $msg['id'] }}"
                        x-bind:class="aiEditBusy && aiEditId === {{ $msg['id'] }} ? 'ai-halo !ring-[color-mix(in_srgb,var(--ai-color)_25%,transparent)]' : ''"
                        class="relative scroll-my-6 rounded-xl overflow-hidden !bg-[var(--md-sys-color-surface)] ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-primary)_45%,transparent)] ring-offset-0 shadow-[0_4px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_18%,transparent)]">
                        <textarea dir="auto" wire:ignore.self wire:model="edit.editingBody"
                                  aria-label="ویرایش پیام"
                                  x-bind:disabled="aiEditBusy && aiEditId === {{ $msg['id'] }}"
                                  x-on:keydown.enter="if(!$event.shiftKey && !$event.isComposing){$event.preventDefault();saveEdit({{ $msg['id'] }})}"
                                  x-on:keydown.escape.stop="cancelEdit"
                                  x-effect="if (editingMsg?.id === {{ $msg['id'] }}) openEdit($el)"
                                  x-on:input="fitEdit($el)"
                                  :style="{ fontFamily: fontFamilyValue() }"
                                  rows="1"
                                  class="w-full bg-transparent px-4 py-2.5 text-sm resize-none [unicode-bidi:plaintext] focus:outline-none leading-relaxed min-h-[40px] max-h-[320px] text-[var(--md-sys-color-on-surface)] placeholder:text-[var(--md-sys-color-on-surface-variant)]"></textarea>
                        @error('edit.editingBody')
                        <p wire:key="contact-edit-error" class="px-4 pb-1 text-[10px] text-[var(--md-sys-color-error)] flex items-center gap-1">
                            <span class="material-symbols-rounded text-[10px]">error</span>{{ $message }}
                        </p>
                        @enderror
                        @include('livewire.dashboard.messaging.ai-edit', ['msgId' => $msg['id'], 'scope' => 'contact', 'aiOn' => $aiOn, 'part' => 'status'])
                        <div
                            class="flex items-center justify-between px-3 pb-2.5 pt-1.5 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_20%,transparent)]">
                            <span
                                class="hidden md:inline text-[9px] tracking-wide text-[var(--md-sys-color-on-surface-variant)] opacity-70">Enter ذخیره · Esc انصراف</span>
                            <div class="flex items-center gap-1.5 ms-auto">
                                <button x-on:click.prevent="cancelEdit"
                                        class="px-3.5 py-1.5 rounded-lg text-[11px] font-semibold transition-all duration-150 hover:brightness-90 active:scale-95 bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_25%,transparent)] text-[var(--md-sys-color-on-surface-variant)]">
                                    انصراف
                                </button>
                                <x-ui.buttons.form x-on:click.prevent="saveEdit({{ $msg['id'] }})"
                                        wire:loading.attr="disabled"
                                        loading="saveEdit"
                                        x-bind:disabled="aiEditBusy && aiEditId === {{ $msg['id'] }}"
                                        class="px-3.5 py-1.5 h-auto rounded-lg text-[11px] font-semibold transition-all duration-150 hover:shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)] hover:brightness-110 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]">
                                    ذخیره
                                </x-ui.buttons.form>
                            </div>
                        </div>
                        @include('livewire.dashboard.messaging.ai-edit', ['msgId' => $msg['id'], 'scope' => 'contact', 'aiOn' => $aiOn, 'part' => 'meter'])
                    </div>
                    @endif

                    @include('livewire.dashboard.messaging.delete-confirm', ['msg' => $msg])

                    <div x-show="editingMsg?.id !== {{ $msg['id'] }} && deletingId !== {{ $msg['id'] }}">
                        <div class="relative">
                            <div
                                :style="isEmojiOnly(@js($msg['forwarded_html'] === null ? $msg['body_html'] : '')) && 'font-size:2rem;background:none;box-shadow:none;padding:0'"                                @class([
                                      'relative msg-bubble touch-pan-y px-4 py-2.5 text-sm leading-relaxed break-words select-text cursor-default ' . $msg['bubble_radius'],
                                      'bubble-mine shadow-[0_3px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_28%,transparent),inset_0_1px_0_color-mix(in_srgb,white_12%,transparent)]' => $msg['is_mine'],
                                      'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-[0_1px_6px_color-mix(in_srgb,var(--md-sys-color-shadow)_6%,transparent),inset_0_1px_0_color-mix(in_srgb,white_6%,transparent)]' => !$msg['is_mine']
                                  ])
                                :class="$store.pinned.isPinned(@js($msg['id']), @js('message')) ? '!ring-2 !ring-[var(--md-sys-color-primary)]' : ''"
                            >

                                @include('livewire.dashboard.messaging.reply-quote', ['msg' => $msg])
                                @include('livewire.dashboard.messaging.forward-quote', ['msg' => $msg])
                                @if($msg['body_html'] !== '')
                                    <span dir="auto" class="block break-words [unicode-bidi:plaintext]" :style="fontStyle({{ $msg['id'] }})">{!! $msg['body_html'] !!}</span>
                                @endif
                                @include('livewire.dashboard.messaging.message-attachments', ['msg' => $msg])
                                @include('livewire.dashboard.messaging.message-actions', ['msg' => $msg, 'canReact' => $canReact, 'canWrite' => $canReact, 'canForward' => true, 'pinnedId' => $pin['id'] ?? null, 'pinLabel' => 'سنجاق پیام', 'canEnhance' => $aiOn])
                            </div>

                                @include('livewire.dashboard.messaging.reactions', ['msg' => $msg, 'canReact' => $canReact])

                            @if($msg['is_edited'] && !$msg['is_last'])
                                <div wire:key="contact-msg-edited" class="flex items-center gap-1.5 mt-1.5 {{ $msg['is_mine'] ? 'justify-start pl-1' : 'justify-end pr-1' }}">
                                    <span class="text-[9px] tracking-wide font-medium italic text-[var(--md-sys-color-on-surface-variant)] opacity-45">ویرایش شده</span>
                                </div>
                            @endif
                            @if($msg['is_last'] || count($msg['viewed_files']))
                                <div wire:key="contact-msg-footer"
                                    class="flex items-center gap-1.5 mt-1.5 {{ $msg['is_mine'] ? 'justify-start pl-1' : 'justify-end pr-1' }}">
                                    @if($msg['is_last'])
                                    @if($msg['is_edited'])
                                        <span
                                            class="text-[9px] tracking-wide font-medium italic text-[var(--md-sys-color-on-surface-variant)] opacity-45">ویرایش شده</span>
                                        <span
                                            class="w-[2px] h-[2px] rounded-full bg-[var(--md-sys-color-on-surface-variant)] opacity-30"></span>
                                    @endif
                                    <time
                                        class="text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)] opacity-60"
                                        datetime="{{ $msg['datetime'] }}"
                                        dir="ltr">{{ $msg['time'] }}</time>
                                    @endif
                                    @if($msg['is_mine'])
                                        @if(!$msg['is_read'] && !count($msg['viewed_files']))
                                            <span wire:key="contact-receipt-sent" class="material-symbols-rounded text-[13px] text-[var(--md-sys-color-on-surface-variant)] opacity-70" title="ارسال شد">done</span>
                                        @else
                                            <x-ui.hover-popover wire:key="contact-receipt-{{ $msg['id'] }}" alignment="top-full left-0 mt-2 origin-top-left" width="w-64" surface="default">
                                                <x-slot:trigger>
                                                    <span class="flex items-center gap-1">
                                                        <span @class([
                                                            'material-symbols-rounded text-[13px]',
                                                            'text-[var(--md-sys-color-primary)]' => $msg['is_read'],
                                                            'text-[var(--md-sys-color-on-surface-variant)] opacity-70' => !$msg['is_read'],
                                                        ])>{{ $msg['is_read'] ? 'done_all' : 'done' }}</span>
                                                        @if(count($msg['viewed_files']))
                                                            <span class="w-4 h-4 rounded-md bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] text-[8px] font-bold flex items-center justify-center ring-2 ring-[var(--md-sys-color-surface)]" dir="ltr">{{ convertToPersian(array_sum(array_column($msg['viewed_files'], 'viewer_count'))) }}</span>
                                                        @endif
                                                    </span>
                                                </x-slot:trigger>
                                                <x-slot:body>
                                                    <div class="p-2.5 flex flex-col gap-2 max-h-[280px] overflow-y-auto">
                                                        <div>
                                                            <p class="px-1 pb-1 text-[10px] font-bold text-[var(--md-sys-color-on-surface-variant)]">
                                                                {{ $msg['is_read'] ? 'خوانده شده توسط' : 'ارسال شد' }}
                                                            </p>
                                                            @if($msg['is_read'])
                                                                <div class="flex items-center gap-2">
                                                                    <span class="w-4 h-4 rounded-md overflow-hidden ring-2 ring-[var(--md-sys-color-surface)] flex-shrink-0">
                                                                        <x-ui.avatar :existingImage="$names[(int) $contact?->id]['avatar']" :alt="$contact?->name" :inactive="$names[(int) $contact?->id]['inactive']" icon="person" icon-size="text-[8px]" />
                                                                    </span>
                                                                    <span class="text-[11px] font-medium text-[var(--md-sys-color-on-surface)] truncate flex-1 min-w-0">{{ $contact?->name }}</span>
                                                                    <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-60 flex-shrink-0" dir="ltr">{{ $msg['read_at_label'] }}</span>
                                                                </div>
                                                            @endif
                                                        </div>

                                                        @if(count($msg['viewed_files']))
                                                            <div class="h-px bg-[var(--md-sys-color-outline-variant)]/40"></div>
                                                            @foreach($msg['viewed_files'] as $att)
                                                                <div>
                                                                    <p class="px-1 pb-1 text-[10px] font-bold text-[var(--md-sys-color-on-surface-variant)]">
                                                                        دیده شده توسط{{ count($msg['attachments']) > 1 ? ' · ' . $att['name'] : '' }}
                                                                    </p>
                                                                    <div class="flex flex-col gap-1.5">
                                                                        @foreach($att['viewers'] as $viewer)
                                                                            <div class="flex items-center gap-2">
                                                                                <span class="w-4 h-4 rounded-md overflow-hidden ring-2 ring-[var(--md-sys-color-surface)] flex-shrink-0">
                                                                                    <x-ui.avatar :existingImage="$viewer['avatar']" :alt="$viewer['name']" :inactive="$viewer['inactive']" icon="person" icon-size="text-[8px]" />
                                                                                </span>
                                                                                <span class="text-[11px] font-medium text-[var(--md-sys-color-on-surface)] truncate flex-1 min-w-0">{{ $viewer['name'] }}</span>
                                                                                <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-60 flex-shrink-0" dir="ltr">{{ toJalali($viewer['viewed_at'], 'Y/m/d H:i') }}</span>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                </x-slot:body>
                                            </x-ui.hover-popover>
                                        @endif
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

    @empty
        <div wire:key="empty-state" class="flex flex-col items-center justify-center flex-1 gap-5 text-center">
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

    <div wire:key="load-newer" class="flex justify-center py-2" x-show="$wire.hasNewer" x-transition style="{{ $hasNewer ? '' : 'display:none;' }}">
        <x-ui.buttons.load-more
            action="loadNewerMessages"
            island="messages"
            wrap="loadNewer"
            text="پیام‌های جدیدتر"
            loadingText="..."
            icon="expand_more"
            wire:loading.attr="disabled"
            wire:target="loadNewerMessages"
            class="px-4 py-2 rounded-xl bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] text-xs font-semibold shadow-sm transition-all disabled:opacity-50" />
    </div>

</div>

@include('livewire.dashboard.messaging.latest-button', ['show' => 'showScrollFab || $wire.focusAnchorId != null', 'position' => 'absolute bottom-28 left-8 z-20'])

<div x-show="showUndo"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-4"
     class="absolute bottom-20 left-1/2 -translate-x-1/2 z-50 flex items-center gap-4 px-5 py-3 rounded-lg shadow-2xl text-sm bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]">
    <button x-on:click="undoDelete()" class="font-bold transition-colors text-[var(--md-sys-color-on-primary)]">↻ بازگشت</button>
</div>
