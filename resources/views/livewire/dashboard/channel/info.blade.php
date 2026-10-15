@php
    $p = $this->presenter;
    $channel = $this->activeChannel;
    $header = $p->channelHeader($channel);
    $isOwner = $channel
        && $channel->owner_id !== null
        && auth()->check()
        && (int) $channel->owner_id === (int) auth()->id();
    $pendingInvitees = $isOwner ? $this->pendingInvitees : [];
@endphp

<div x-show="showInfo"
     x-transition:enter="animate-slide-fade-from-left"
     x-transition:leave="animate-slide-fade-to-left"
     x-on:click.outside="showInfo = false"
     class="absolute inset-y-0 left-0 z-30 flex w-72 md:w-80 flex-col overflow-hidden rounded-xl border-r border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] shadow-[0_16px_48px_color-mix(in_srgb,var(--md-sys-color-shadow)_24%,transparent)] bg-[var(--md-sys-color-primary-container)] "
     role="dialog"
     aria-label="اطلاعات گروه">

    <div class="flex flex-shrink-0 flex-col gap-3 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-low)_60%,transparent)] px-4 pt-3.5 pb-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                    <span class="material-symbols-rounded text-[15px]">tag</span>
                </span>
                <h3 class="text-xs font-bold tracking-tight text-[var(--md-sys-color-on-surface)]">اطلاعات گروه</h3>
            </div>
            <x-ui.modals.close-button close="showInfo = false"/>
        </div>

        <x-ui.buttons.tab-selector
            hasA11y
            :activeTab="''"
            :buttonBaseClass="'flex-1 justify-center px-2 py-1.5 rounded-lg text-[11px] font-bold transition-all duration-150'"
            :iconBaseClass="'material-symbols-rounded text-[14px]'"
            class="!mb-0 !w-full"
            :tabs="[
                ['id' => 'info', 'icon' => 'info', 'label' => 'مشخصات', 'description' => 'اطلاعات گروه',
                 'alpine' => 'if (filesOpen) toggleFiles()', 'activeExpr' => '!filesOpen'],
                ['id' => 'files', 'icon' => 'folder_open', 'label' => 'پیوست‌ها', 'description' => 'همهٔ فایل‌های ردوبدل‌شدهٔ این گفتگو',
                 'alpine' => 'if (!filesOpen) toggleFiles()', 'activeExpr' => 'filesOpen', 'countExpr' => 'filesList.length'],
            ]"/>
    </div>

    <div x-show="!filesOpen" class="flex flex-1 min-h-0 flex-col justify-between p-4 gap-3 overflow-hidden">
        <div class="flex flex-col gap-2.5 min-h-0 overflow-y-auto contact-scrollbar">
            <div class="flex items-center gap-3 rounded-2xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[var(--md-sys-color-surface-container-low)] p-3">
                <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl shadow-sm bg-[linear-gradient(135deg,var(--md-sys-color-primary),var(--md-sys-color-secondary))] text-[var(--md-sys-color-on-primary)]">
                    <span class="material-symbols-rounded text-2xl">{{ $header['type_icon'] }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-[var(--md-sys-color-on-surface)]">{{ $header['name'] }}</p>
                    <div class="mt-1 flex items-center gap-1.5">
                        <span class="inline-flex items-center rounded-md bg-[var(--md-sys-color-secondary-container)] px-2 py-0.5 text-[10px] font-bold text-[var(--md-sys-color-on-secondary-container)]">
                            {{ $header['type_label'] }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div class="flex items-center gap-2.5 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] bg-[var(--md-sys-color-surface-container-low)] p-2.5">
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-base" aria-hidden="true">groups</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">اعضا</p>
                        <p class="truncate text-xs font-bold text-[var(--md-sys-color-on-surface)]">{{ convertToPersian($header['members_count']) }} نفر</p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] bg-[var(--md-sys-color-surface-container-low)] p-2.5">
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-base" aria-hidden="true">person</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">مدیر گروه</p>
                        <p class="truncate text-xs font-bold text-[var(--md-sys-color-on-surface)]" title="{{ $header['owner_name'] }}">{{ $header['owner_name'] }}</p>
                    </div>
                </div>
            </div>

            @if($header['description'])
                <div class="rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] bg-[var(--md-sys-color-surface-container-low)] px-3 py-2">
                    <p class="mb-0.5 text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)]">توضیحات</p>
                    <p class="max-h-16 overflow-y-auto contact-scrollbar text-xs leading-relaxed whitespace-pre-wrap text-[var(--md-sys-color-on-surface)]">{{ $header['description'] }}</p>
                </div>
            @endif

            @if($isOwner && $pendingInvitees)
                <div class="flex min-h-0 flex-1 flex-col rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] bg-[var(--md-sys-color-surface-container-low)] p-2.5 overflow-hidden">
                    <div class="mb-2 flex flex-shrink-0 items-center justify-between gap-1" title="کاربرانی که توسط شما دعوت شده‌اند اما هنوز یک‌بار وارد گروه نشده‌اند">
                        <span class="flex items-center gap-1 text-[10px] font-bold text-[var(--md-sys-color-on-surface-variant)]">
                            <span class="material-symbols-rounded text-[13px] text-[var(--md-sys-color-primary)]" aria-hidden="true">hourglass_top</span>
                            <span>در انتظار ورود ({{ convertToPersian((string) count($pendingInvitees)) }})</span>
                        </span>
                        <span class="text-[9px] text-[var(--md-sys-color-on-surface-variant)] opacity-75">حذف خودکار پس از ورود</span>
                    </div>

                    <div class="flex-1 min-h-0 space-y-1.5 overflow-y-auto contact-scrollbar pe-0.5">
                        @foreach($pendingInvitees as $invitee)
                            <div class="flex items-center gap-2 rounded-lg bg-[var(--md-sys-color-surface)] px-2 py-1.5 border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_25%,transparent)]">
                                <div class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-[var(--md-sys-color-secondary-container)] text-[10px] font-bold text-[var(--md-sys-color-on-secondary-container)]" aria-hidden="true">
                                    {{ mb_substr($invitee['name'], 0, 1) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[11px] font-semibold text-[var(--md-sys-color-on-surface)]">{{ $invitee['name'] }}</p>
                                    @if($invitee['cancellable'])
                                        <p class="truncate text-[9px] text-[var(--md-sys-color-on-surface-variant)] opacity-80">دعوت‌شده · منتظر نخستین ورود</p>
                                    @else
                                        <p class="truncate text-[9px] text-[var(--md-sys-color-on-surface-variant)] opacity-80">دعوت دریافت شده و قابل لغو نیست</p>
                                    @endif
                                </div>

                                @if($invitee['cancellable'])
                                    <div x-show="cancelInviteeId === {{ $invitee['id'] }}" style="display: none;" class="flex flex-shrink-0 items-center gap-1">
                                        <button type="button"
                                                x-on:click="cancelInviteeId = null"
                                                class="rounded-md bg-[var(--md-sys-color-surface-variant)] px-1.5 py-1 text-[9px] font-semibold text-[var(--md-sys-color-on-surface-variant)] transition-all hover:brightness-95 active:scale-95"
                                                title="انصراف از لغو">
                                            انصراف
                                        </button>
                                        <button type="button"
                                                x-on:click="doCancelInvite({{ $header['id'] }}, {{ $invitee['id'] }})"
                                                wire:loading.attr="disabled"
                                                wire:target="cancelInvite"
                                                class="rounded-md bg-[var(--md-sys-color-error)] px-1.5 py-1 text-[9px] font-bold text-[var(--md-sys-color-on-error)] transition-all hover:brightness-110 active:scale-95"
                                                title="لغو این دعوت">
                                            لغو دعوت
                                        </button>
                                    </div>
                                    <button x-show="cancelInviteeId !== {{ $invitee['id'] }}"
                                            style="display: none;"
                                            type="button"
                                            x-on:click="confirmCancelInvite({{ $invitee['id'] }})"
                                            class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-md bg-[color-mix(in_srgb,var(--md-sys-color-error-container)_50%,transparent)] text-[var(--md-sys-color-error)] transition-all hover:!bg-[var(--md-sys-color-error-container)] active:scale-90"
                                            title="لغو دعوت"
                                            aria-label="لغو دعوت {{ $invitee['name'] }}">
                                        <span class="material-symbols-rounded text-[14px]" aria-hidden="true">close</span>
                                    </button>
                                @else
                                    <span class="flex h-6 w-6 flex-shrink-0 cursor-not-allowed items-center justify-center rounded-full bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] opacity-60"
                                          title="این کاربر دعوت را دریافت کرده و دیگر قابل لغو نیست">
                                        <span class="material-symbols-rounded text-[14px]" aria-hidden="true">mark_email_read</span>
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        @if($isOwner)
            <div class="flex flex-shrink-0 flex-col gap-2 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] pt-3">
                <button x-on:click="openManageMembers({{ $header['id'] }})"
                        type="button"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-surface-container-high)] px-3 py-2 text-xs font-bold text-[var(--md-sys-color-on-surface)] transition-all hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-[0.98]">
                    <span class="material-symbols-rounded text-[17px]">group_add</span>
                    <span>مدیریت اعضا</span>
                </button>

                @if((int) $header['members_count'] === 1 && $channel->isProjectLinked())
                    <p wire:key="channel-info-project-linked" class="text-center text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-80">
                        گفتگوی پروژه است و با حذف پروژه حذف می‌شود.
                    </p>
                @elseif((int) $header['members_count'] === 1)
                    <div wire:key="channel-info-delete-group">
                        <div x-show="!confirmingDeleteChannel" style="display: none;">
                            <button type="button"
                                    x-on:click="confirmDeleteChannel()"
                                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-[color-mix(in_srgb,var(--md-sys-color-error-container)_65%,transparent)] px-3 py-2 text-xs font-bold text-[var(--md-sys-color-on-error-container)] transition-all hover:brightness-105 active:scale-[0.98]">
                                <span class="material-symbols-rounded text-[17px]">delete_forever</span>
                                <span>حذف گروه</span>
                            </button>
                        </div>

                        <div x-show="confirmingDeleteChannel"
                             style="display: none;"
                             class="flex flex-col gap-2 rounded-xl bg-[var(--md-sys-color-error-container)] p-2.5 text-[var(--md-sys-color-on-error-container)]">
                            <p class="text-[11px] font-bold">این گروه حذف خواهد شد. مطمئن هستید؟</p>
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button"
                                        x-on:click="cancelDeleteChannel()"
                                        class="rounded-lg bg-[color-mix(in_srgb,var(--md-sys-color-on-error-container)_12%,transparent)] px-3 py-1 text-[11px] font-semibold transition-all hover:brightness-90 active:scale-95">
                                    انصراف
                                </button>
                                <x-ui.buttons.form x-on:click.prevent="doDeleteChannel({{ $header['id'] }})"
                                                   wire:loading.attr="disabled"
                                                   wire:target="deleteChannel"
                                                   loading="deleteChannel"
                                                   class="h-auto rounded-lg bg-[var(--md-sys-color-error)] px-3 py-1 text-[11px] font-bold text-[var(--md-sys-color-on-error)] transition-all hover:brightness-110">
                                    حذف گروه
                                </x-ui.buttons.form>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    @include('livewire.dashboard.messaging.files')
</div>
