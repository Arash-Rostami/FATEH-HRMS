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
                    <button wire:click="assign" wire:loading.attr="disabled" wire:target="assign"
                            class="px-3 py-2 rounded-xl text-xs font-bold bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:brightness-110 transition disabled:opacity-50">
                        تخصیص
                    </button>
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
                            <button wire:click="setEffectiveness('{{ $value }}')" wire:loading.attr="disabled" wire:target="setEffectiveness('{{ $value }}')"
                                    class="px-3 py-1.5 rounded-xl text-xs font-semibold border transition disabled:opacity-50"
                                    @class([
                                        'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent' => $this->ticket->effectiveness === $value,
                                        'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)]' => $this->ticket->effectiveness !== $value,
                                    ])>
                                {{ $label }}
                            </button>
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
                    <button wire:click="closeTicket" wire:loading.attr="disabled" wire:target="closeTicket"
                            class="mt-3 px-4 py-2 rounded-xl text-xs font-bold bg-[var(--md-sys-color-tertiary)] text-[var(--md-sys-color-on-tertiary)] hover:brightness-110 transition disabled:opacity-50">
                        بستن تیکت
                    </button>
                @endif
            </div>
        @endif

        {{-- Reply thread --}}
        <div class="bg-[var(--md-sys-color-surface-container-low)] rounded-2xl p-4 border border-[var(--md-sys-color-outline-variant)]/30 shadow-sm">
            <h5 class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)] mb-3 flex items-center gap-1.5">
                <span class="material-symbols-rounded text-[16px]">forum</span>
                گفتگو
            </h5>

            <div class="space-y-3 max-h-96 overflow-y-auto custom-scrollbar pr-1">
                @forelse($this->ticket->replies as $reply)
                    @include('livewire.dashboard.ths.reply-bubble', ['reply' => $reply])
                @empty
                    <p class="text-xs italic opacity-70 text-[var(--md-sys-color-on-surface-variant)] text-center py-4">هنوز پاسخی ثبت نشده است...</p>
                @endforelse
            </div>

            @if($this->canReply)
                <form wire:submit.prevent="postReply" class="mt-4 pt-4 border-t border-[var(--md-sys-color-outline-variant)]/30 space-y-2">
                    <textarea wire:model.defer="replyForm.body" rows="2" placeholder="پاسخ خود را بنویسید..."
                              class="w-full rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] px-3 py-2.5 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-surface)]"></textarea>
                    @error('replyForm.body') <p class="text-[11px] text-[var(--md-sys-color-error)]">{{ $message }}</p> @enderror

                    <div class="flex items-center justify-between gap-2">
                        <label class="text-xs cursor-pointer flex items-center gap-1.5 text-[var(--md-sys-color-on-surface-variant)]">
                            <span class="material-symbols-rounded text-[16px]">attach_file</span>
                            <input type="file" multiple wire:model="replyForm.files" class="hidden"/>
                            پیوست
                        </label>
                        <button type="submit" wire:loading.attr="disabled" wire:target="postReply"
                                class="px-4 py-2 rounded-xl text-xs font-bold bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:brightness-110 transition disabled:opacity-50">
                            ارسال پاسخ
                        </button>
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
