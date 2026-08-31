<div class="min-h-full flex flex-col gap-3 relative" wire:key="chat-{{ $activeProjectId }}" x-on:project-teamchat-refresh.window="$wire.refreshTeamChat()" x-on:project-teamchat-activate.window="$wire.activate()">
    <x-ui.decor.chat-pattern x-show="backgroundPattern === 'on'"/>
    @if($activated)
        @if($this->teamChatMessages['hasMore'])
            <x-ui.buttons.load-more action="loadOlderTeamChat" text="پیام‌های قدیمی‌تر" loadingText="در حال بارگذاری…"
                icon="expand_less" wire:loading.attr="disabled" wire:target="loadOlderTeamChat"
                class="mx-auto px-4 py-2 rounded-xl bg-[var(--md-sys-color-surface-variant)]/50 text-[var(--md-sys-color-on-surface-variant)] text-xs font-medium disabled:opacity-50" />
        @endif

        <div id="team-chat-viewport" role="log" aria-live="polite" aria-relevant="additions" aria-label="پیام‌های گفتگوی تیم" class="flex-1 min-h-0 overflow-y-auto custom-scrollbar flex flex-col gap-1 px-1">
            @forelse($this->groupedTeamChatMessages as $group)
                <div class="flex items-center gap-3 py-3" wire:key="date-{{ $group['date'] }}">
                    <div class="flex-1 h-px bg-[linear-gradient(to_left,transparent,color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent))]"></div>
                    <span class="text-[10px] font-bold tracking-widest px-2 py-1 rounded-lg bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">{{ $group['label'] }}</span>
                    <div class="flex-1 h-px bg-[linear-gradient(to_right,transparent,color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent))]"></div>
                </div>
                @foreach($group['messages'] as $msg)
                    <div @class(['flex items-end gap-2 animate-bubble-in', 'justify-start' => $msg['is_mine'], 'justify-end' => !$msg['is_mine'], $msg['gap_class']]) wire:key="chat-msg-{{ $msg['id'] }}" style="animation-delay: {{ $msg['animation_delay'] }}s">
                        @if($msg['is_last'])
                            <div class="flex-shrink-0 w-7 h-7 rounded-lg overflow-hidden self-end mb-0.5 shadow-sm">
                                <x-ui.avatar :existingImage="$msg['sender_avatar']" :alt="$msg['sender_name']" icon="person" icon-size="text-base" />
                            </div>
                        @else
                            <div class="flex-shrink-0 w-7" aria-hidden="true"></div>
                        @endif
                        <div class="max-w-[75%] md:max-w-[65%] relative">
                            <div @class([
                                'px-4 py-2.5 text-sm leading-relaxed break-words ' . $msg['bubble_radius'],
                                'bg-[linear-gradient(145deg,var(--md-sys-color-primary)_0%,color-mix(in_srgb,var(--md-sys-color-primary)_82%,var(--md-sys-color-tertiary))_100%)] text-[var(--md-sys-color-on-primary)] shadow-[0_3px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_28%,transparent)]' => $msg['is_mine'],
                                'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-sm' => !$msg['is_mine'],
                                'ring-2 ring-[var(--md-sys-color-tertiary)]' => $msg['mentions_you'],
                            ])>
                                @if($msg['mentions_you'])
                                    <span class="flex items-center gap-1 text-[10px] font-bold mb-1 text-[var(--md-sys-color-tertiary)]"><span class="material-symbols-rounded text-[13px]">alternate_email</span> اشاره به شما</span>
                                @endif
                                @if(!$msg['is_mine'] && $msg['is_first'])
                                    <p class="text-[10px] font-bold mb-1 text-[var(--md-sys-color-primary)]">{{ $msg['sender_name'] }}</p>
                                @endif
                                @if($msg['body'] !== '')
                                    <span class="break-words">{!! $msg['body_html'] !!}</span>
                                @endif
                                @include('livewire.dashboard.messaging.message-attachments', ['msg' => $msg])
                            </div>
                            @if($msg['is_last'])
                                <div class="flex items-center gap-1.5 mt-1 {{ $msg['is_mine'] ? 'justify-start pl-1' : 'justify-end pr-1' }}">
                                    <time class="text-[10px] font-medium tabular-nums text-[var(--md-sys-color-on-surface-variant)] opacity-60" datetime="{{ $msg['datetime'] }}" dir="ltr">{{ $msg['time'] }}</time>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @empty
                <x-ui.empty icon="forum" title="هنوز پیامی ارسال نشده" :description="'شروع گفتگو در ' . ($this->activeProject?->name ?? '')" variant="list"/>
            @endforelse
        </div>
    @else
        <div class="flex-1 min-h-0 flex flex-col gap-1 px-1 animate-fade" role="status" aria-label="در حال بارگذاری پیام‌ها">
            @for($i = 0; $i < 4; $i++)
                <div @class(['flex items-end gap-2', 'justify-start' => $i % 2 === 0, 'justify-end' => $i % 2 !== 0])>
                    <x-ui.loaders.skeleton.bar width="{{ $i % 2 === 0 ? 'w-2/5' : 'w-1/3' }}" height="h-9" class="rounded-2xl"/>
                </div>
            @endfor
        </div>
    @endif

    <button x-show="showScrollFab" x-transition x-on:click="scrollToBottom(true)"
            class="self-center my-2 w-9 h-9 rounded-lg flex items-center justify-center shadow-xl transition-all hover:scale-110 active:scale-95 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_20px_color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)]"
            aria-label="اسکرول به پایین">
        <span class="material-symbols-rounded text-[18px]" aria-hidden="true">keyboard_arrow_down</span>
    </button>

    <footer class="flex-shrink-0 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] bg-[var(--md-sys-color-surface)]">
        <div class="relative">
            <div x-show="mentionOpen" x-cloak x-transition x-on:click.outside="mentionOpen = false"
                 class="absolute bottom-full end-2 mb-2 p-1.5 rounded-xl z-40 min-w-[220px] max-h-[240px] overflow-y-auto bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-[0_12px_32px_color-mix(in_srgb,var(--md-sys-color-scrim)_22%,transparent)]"
                 role="listbox" aria-label="اشاره به کاربر">
                <template x-for="(name, i) in mentionMatches" :key="i">
                    <button type="button" x-on:click.prevent="pickMention(i)"
                            x-on:mouseenter="mentionActiveIndex = i"
                            :class="mentionActiveIndex === i ? 'bg-[var(--md-sys-color-primary-container)]' : 'hover:bg-[var(--md-sys-color-surface-variant)]'"
                            class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[12.5px] font-medium text-[var(--md-sys-color-on-surface)] text-start">
                        <span class="material-symbols-rounded text-[15px] text-[var(--md-sys-color-primary)]">alternate_email</span>
                        <span class="truncate" x-text="name"></span>
                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" :class="$wire.mentionMemberPresence[name]?.presence_class" :title="$wire.mentionMemberPresence[name]?.presence_label"></span>
                    </button>
                </template>
            </div>

            @if(count($chatComposer->attachments))
                <div class="flex flex-wrap items-center gap-1.5 px-1 pt-2">
                    @foreach($chatComposer->attachments as $i => $file)
                        <div wire:key="staged-teamchat-file-{{ $i }}"
                             class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg max-w-[180px] bg-[var(--tool-gold-bg)] text-[var(--tool-gold-text)] border border-[color-mix(in_srgb,var(--tool-gold-color)_25%,transparent)]">
                            @if(str_starts_with($file->getMimeType() ?? '', 'image/'))
                                <img src="{{ $file->temporaryUrl() }}" class="w-4 h-4 rounded object-cover flex-shrink-0" alt="">
                            @else
                                <span class="material-symbols-rounded text-[12px] flex-shrink-0">attach_file</span>
                            @endif
                            <span class="text-[10px] font-bold truncate">{{ $file->getClientOriginalName() }}</span>
                            <button type="button" wire:click="removeAttachment({{ $i }})" aria-label="حذف فایل"
                                    class="flex-shrink-0 w-4 h-4 rounded-full flex items-center justify-center hover:bg-[var(--md-sys-color-error-container)] hover:text-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-surface-variant)] transition-colors">
                                <span class="material-symbols-rounded text-[11px]">close</span>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="flex items-center gap-2 px-1 py-2">
                <textarea id="team-chat-ta" wire:model.defer="chatComposer.body" x-on:input="$el.style.height='auto';$el.style.height=Math.min($el.scrollHeight,120)+'px'; detectMention($event)" x-on:keydown="onComposerKeydown($event)" rows="1" placeholder="برای هماهنگی روزمره پیام بدهید…" aria-label="متن پیام"
                          class="flex-1 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] bg-[var(--md-sys-color-surface-variant)] px-3 py-2.5 text-sm resize-none min-h-[40px] max-h-[120px] focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-surface)]"></textarea>
                <label for="team-chat-attachments" aria-label="افزودن فایل ضمیمه"
                       class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center cursor-pointer transition-all hover:brightness-95 active:scale-90 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">
                    <span class="material-symbols-rounded text-lg">attach_file</span>
                </label>
                <input id="team-chat-attachments" type="file" multiple wire:model="chatComposer.attachments" class="hidden"
                       accept="image/jpeg,image/png,image/gif,image/webp,application/pdf,.doc,.docx,.xls,.xlsx,.zip,.rar"/>
                <button type="button" x-on:click.prevent="sendMessage" wire:loading.attr="disabled" wire:target="sendChatMessage" aria-label="ارسال پیام"
                        class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] disabled:opacity-50 hover:brightness-110 transition">
                    <span class="material-symbols-rounded text-lg rotate-180">send</span>
                </button>
            </div>
        </div>
        @error('chatComposer.body') <p class="text-xs text-[var(--md-sys-color-error)] px-1 pb-1">{{ $message }}</p> @enderror
        @error('chatComposer.attachments') <p class="text-xs text-[var(--md-sys-color-error)] px-1 pb-1">{{ $message }}</p> @enderror
        @error('chatComposer.attachments.*') <p class="text-xs text-[var(--md-sys-color-error)] px-1 pb-1">{{ $message }}</p> @enderror
    </footer>
</div>
