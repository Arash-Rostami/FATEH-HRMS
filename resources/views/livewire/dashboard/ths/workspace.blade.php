<div class="space-y-5">
    @if($this->ticket)
        {{-- Assignee card + assign control --}}
        <div class="flex items-center gap-4 bg-[var(--md-sys-color-primary-container)] p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30 shadow-sm flex-wrap">
            <div class="w-12 h-12 rounded-lg text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center font-bold text-lg shadow-inner bg-[var(--md-sys-color-secondary-container)]">
                {{ $this->ticket->assignee ? mb_substr($this->ticket->assignee->name, 0, 1) : '❗' }}
            </div>
            <div>
                <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] font-bold tracking-wider uppercase">مسئول رسیدگی</p>
                <p class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">
                    {{ $this->ticket->assignee?->name ?? 'در انتظار تخصیص به کارشناس' }}
                </p>
            </div>

            @if($this->canAssign)
                <div class="mr-auto flex items-center gap-2">
                    <select wire:model="assigneeId"
                            class="text-xs rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] px-3 py-2 text-[var(--md-sys-color-on-surface)] focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]">
                        <option value="">انتخاب کارشناس...</option>
                        @foreach($this->assignableUsers as $candidate)
                            <option value="{{ $candidate->id }}">{{ $candidate->name }}</option>
                        @endforeach
                    </select>
                    <x-ui.buttons.form type="button" wire:click="assign" loading="assign"
                            class="!h-auto !px-3 !py-2 rounded-xl text-xs font-bold bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:brightness-110">
                        تخصیص
                    </x-ui.buttons.form>
                </div>
            @endif
        </div>

        {{-- Effectiveness card --}}
        @if($this->canSetEffectiveness || $this->ticket->effectiveness)
            <div class="bg-[var(--md-sys-color-surface-container-low)] rounded-2xl p-4 border border-[var(--md-sys-color-outline-variant)]/30 shadow-sm">
                <h5 class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] mb-3 flex items-center gap-1.5">
                    <span class="material-symbols-rounded text-[16px]">insights</span>
                    اثربخشی اقدام
                </h5>

                @if($this->canSetEffectiveness)
                    <div class="flex flex-wrap gap-2">
                        @foreach(['5' => 'بسیار مؤثر', '4' => 'مؤثر', '3' => 'خنثی', '2' => 'کم‌اثر', '1' => 'بی‌اثر'] as $value => $label)
                            <x-ui.buttons.form type="button" wire:click="setEffectiveness('{{ $value }}')" loading="setEffectiveness('{{ $value }}')"
                                    @class([
                                        '!h-auto !px-3 !py-1.5 rounded-xl text-xs font-semibold border',
                                        'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent' => $this->ticket->effectiveness === $value,
                                        'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)]' => $this->ticket->effectiveness !== $value,
                                    ])>
                                {{ $label }}
                            </x-ui.buttons.form>
                        @endforeach
                    </div>
                @elseif($this->ticket->effectiveness)
                    <p class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">
                        {{ ['5' => '★★★★★ — بسیار مؤثر', '4' => '★★★★☆ — مؤثر', '3' => '★★★☆☆ — خنثی', '2' => '★★☆☆☆ — کم‌اثر', '1' => '★☆☆☆☆ — بی‌اثر'][$this->ticket->effectiveness] ?? '—' }}
                    </p>
                @else
                    <p class="text-xs italic opacity-70 text-[var(--md-sys-color-on-surface-variant)]">هنوز ثبت نشده است.</p>
                @endif

                @if($this->canClose && $this->ticket->status !== 'closed')
                    <x-ui.buttons.form type="button" wire:click="closeTicket" loading="closeTicket"
                            class="mt-3 !h-auto !px-4 !py-2 rounded-xl text-xs font-bold bg-[var(--md-sys-color-tertiary)] text-[var(--md-sys-color-on-tertiary)] hover:brightness-110">
                        بستن تیکت
                    </x-ui.buttons.form>
                @endif
            </div>
        @endif

        {{-- Reply thread --}}
        <div class="bg-[var(--md-sys-color-surface-container-low)] rounded-2xl p-4 border border-[var(--md-sys-color-outline-variant)]/30 shadow-sm">
            <h5 class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] mb-3 flex items-center gap-1.5">
                <span class="material-symbols-rounded text-[16px]">forum</span>
                گفتگو
            </h5>

            <div id="ths-reply-thread"
                 x-data
                 x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
                 class="space-y-3 max-h-96 overflow-y-auto custom-scrollbar pr-1">
                @forelse($this->ticket->replies as $reply)
                    @include('livewire.dashboard.ths.reply-bubble', ['reply' => $reply, 'animateIn' => $loop->last])
                @empty
                    <p class="text-xs italic opacity-70 text-[var(--md-sys-color-on-surface-variant)] text-center py-4">هنوز پاسخی ثبت نشده است...</p>
                @endforelse
            </div>

            @if($this->canReply)
                <form wire:submit.prevent="postReply" x-data="{ empty: true }" class="mt-4 pt-4 border-t border-[var(--md-sys-color-outline-variant)]/30 space-y-2">
                    <textarea wire:model.defer="replyForm.body" rows="2" placeholder="پاسخ خود را بنویسید..."
                              @input="empty = $event.target.value.trim().length === 0"
                              class="w-full rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] px-3 py-2.5 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-surface)]"></textarea>
                    @error('replyForm.body') <p class="text-[11px] text-[var(--md-sys-color-error)]">{{ $message }}</p> @enderror

                    @if(count($replyForm->files))
                        <div class="flex flex-wrap items-center gap-1.5">
                            @foreach($replyForm->files as $i => $file)
                                <div wire:key="staged-ths-reply-file-{{ $i }}"
                                     class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg max-w-[180px] bg-[var(--md-sys-color-surface-container-high)] border border-[var(--md-sys-color-outline-variant)]/40">
                                    @if(str_starts_with($file->getMimeType() ?? '', 'image/'))
                                        <img src="{{ $file->temporaryUrl() }}" class="w-4 h-4 rounded object-cover flex-shrink-0" alt="">
                                    @else
                                        <span class="material-symbols-rounded text-[12px] flex-shrink-0">attach_file</span>
                                    @endif
                                    <span class="text-[10px] font-bold truncate">{{ $file->getClientOriginalName() }}</span>
                                    <button type="button" wire:click="removeReplyAttachment({{ $i }})" aria-label="حذف فایل"
                                            class="flex-shrink-0 w-4 h-4 rounded-full flex items-center justify-center hover:bg-[var(--md-sys-color-error-container)] hover:text-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-surface-variant)] transition-colors">
                                        <span class="material-symbols-rounded text-[11px]">close</span>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex items-center justify-between gap-2">
                        <label class="text-xs cursor-pointer flex items-center gap-1.5 text-[var(--md-sys-color-on-surface-variant)]">
                            <span class="material-symbols-rounded text-[16px]">attach_file</span>
                            <input type="file" multiple wire:model="replyForm.files" class="hidden"/>
                            پیوست
                        </label>
                        <x-ui.buttons.form type="submit" loading="postReply" x-bind:disabled="empty"
                                class="!h-auto !px-4 !py-2 rounded-xl text-xs font-bold bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:brightness-110">
                            ارسال پاسخ
                        </x-ui.buttons.form>
                    </div>
                    @error('replyForm.files') <p class="text-[11px] text-[var(--md-sys-color-error)]">{{ $message }}</p> @enderror
                </form>
            @elseif($this->ticket->status === 'closed')
                <p class="mt-4 pt-4 border-t border-[var(--md-sys-color-outline-variant)]/30 text-[11px] text-center italic opacity-60 text-[var(--md-sys-color-on-surface-variant)]">
                    این تیکت بسته شده و امکان ارسال پاسخ جدید وجود ندارد.
                </p>
            @endif
        </div>
    @endif
</div>
