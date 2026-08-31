@php
    $p = $this->presenter;
    $header = $p->channelHeader($this->activeChannel);
    $isOwner = $this->activeChannel
        && $this->activeChannel->owner_id !== null
        && auth()->check()
        && (int) $this->activeChannel->owner_id === (int) auth()->id();
@endphp
<div x-show="showInfo"
     x-transition:enter="ease-out duration-300"
     x-transition:enter-start="opacity-0 -translate-x-8"
     x-transition:enter-end="opacity-100 translate-x-0"
     x-transition:leave="ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0 -translate-x-8"
     x-on:click.outside="showInfo = false"
     class="absolute inset-y-0 left-0 z-30 w-72 md:w-80 bg-[var(--md-sys-color-primary-container)] overflow-y-auto contact-scrollbar rounded-xl"
     role="dialog">
    <div class="p-5 space-y-5 border-none">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">اطلاعات کانال</h3>
            <button x-on:click="showInfo = false" aria-label="بستن"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)]">
                <span class="material-symbols-rounded text-base" aria-hidden="true">close</span>
            </button>
        </div>
        <div class="flex flex-col items-center gap-3 py-4">
            <div class="w-20 h-20 rounded-xl flex items-center justify-center text-2xl shadow-lg bg-[linear-gradient(135deg,var(--md-sys-color-primary),var(--md-sys-color-secondary))] text-[var(--md-sys-color-on-primary)]">
                <span class="material-symbols-rounded text-4xl">{{ $header['type_icon'] }}</span>
            </div>
            <p class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">{{ $header['name'] }}</p>
            <span class="text-[11px] font-medium px-2 py-0.5 rounded-md bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">{{ $header['type_label'] }}</span>
        </div>
        <div class="space-y-3">
            @if($header['description'])
                <div class="px-3 py-2.5 rounded-xl bg-[var(--md-sys-color-surface)]">
                    <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] mb-1">توضیحات</p>
                    <p class="text-xs leading-relaxed whitespace-pre-wrap text-[var(--md-sys-color-on-surface)]">{{ $header['description'] }}</p>
                </div>
            @endif
            <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[var(--md-sys-color-primary)]">
                <span class="material-symbols-rounded text-base text-[var(--md-sys-color-on-primary)]" aria-hidden="true">groups</span>
                <div>
                    <p class="text-[10px] text-[var(--md-sys-color-on-primary)]">اعضا</p>
                    <p class="text-xs font-medium text-[var(--md-sys-color-on-primary)]">{{ $header['members_count'] }} نفر</p>
                </div>
            </div>
            <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[var(--md-sys-color-primary)]">
                <span class="material-symbols-rounded text-base text-[var(--md-sys-color-on-primary)]" aria-hidden="true">person</span>
                <div class="min-w-0">
                    <p class="text-[10px] text-[var(--md-sys-color-on-primary)]">مدیر کانال</p>
                    <p class="text-xs font-medium truncate text-[var(--md-sys-color-on-primary)]">{{ $header['owner_name'] }}</p>
                </div>
            </div>
        </div>
        @if($isOwner)
            @php $pendingInvitees = $this->pendingInvitees; @endphp
            @if($pendingInvitees)
                <div class="px-3 py-2.5 rounded-xl bg-[var(--md-sys-color-surface)]">
                    <p class="text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)] mb-2 flex items-center gap-1" title="کاربرانی که توسط شما دعوت شده‌اند اما هنوز یک‌بار وارد کانال نشده‌اند">
                        <span class="material-symbols-rounded text-[13px]" aria-hidden="true">hourglass_top</span>
                        در انتظار ورود ({{ convertToPersian((string) count($pendingInvitees)) }})
                    </p>
                    <div class="space-y-1.5">
                        @foreach($pendingInvitees as $invitee)
                            <div class="flex items-center gap-2.5 px-2 py-1.5 rounded-lg bg-[color-mix(in_srgb,var(--md-sys-color-secondary-container)_40%,transparent)]">
                                <div class="w-7 h-7 flex-shrink-0 rounded-full flex items-center justify-center text-[11px] font-bold bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]" aria-hidden="true">
                                    {{ mb_substr($invitee['name'], 0, 1) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[12px] font-medium truncate text-[var(--md-sys-color-on-surface)]">{{ $invitee['name'] }}</p>
                                    @if($invitee['cancellable'])
                                        <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-80">دعوت‌شده · منتظر نخستین ورود</p>
                                    @else
                                        <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-80">دعوت دریافت شده و قابل لغو نیست</p>
                                    @endif
                                </div>
                                @if($invitee['cancellable'])
                                    <div x-show="cancelInviteeId === {{ $invitee['id'] }}" style="display: none;" class="flex items-center gap-1 flex-shrink-0">
                                        <button type="button" x-on:click="cancelInviteeId = null"
                                                class="px-2 py-1 rounded-lg text-[10px] font-semibold transition-all duration-150 hover:brightness-90 active:scale-95 text-[var(--md-sys-color-on-surface-variant)] bg-[var(--md-sys-color-surface-variant)]"
                                                title="انصراف از لغو">
                                            انصراف
                                        </button>
                                        <button type="button" x-on:click="doCancelInvite({{ $header['id'] }}, {{ $invitee['id'] }})"
                                                wire:loading.attr="disabled" wire:target="cancelInvite"
                                                class="px-2 py-1 rounded-lg text-[10px] font-bold transition-all duration-150 hover:brightness-110 active:scale-95 bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)]"
                                                title="لغو این دعوت">
                                            لغو دعوت
                                        </button>
                                    </div>
                                    <button x-show="cancelInviteeId !== {{ $invitee['id'] }}" style="display: none;" type="button"
                                            x-on:click="confirmCancelInvite({{ $invitee['id'] }})"
                                            class="w-7 h-7 flex-shrink-0 rounded-lg flex items-center justify-center transition-all active:scale-90 text-[var(--md-sys-color-error)] bg-[color-mix(in_srgb,var(--md-sys-color-error-container)_50%,transparent)] hover:!bg-[var(--md-sys-color-error-container)]"
                                            title="لغو دعوت" aria-label="لغو دعوت {{ $invitee['name'] }}">
                                        <span class="material-symbols-rounded text-[15px]" aria-hidden="true">close</span>
                                    </button>
                                @else
                                    <span class="w-7 h-7 flex-shrink-0 rounded-full flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] opacity-60 cursor-not-allowed bg-[var(--md-sys-color-surface-variant)]"
                                          title="این کاربر دعوت را دریافت کرده و دیگر قابل لغو نیست">
                                        <span class="material-symbols-rounded text-[15px]" aria-hidden="true">mark_email_read</span>
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] mt-2 opacity-80">با نخستین ورودشان، از این فهرست حذف می‌شوند.</p>
                </div>
            @endif
            <button x-on:click="openManageMembers({{ $header['id'] }})" type="button"
                    class="w-full flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-[12px] font-semibold transition-all hover:brightness-110 active:scale-95 bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/50">
                <span class="material-symbols-rounded text-[18px]">group_add</span>
                مدیریت اعضا
            </button>
        @endif
    </div>
</div>