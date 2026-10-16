<div class="relative flex-1 flex flex-col"
     x-data="{ dragging: 0 }"
     x-on:confirmation-confirmed.window="{{ confirmDialogListener(['closeTicket' => false, 'withdrawTicket' => false, 'releaseTicket' => false, 'rerouteTicket' => false]) }}"
     @if($this->canReply)
         x-on:dragenter.prevent="dragging++"
         x-on:dragover.prevent
         x-on:dragleave="dragging = Math.max(0, dragging - 1)"
         x-on:drop.prevent="dragging = 0; if ($event.dataTransfer?.files?.length) $wire.uploadMultiple('replyForm.files', $event.dataTransfer.files)"
     @endif>
    @if($this->ticket)
        <div wire:key="ths-workspace-ticket" class="contents">
            @include('livewire.dashboard.ths.workspace.action-bar')

            <div class="flex-1 flex flex-col gap-4 px-4 sm:px-6 py-4">
                @php $lastDay = null; @endphp
                <div id="ths-reply-thread"
                     x-data
                     x-init="$nextTick(() => { const pane = $el.closest('[data-ths-pane]'); if (pane) pane.scrollTop = pane.scrollHeight })"
                     wire:loading.class="opacity-60"
                     wire:target="postReply"
                     class="flex flex-col gap-3 transition-opacity">
                    @forelse($this->ticket->replies as $reply)
                        @php
                            $day = $reply->created_at?->toDateString();
                            $kind = $presenter->replyKind($reply);
                        @endphp
                        @if($day !== $lastDay)
                            @php $lastDay = $day; @endphp
                            @include('livewire.dashboard.ths.workspace.day-separator', ['date' => $reply->created_at, 'dayKey' => $day])
                        @endif
                        @if($kind === 'reply')
                            @include('livewire.dashboard.ths.workspace.reply-bubble', ['reply' => $reply, 'animateIn' => $loop->last])
                        @else
                            @include('livewire.dashboard.ths.workspace.system-entry', ['reply' => $reply, 'kind' => $kind])
                        @endif
                    @empty
                        <div wire:key="ths-replies-empty" class="contents">
                            <x-ui.empty icon="forum" title="هنوز پاسخی برای این تیکت ثبت نشده است" :description="$this->canReply ? 'اولین پیام را شما بنویسید.' : null"/>
                        </div>
                    @endforelse
                </div>

            </div>

            <div class="sticky bottom-0 z-10 mt-auto flex flex-col">
                @include('livewire.dashboard.ths.workspace.closing')

                @if($this->canReply)
                    @include('livewire.dashboard.ths.workspace.composer')
                @elseif($this->ticket->status === 'closed')
                    <p wire:key="ths-reply-closed"
                       class="flex items-center justify-center gap-1.5 px-4 sm:px-6 pt-3.5 pb-[calc(0.875rem+env(safe-area-inset-bottom))] border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] bg-[var(--md-sys-color-surface)] text-[11px] text-center italic opacity-75 text-[var(--md-sys-color-on-surface-variant)]">
                        <span class="material-symbols-rounded text-[14px]">lock</span>
                        {{ $this->ticket->action_result === \App\Models\Ticket::WITHDRAWN_RESULT ? 'این تیکت لغو شده و امکان ارسال پاسخ جدید وجود ندارد.' : 'این تیکت بسته شده و امکان ارسال پاسخ جدید وجود ندارد.' }}
                    </p>
                @endif
            </div>

            @if($this->canReply)
                <div wire:key="ths-drop-overlay" x-show="dragging > 0" x-cloak
                     class="pointer-events-none absolute inset-2 z-20 grid place-items-center rounded-2xl border-2 border-dashed border-[var(--md-sys-color-primary)] bg-[color-mix(in_srgb,var(--md-sys-color-primary-container)_75%,transparent)] text-[var(--md-sys-color-on-primary-container)] text-sm font-bold text-center">
                    <span class="flex flex-col items-center gap-2">
                        <span class="material-symbols-rounded text-[40px]">upload_file</span>
                        فایل‌ها را اینجا رها کنید
                    </span>
                </div>
            @endif
        </div>
    @endif
</div>
